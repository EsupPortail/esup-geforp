<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 28/04/14
 * Time: 10:41.
 */

namespace App\BatchOperations\Generic;

use App\Utils\HumanReadable\HumanReadablePropertyAccessorFactory;
use App\Vocabulary\VocabularyRegistry;
use Doctrine\ORM\EntityManager;
use MBence\OpenTBSBundle\Services\OpenTBS;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use App\Utils\ArrayFunctions;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Process\Exception\RuntimeException;
use App\Entity\Term\PublipostTemplate;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use App\BatchOperations\AbstractBatchOperation;
use App\BatchOperations\BatchOperationModalConfigInterface;

/**
 * Class MailingBatchOperation.
 */
class MailingBatchOperation extends AbstractBatchOperation implements BatchOperationModalConfigInterface, ContainerAwareInterface
{
    /** @var string Current template as a filename */
    private $currentTemplateFileName;

    /** @string current tempalte filename */
    private $currentTemplate;

    /** @var Container service container */
    private $container;

    /** @var Security security  */
    private $security;

    protected $parameterBag;
    protected $vocRegistry;
    protected $hrpaf;

    /** @var array */
    protected $idList = array();

    /**
     * @param SecurityContext $securityContext
     *
     * @internal param $path
     */
    public function __construct(Security $security, ParameterBagInterface $parameterBag, VocabularyRegistry $vocRegistry, HumanReadablePropertyAccessorFactory $hrpaf)
    {
        $this->options['tempDir'] = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sygefor'.DIRECTORY_SEPARATOR;
        if (!file_exists($this->options['tempDir'])) {
            mkdir($this->options['tempDir'], 0777);
        }
        $this->parameterBag = $parameterBag;
        $this->security = $security;
        $this->vocRegistry = $vocRegistry;
        $this->hrpaf = $hrpaf;
    }

    /**
     * Get directory where generating file are written.
     *
     * @return string
     */
    public function getTempDir()
    {
        return isset($this->options['tempDir']) ? $this->options['tempDir'] : sys_get_temp_dir();
    }

    /**
     * make fields available.
     *
     * @return mixed
     */
    public function getFields()
    {
        return $this->options['fields'];
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * creates the result file and stores it on disk.
     *
     * @param array $idList
     * @param array $options
     *
     * @return mixed
     */
    public function execute(array $idList = array(), array $options = array())
    {
        $this->idList = $idList;
        $entities = $this->getObjectList($idList);
        $this->setOptions($options);
        $deleteTemplate = false;

        //---setting choosed template file
        // 1/ File was provided by user
        if (isset($this->options['attachment']) && !empty($this->options['attachment'])) {
            $attachment = $this->options['attachment'][0];
            $attachment->move($this->options['tempDir'], $attachment->getClientOriginalName());
            $this->currentTemplate = $this->options['tempDir'].$attachment->getClientOriginalName();
            $this->currentTemplateFileName = $attachment->getClientOriginalName();
            $deleteTemplate = true;
        } elseif (isset($this->options['template']) && (is_integer($this->options['template']))) {
            //file was choosed in template list
            $templateTerm = $this->vocRegistry->getVocabularyById(1); // vocabulary_publipost_template;
            /** @var EntityManager $em */
            $em = $this->doctrine->getManager();
            $repo = $em->getRepository(get_class($templateTerm));
            /** @var PublipostTemplate[] $templates */
            $template = $repo->find($this->options['template']);

            $this->currentTemplate = $template->getAbsolutePath();
            $this->currentTemplateFileName = $template->getFileName();
        } else {
            // 3/ Error...
            return '';
        }
        $parseInfos = $this->parseFile($this->currentTemplate, $entities);

        if ($deleteTemplate) {
            unlink($this->currentTemplate);
        }

        return $parseInfos;
    }

    /**
     * Gets a file from module's temp dir if exists, and send it to client.
     *
     * @param $fileName
     * @param null  $outputFileName
     * @param array $options
     *
     * @internal param bool $pdf
     * @internal param bool $return
     *
     * @return string|Response
     */
    public function sendFile($fileName, $outputFileName = null, $options = array('pdf' => false, 'return' => false))
    {
        if (file_exists($this->options['tempDir'].$fileName)) {

            //security check first : if requested file path doesn't correspond to temp dir,
            //triggering error
            $path_parts = pathinfo($this->options['tempDir'].$fileName);

            $response = new Response();
            if (realpath($path_parts['dirname']) !== $this->options['tempDir']) {
                $response->setContent('Accès non autorisé :'.$path_parts['dirname']);
            }

            // setting output file name
            $outputFileName = (empty($outputFileName)) ? $fileName : $outputFileName;
            //if pdf file is asked
            if (isset($options['pdf']) && $options['pdf']) {
                $pdfName = $this->toPdf($fileName);
                $fp = $this->options['tempDir'].$pdfName;

                //renaming output filename (for end user)
                $tmp = explode('.', $outputFileName);
                $tmp[count($tmp) - 1] = 'pdf';
                $outputFileName = implode('.', $tmp);
            } else {
                $fp = $this->options['tempDir'].$fileName;
            }

            if (isset($options['return']) && $options['return']) {
                $file = new File($fp);

                return $file->move($file->getFileInfo()->getPath(), $outputFileName);
            } else {
                // Set headers
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $response->headers->set('Cache-Control', 'private');
                $response->headers->set('Content-type', finfo_file($finfo, $fp));
                $response->headers->set('Content-Disposition', 'attachment; filename="'.$outputFileName.'";');
                $response->headers->set('Content-length', filesize($fp));
                $response->sendHeaders();
                $response->setContent(readfile($fp));
                $response->sendContent();

                // file is then deleted
                unlink($fp);

                return $response;
            }
        }

        return '';
    }

    /**
     * @param string|PublipostTemplate $template $template
     * @param array                    $entities
     *
     * @return array
     */
    public function parseFile($template, $entities, $getFile = false, $outputFileName = '', $getPdf = false)
    {
        /*
        list($TBS, $classCatalog) = $this->initializeOpenTbs($template, $entities);
        $lines = $this->getTemplateLines($entities);
        $errors = $this->mergeLinesWithPublipostTemplate($TBS, $lines);
        if (count($errors) > 0) {
            return $errors;
        }
        $this->computeShorcutsAndMerge($TBS, $entities, $classCatalog);
        $fileName = $this->generateFinalFile($TBS, $template);

        return array('fileUrl' => $fileName); */
        //getting the file generator
        $TBS = new OpenTBS();
        $TBS->setOption('noerr', true);

        //loading the template
        $TBS->LoadTemplate($template, OPENTBS_ALREADY_UTF8);

        $alias = $this->hrpaf->getEntityAlias($this->targetClass);
        $entityName = ($alias !== null) ? $alias : 'entity';

        $lines = array();

        $uid = substr(md5(rand()), 0, 5);
        $fileName = $this->removeAccents($uid . '_' . ((!empty($this->currentTemplateFileName) ? $this->currentTemplateFileName : $template->getFileName())));

        // Mise en page spécifique pour feuille émargement pour une session
        $fileTest = stripos($fileName, "EmargementSession");
        // Mise en page spécifique pour liste des participants pour une session
        $fileTest2 = stripos($fileName, "ListeParticipants");
        // Mise en page spécifique pour fiche individuelle de formation pour un stagiaire
        $fileTest3 = stripos($fileName, "FicheFormation");
        // Mise en page spécifique pour fiche individuelle de formation pour un formateur
        $fileTest4 = stripos($fileName, "FicheFormateur");
        // Mise en page spécifique pour liste des convoqués pour une session
        $fileTest5 = stripos($fileName, "ListeAcceptes");

        if ($fileTest === false)  {
            if ($fileTest2==false)  {
                if ($fileTest3==false) {
                    if ($fileTest4==false) {
                        if ($fileTest5==false) {
                            //tous les autres cas
                            //iterating through properties to construct a (nested) array of properties => values
                            foreach ($entities as $entity) {
                                //                    if ($this->securityContext->isGranted('VIEW', $entity)) {
                                $data = $this->hrpaf->getAccessor($entity);
                                $lines[$entity->getId()] = $data;
                                //                    }
                            }

                            if (!empty($this->idList)) {
                                $this->reorderByKeys($lines, $this->idList);
                            }
                        } else {
                            // Cas de la liste des inscrits à une session acceptés
                            //                if ($this->securityContext->isGranted('VIEW', $entities[0])) {
                            $data = $this->hrpaf->getAccessor($entities[0]);

                            $lines[0]['dateDebut'] = $data->dateDebut;
                            $lines[0]['nom'] = $data->nom;

                            $inscriptions = $entities[0]->getInscriptions();
                            foreach ($inscriptions as $insc) {
                                if ($insc->getInscriptionstatus() == 'Accepté') {
                                    $lines[0]['inscriptions'][] = array('nom' => $insc->getTrainee()->getLastname(), 'prenom' => $insc->getTrainee()->getFirstname(), 'nomComplet' => $insc->getTrainee()->getFullname(), 'mail' => $insc->getTrainee()->getEmail(), 'unite' => $insc->getTrainee()->getInstitution()->getName(), 'service' => $insc->getTrainee()->getService(), 'corps' => $insc->getTrainee()->getCorps(), 'bap' => $insc->getTrainee()->getBap(), 'fonction' => $insc->getTrainee()->getFonction() );
                                }
                            }
                            usort($lines[0]['inscriptions'], function($a, $b) {
                                return strcasecmp($a['nom'], $b['nom']);
                            });
                            $entityName = 's';
//                }
                        }
                    } else {
                        // Cas de la fiche de formation individuelle pour un formateur
//                if ($this->securityContext->isGranted('VIEW', $entities[0])) {
                        $data = $this->hrpaf->getAccessor($entities[0]);

                        $lines[0]['nom'] = $data->nom;
                        $lines[0]['prenom'] = $data->prenom;
                        $lines[0]['dateJour'] = date("d/m/Y");

                        // Tri des sessions par date de session
                        $sessions = $entities[0]->getSessions();

                        // Création d'un tableau intermédiaire pour comparer les dates et trier le tableau à l'aide de timestamp
                        $a = array();
                        foreach($sessions as $k) {
                            //$date = date_create_from_format('d/m/Y', $k->dateDebut);
                            //$dateDeb = $date->format('Y-m-d');
                            $dateDeb = $k->getDatebegin()->format('d/m/Y');
                            $timestamp = strtotime($dateDeb);
                            $a[$timestamp] = $k;
                        }
                        // Tri du tableau par date croissante
                        ksort($a);
                        // Tri du tableau par date décroissante
                        $b = array_reverse($a);
                        $newSessions = array_values($b);

                        foreach ($newSessions as $sess) {
                            $lines[0]['sessions'][] = array('dateDebut' => $sess->getDatebegin()->format('d/m/Y'),
                                'nombreHeures' => $sess->getHournumber(),
                                'nom' => $sess->getName(),
                                'domaine' => $sess->getTraining()->getTheme());
                        }
                        $entityName = 'formateur';
//                }
                    }
                } else {
                    // Cas de la fiche de formation individuelle pour un stagiaire
//                if ($this->securityContext->isGranted('VIEW', $entities[0])) {
                    $data = $this->hrpaf->getAccessor($entities[0]);

                    $lines[0]['civilite'] = $data->civilite;
                    $lines[0]['nomComplet'] = $data->nomComplet;
                    $lines[0]['corps'] = $data->corps;
                    $lines[0]['dateJour'] = date("d/m/Y");
                    $lines[0]['date1insc'] = "";

                    // Tri des inscriptions par date de session
                    $inscriptions = $entities[0]->getInscriptions();

                    // Création d'un tableau intermédiaire pour comparer les dates et trier le tableau à l'aide de timestamp
                    $a = array();
                    foreach($inscriptions as $k) {
                        //$date = date_create_from_format('d/m/Y', $k->session->dateDebut);
                        //$dateDeb = $date->format('Y-m-d');
                        $dateDeb = $k->getSession()->getDatebegin()->format('d/m/Y');
                        $timestamp = strtotime($dateDeb);
                        $a[$timestamp] = $k;
                    }
                    // Tri du tableau par date croissante
                    ksort($a);
                    // Récupération de la date de la première formation suivie
                    foreach ($a as $tinsc) {
                        if (($tinsc->getPresencestatus() == 'Présent') || ($tinsc->getPresencestatus() == 'Partiel')) {
                            // on récupère la date de la première formation du stagiaire
                            $lines[0]['date1insc'] = $tinsc->getSession()->getDateBegin()->format('d/m/Y');
                            break;
                        }
                    }

                    // Tri du tableau par date décroissante
                    $b = array_reverse($a);
                    $newInscriptions = array_values($b);

                    foreach ($newInscriptions as $insc) {
                        $formateurs = "";
                        $session = $insc->getSession();
                        // on ne garde que les inscriptions où le stagiaire a été présent
                        if (($insc->getPresencestatus() == 'Présent') || ($insc->getPresencestatus() == 'Partiel')) {
                            // récupération des formateurs pour une session
                            foreach ($session->getTrainers() as $formateur) {
                                $formateurs .= $formateur->getFullname() . " ; ";
                            }

                            // On crée le tableau de dates correspondant au tableau des présences
                            $tabDates = array();
                            foreach ($session->getDates() as $dateSes) {
                                // Conversion date de début de session
                                $dateDeb = strtotime(str_replace("/", "-", $dateSes->getDatebegin()->format('d/m/Y')));
                                // création du tableau des dates suivant le nombre de jours à afficher
                                for ($j = 0; $j < $session->getDaynumber() + 1; $j++) {
                                    $dateNew = date('d/m/Y', $dateDeb + $j*86400);
                                    $tabDates[] = array("dateDeb" => $dateNew, "nbHeuresMatin" => $dateSes->getHournumbermorn(), "nbHeuresApr" => $dateSes->getHournumberafter());
                                }
                            }

                            // calcul du nombre d'heures de présence effective
                            // On initialise le nombre d'heures de présence
                            $nbHeuresPresence = 0;
                            // Pour chaque presence, on compare avec le tableau des dates et on calcule le nombre d'heures
                            foreach ($insc->getPresences() as $pres) {
                                foreach ($tabDates as $datePres) {
                                    if ($pres->getDateBegin() == $datePres["dateDeb"]) {
                                        if ($pres->morning == "Présent") {
                                            $nbHeuresPresence += $datePres["nbHeuresMatin"];
                                        }
                                        if ($pres->afternoon == "Présent") {
                                            $nbHeuresPresence += $datePres["nbHeuresApr"];
                                        }
                                        break;
                                    }
                                }
                            }
                            if ($insc->getActiontype() == null)
                                $typAc = "";
                            else
                                $typAc = $insc->getActiontype()->getName();


                            $lines[0]['inscriptions'][] = array('dateDebut' => $insc->getSession()->getDatebegin()->format('d/m/Y'),
                                'nombreHeures' => $insc->getSession()->getHournumber(),
                                'nombreHeuresPres' => $nbHeuresPresence,
                                'nom' => $insc->getSession()->getName(),
                                'domaine' => $insc->getSession()->getTraining()->getTheme(),
                                "formateurs" => $formateurs,
                                "type" => $insc->getSession()->getTraining()->getCategory(),
                                "typeAction" => $typAc);
                        }
                    }
                    $entityName = 'stagiaire';
//                }
                }
            } else {
                // Cas de la liste des participants à une session
//                if ($this->securityContext->isGranted('VIEW', $entities[0])) {
                $data = $this->hrpaf->getAccessor($entities[0]);

                $lines[0]['dateDebut'] = $data->dateDebut;
                $lines[0]['nom'] = $data->nom;

//                foreach ($data->inscriptions as $insc) {
//                    if ($insc->statutInscription == 'Convoqué') {
//                        $lines[0]['inscriptions'][] = array('nom' => $insc->stagiaire->nom, 'prenom' => $insc->stagiaire->prenom, 'nomComplet' => $insc->stagiaire->nomComplet, 'mail' => $insc->stagiaire->email, 'unite' => $insc->stagiaire->institution->nom, 'service' => $insc->stagiaire->service, 'corps' => $insc->stagiaire->corps, 'bap' => $insc->stagiaire->bap, 'fonction' => $insc->stagiaire->fonction );
//                    }
//                }

                $inscriptions = $entities[0]->getInscriptions();
                foreach ($inscriptions as $insc) {
                    if ($insc->getInscriptionstatus() == 'Convoqué') {
                        $lines[0]['inscriptions'][] = array('nom' => $insc->getTrainee()->getLastname(), 'prenom' => $insc->getTrainee()->getFirstname(), 'nomComplet' => $insc->getTrainee()->getFullname(), 'mail' => $insc->getTrainee()->getEmail(), 'unite' => $insc->getTrainee()->getInstitution()->getName(), 'service' => $insc->getTrainee()->getService(), 'corps' => $insc->getTrainee()->getCorps(), 'bap' => $insc->getTrainee()->getBap(), 'fonction' => $insc->getTrainee()->getFonction() );
                    }
                }

                if (isset($lines[0]['inscriptions'])) {
                    usort($lines[0]['inscriptions'], function ($a, $b) {
                        return strcasecmp($a['nom'], $b['nom']);
                    });
                }

                $entityName = 's';
//                }
            }

        } else {
            // Cas de la feuille d'émargement pour une session
//            if ($this->securityContext->isGranted('VIEW', $entities[0])) {
            $data = $this->hrpaf->getAccessor($entities[0]);

            function cmp($a, $b) {
                $dateDebA = strtotime(str_replace("/", "-", $a->dateDebut));
                $dateDebB = strtotime(str_replace("/", "-", $b->dateDebut));
                if ($dateDebA == $dateDebB) {
                    return 0;
                }
                return ($dateDebA < $dateDebA) ? -1 : 1;
            }
            //$data->dates ->uasort('cmp');

            $Dates = $entities[0]->getDates();
            $inscriptions = $entities[0]->getInscriptions();
            $formateurs = $entities[0]->getTrainers();

            $i=0;
            foreach ($Dates as $date){
                // Test sur le nombre de jours à afficher
                $dateDeb = strtotime(str_replace("/", "-", $date->getDatebegin()->format('d/m/Y')));
                $dateFin = strtotime(str_replace("/", "-", $date->getDateend()->format('d/m/Y')));
                $diff = abs($dateFin - $dateDeb)/86400;

                for ($j=0; $j<$diff+1; $j++) {
                    $lines[$i]['dateDebut'] = date('d/m/Y', $dateDeb + $j*86400);
                    $lines[$i]['dateFin'] = date('d/m/Y', $dateDeb + $j*86400);
                    $lines[$i]['horairesMatin'] = $date->getScheduleMorn();
                    $lines[$i]['horairesAprem'] = $date->getScheduleAfter();
                    $lines[$i]['lieu'] = $date->getPlace();
                    $lines[$i]['nom'] = $data->nom;

                    foreach ($formateurs as $formateur) {
                        $lines[$i]['formateurs'][] = array('nom' => $formateur->getLastname(), 'prenom' => $formateur->getFirstname());
                    }
                    foreach ($inscriptions as $insc) {
                        if ($insc->getInscriptionstatus() == 'Convoqué') {
                            $lines[$i]['inscriptions'][] = array('nom' => $insc->getTrainee()->getLastname(), 'prenom' => $insc->getTrainee()->getFirstname(), 'nomComplet' => $insc->getTrainee()->getFullname(), 'mail' => $insc->getTrainee()->getEmail(), 'unite' => $insc->getTrainee()->getInstitution()->getName(), 'service' => $insc->getTrainee()->getService() );
                        }
                    }
                    usort($lines[$i]['inscriptions'], function($a, $b) {
                        return strcasecmp($a['nom'], $b['nom']);
                    });
                    $i += 1;
                }
            }

            $entityName = 's';
//            }
        }

        ob_start();


        // merge all fields from the first object
        //fields are merged one by one, so that we dont have to recall a enity name for global names
        if (!empty($lines)) {
//            $vals = current($lines)->toArray();
//            //var_dump($vals);die();
//            foreach ($vals as $fieldName => $prop){
//                $TBS->MergeField($fieldName,$prop);
//            }
            //var_dump(current($lines));

            if (($fileTest === false) && ($fileTest2 === false) && ($fileTest3 === false) && ($fileTest4 === false) && ($fileTest5 === false)) {
                $TBS->MergeField('global', current($lines)->toArray());
            }
            else {
                $TBS->MergeField('global', current($lines));
            }
        }

        reset($lines);

        $TBS->MergeBlock($entityName, $lines);

        $error = ob_get_flush();

        if ($error) {
            return array('error' => $error);
        }

        $TBS->Show(OPENTBS_FILE, $this->options['tempDir'] . $fileName);
        $TBS->_PlugIns[OPENTBS_PLUGIN]->Close();

        //do we want the file or just infos about it ?
        if ($getFile) {
            return $this->sendFile($fileName, $outputFileName, array('pdf' => $getPdf, 'return' => true));
        }
        else {
            // file can then be taken using senFile.
            return array('fileUrl' => $fileName);
        }
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function getModalConfig($options = array())
    {
        $templateTerm = $this->vocRegistry->getVocabularyById(1); // vocabulary_publipost_template
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        $repo = $em->getRepository(get_class($templateTerm));
        /** @var PublipostTemplate[] $templates */
        $templates = $repo->findBy(array('organization' => $this->security->getUser()->getOrganization()));

        $files = array();
        foreach ($templates as $template) {
            $templateEntity = $template->getEntity();
            $ancestor = class_parents($this->targetClass);
            //file is added if its associated entity is an ancestor for current target class
            if ($templateEntity === $this->targetClass || in_array($templateEntity, $ancestor, true)) {
                $files[] = array('id' => $template->getId(), 'name' => $template->getName(), 'fileName' => $template->getFileName());
            }
        }

        return array('templateList' => $files);
    }

    /**
     * @param $fileName
     * @param null $outputFileName
     *
     * @return string pdf file name, or null if error
     */
    public function toPdf($fileName, $outputFileName = null)
    {
        if (empty($outputFileName)) {
            $outputFileName = $fileName;
        }

        //renaming output filename (for end user)
        $info = pathinfo($outputFileName);
        $outputFileName = $info['filename'].'.pdf';

        // prepare the process
        $unoconvBin = $this->container->getParameter('unoconv_bin');
        $args = array(
            $unoconvBin,
            '--output='.$this->options['tempDir'].$outputFileName,
            $this->options['tempDir'].$fileName,
        );
        //$process = new Process(implode(' ', $args));
        $process = new Process($args);

        // run
        try {
            $process->run();
        } catch (RuntimeException $exception) {
            // unoconv somtimes returns 8 (SIGFPE) error code but still produces a correct output,
            // so we can ignore it.
//            if ($exception->getCode() !== 8) {
//                throw $exception;
//            }
        }

        if (!empty($process->getErrorOutput())) {
            throw new RuntimeException('The PDF file has not been generated : '.$process->getErrorOutput());
        }

        return $outputFileName;
    }

    /**
     * @param $template
     * @param $entities
     *
     * @return \clsTinyButStrong
     *
     * @throws \Throwable
     */
    protected function initializeOpenTbs($template, $entities)
    {
//        $TBS = $this->container->get('opentbs');
        $TBS = new OpenTBS();
        $TBS->setOption('noerr', true);
        $TBS->LoadTemplate($template, OPENTBS_ALREADY_UTF8);
        $classCatalog = $this->hrpaf->getTermCatalog(get_class(current($entities)));

        return array($TBS, $classCatalog);
    }

    /**
     * @param $entities
     *
     * @return array
     *
     * @throws \Throwable
     */
    protected function getTemplateLines($entities)
    {
        $lines = array();
        foreach ($entities as $entity) {
//            if ($this->securityContext->isGranted('VIEW', $entity)) {
                $lines[$entity->getId()] = $this->hrpaf->getAccessor($entity);
//            }
        }

        return $lines;
    }

    /**
     * @param \clsTinyButStrong $TBS
     * @param array             $lines
     *
     * @return array
     *
     * @throws \Exception
     * @throws \Throwable
     */
    protected function mergeLinesWithPublipostTemplate($TBS, $lines)
    {
        ob_start();
        $alias = $this->hrpaf->getEntityAlias($this->targetClass);
        $entityName = ($alias !== null) ? $alias : 'entity';
        $TBS->MergeBlock($entityName, $lines);
        // add global variable matching first entity
        if (count($lines) > 0) {
	        /**
	         * We need to handle empty arrays ourselves, or TBS will cast them to string "Array".
	         * @see  vendor/mbence/opentbs-bundle/MBence/OpenTBSBundle/lib/tbs_class.php:3360
	         * @var  array  $Value  Named after TBS convention
	         */
	        $Value = ArrayFunctions::emptyArraysToStringsRecursive(
		        reset($lines)->toArray()
	        );

	        $TBS->MergeField('global', $Value);
        }
        $error = ob_get_flush();

        return $error ? array('error' => $error) : array();
    }

    /**
     * Add global variables with publipost shorcuts.
     *
     * @param \clsTinyButStrong $TBS
     * @param array             $entities
     * @param string            $classCatalog
     *
     * @return int
     *
     * @throws \Throwable
     */
    protected function computeShorcutsAndMerge($TBS, $entities, $classCatalog)
    {
        $i = 0;
        if (isset($classCatalog['shorcuts'])) {
            $aliases = $classCatalog['shorcuts'];

            $propertyAccessor = new PropertyAccessor();
            foreach ($aliases as $alias => $params) {
                $arrayValues = array();
                $max = count($entities);
                if (isset($params['current']) && $params['current'] === true && $max > 0) {
                    $max = 1;
                }
                $i = 0;
                $keys = array_keys($entities);
                // get only current entity value with path or all entities values with path
                while ($i < $max) {
                    try {
                        $val = $propertyAccessor->getValue($entities[$keys[$i]], $params['path']);
                        // create an array collection to simplify work in foreach
                        $collection = new ArrayCollection();
                        if (is_object($val) && $val instanceof ArrayCollection) {
                            $collection = $val;
                        } else {
                            $collection->add($val);
                        }
                        // get human readable accessor foreach value
                        foreach ($collection as $item) {
                            $accessor = $this->container->get('sygefor_core.human_readable_property_accessor_factory')->getAccessor($item);
                            if (is_object($item) && method_exists($item, 'getId')) {
                                $id = $item->getId();
                                $arrayValues[$id] = $accessor;
                            } else {
                                $arrayValues[] = $accessor;
                            }
                        }
                    } catch (\Exception $e) {
                    }
                    ++$i;
                }

                if (isset($params['sort']) && $params['sort']) {
                    usort($arrayValues, function ($a, $b) use ($params, $propertyAccessor) {
                        return $propertyAccessor->getValue($a, $params['sort']) > $propertyAccessor->getValue($b, $params['sort']);
                    });
                }

                $TBS->MergeBlock($alias, $arrayValues);
                ++$i;
            }
        }

        return $i;
    }

    /**
     * @param \clsTinyButStrong        $TBS
     * @param string|PublipostTemplate $template
     *
     * @return mixed|string
     */
    protected function generateFinalFile($TBS, $template)
    {
        $uid = uniqid();
        $fileName = empty($this->currentTemplateFileName) ? $template->getFileName() : $this->currentTemplateFileName;
        $uniqFileName = $this->removeAccents($uid.'_'.$fileName);
        $TBS->Show(OPENTBS_FILE, $this->options['tempDir'].$uniqFileName);
        $TBS->_PlugIns[OPENTBS_PLUGIN]->Close();

        return $uniqFileName;
    }

    /**
     * @param $str
     * @param string $charset
     *
     * @return mixed|string
     */
    private function removeAccents($str, $charset = 'utf-8')
    {
        //converting to html elements
        $str = htmlentities($str, ENT_NOQUOTES, $charset);

        //keeping only first char after '&', so that &eacute becomes e for example
        $str = preg_replace('#&([A-Za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&[^;]+;#', '', $str); // removing not recognized chars

        $str = str_replace(' ', '_', $str);
        $str = str_replace('\'', '-', $str);

        return $str;
    }
}

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
use Doctrine\ORM\Exception\NotSupported;
use MBence\OpenTBSBundle\Services\OpenTBS;
use clsTinyButStrong;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use App\Utils\ArrayFunctions;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Process\Exception\RuntimeException;
use App\Entity\Term\PublipostTemplate;
use App\BatchOperations\AbstractBatchOperation;
use App\BatchOperations\BatchOperationModalConfigInterface;

/**
 * Class MailingBatchOperation.
 */
class MailingBatchOperation extends AbstractBatchOperation implements BatchOperationModalConfigInterface
{
    /** @var string Current template as a filename */
    private $currentTemplateFileName;

    /** @string current tempalte filename */
    private $currentTemplate;

    /**
     * @param SecurityContext $securityContext
     *
     * @internal param $path
     */
    public function __construct(/**
     * @var Security security
     */
    private readonly Security $security, protected ParameterBagInterface $parameterBag, protected VocabularyRegistry $vocabularyRegistry, protected HumanReadablePropertyAccessorFactory $humanReadablePropertyAccessorFactory)
    {
        $this->options['tempDir'] = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sygefor'.DIRECTORY_SEPARATOR;
        if (!file_exists($this->options['tempDir'])) {
            mkdir($this->options['tempDir'], 0777);
        }
    }

    /**
     * Get directory where generating file are written.
     *
     */
    public function getTempDir(): string
    {
        return $this->options['tempDir'] ?? sys_get_temp_dir();
    }

    /**
     * make fields available.
     *
     */
    public function getFields(): mixed
    {
        return $this->options['fields'];
    }

    /**
     * creates the result file and stores it on disk.
     *
     *
     * @throws NotSupported
     */
    public function execute(array $idList = [], array $options = []): mixed
    {
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
        } elseif (isset($this->options['template']) && (is_int($this->options['template']))) {
            //file was choosed in template list
            $templateTerm = $this->vocabularyRegistry->getVocabularyById(1); // vocabulary_publipost_template;
            /** @var EntityManager $em */
            $em = $this->doctrine->getManager();
            $repo = $em->getRepository($templateTerm::class);
            $vocabulary = $repo->find($this->options['template']);

            $this->currentTemplate = $vocabulary->getAbsolutePath();
            $this->currentTemplateFileName = $vocabulary->getFileName();
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
     *
     *@internal param bool $return
     *
     * @internal param bool $pdf
     */
    public function sendFile($fileName, $outputFileName = null, array $options = ['pdf' => false, 'return' => false]): string|Response
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
                $tmp = explode('.', (string) $outputFileName);
                $tmp[count($tmp) - 1] = 'pdf';
                $outputFileName = implode('.', $tmp);
            } else {
                $fp = $this->options['tempDir'].$fileName;
            }

            if (isset($options['return']) && $options['return']) {
                $file = new File($fp);

                return $file->move($file->getFileInfo()->getPath(), $outputFileName);
            }
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

        return '';
    }

    /**
     * @param string|PublipostTemplate $template $template
     * @param array                    $entities
     *
     * @return array
     */
    public function parseFile(string|\App\Entity\Term\PublipostTemplate $template, $entities, $getFile = false, $outputFileName = '', $getPdf = false): array
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
        $clsTinyButStrong = new clsTinyButStrong;
        $clsTinyButStrong->Plugin(TBS_INSTALL, 'clsOpenTBS');

        $clsTinyButStrong->setOption('noerr', true);

        //loading the template
        $clsTinyButStrong->LoadTemplate($template, OPENTBS_ALREADY_UTF8);

        $alias = $this->humanReadablePropertyAccessorFactory->getEntityAlias($this->targetClass);
        $entityName = $alias ?? 'entity';

        $lines = [];

        $uid = substr(md5(random_int(0, mt_getrandmax())), 0, 5);
        $fileName = $this->removeAccents($uid . '_' . ((empty($this->currentTemplateFileName) ? $template->getFileName() : $this->currentTemplateFileName)));

        // Mise en page spécifique pour feuille émargement pour une session
        $fileTest = stripos((string) $fileName, "EmargementSession");
        // Mise en page spécifique pour liste des participants pour une session
        $fileTest2 = stripos((string) $fileName, "ListeParticipants");
        // Mise en page spécifique pour fiche individuelle de formation pour un stagiaire
        $fileTest3 = stripos((string) $fileName, "FicheFormation");
        // Mise en page spécifique pour fiche individuelle de formation pour un formateur
        $fileTest4 = stripos((string) $fileName, "FicheFormateur");
        // Mise en page spécifique pour liste des convoqués pour une session
        $fileTest5 = stripos((string) $fileName, "ListeAcceptes");
        // Mise en page spécifique pour liste des personnes en liste d'attente pour une session
        $fileTest6 = stripos((string) $fileName, "ListeAttente");

        if ($fileTest === false) {
            if ($fileTest2 == false) {
                if ($fileTest3 == false) {
                    if ($fileTest4 == false) {
                        if ($fileTest5 == false) {
                            if ($fileTest6 == false) {
                                //tous les autres cas
                                $tabRes = $this->dataForTBS($entities);
                                $lines = $tabRes['lines'];
                                $entityName = $tabRes['entityName'];

                            } else {
                                // Cas de la liste des personnes en liste d'attente
                                // Cas de la liste des inscrits à une session acceptés
                                $data = $this->humanReadablePropertyAccessorFactory->getAccessor($entities[0]);

                                $lines[0]['dateDebut'] = $data->dateDebut;
                                $lines[0]['nom'] = $data->nom;

                                $inscriptions = $entities[0]->getInscriptions();
                                foreach ($inscriptions as $insc) {
                                    if ($insc->getInscriptionstatus() == "Liste d'attente") {
                                        $lines[0]['inscriptions'][] = ['nom' => $insc->getTrainee()->getLastname(), 'prenom' => $insc->getTrainee()->getFirstname(), 'nomComplet' => $insc->getTrainee()->getFullname(), 'mail' => $insc->getTrainee()->getEmail(), 'unite' => $insc->getTrainee()->getInstitution() ? $insc->getTrainee()->getInstitution()->getName() : '', 'service' => $insc->getTrainee()->getService(), 'corps' => $insc->getTrainee()->getCorps(), 'bap' => $insc->getTrainee()->getBap(), 'fonction' => $insc->getTrainee()->getFonction(), 'motivation' => $insc->getMotivation()];
                                    }
                                }

                                usort($lines[0]['inscriptions'], static fn($a, $b): int => strcasecmp((string) $a['nom'], (string) $b['nom']));
                                $entityName = 's';
                            }
                        } else {
                            // Cas de la liste des inscrits à une session acceptés
                            $data = $this->humanReadablePropertyAccessorFactory->getAccessor($entities[0]);

                            $lines[0]['dateDebut'] = $data->dateDebut;
                            $lines[0]['nom'] = $data->nom;

                            $inscriptions = $entities[0]->getInscriptions();
                            foreach ($inscriptions as $insc) {
                                if ($insc->getInscriptionstatus() == 'Accepté') {
                                    $lines[0]['inscriptions'][] = ['nom' => $insc->getTrainee()->getLastname(), 'prenom' => $insc->getTrainee()->getFirstname(), 'nomComplet' => $insc->getTrainee()->getFullname(), 'mail' => $insc->getTrainee()->getEmail(), 'unite' => $insc->getTrainee()->getInstitution() ? $insc->getTrainee()->getInstitution()->getName() : '', 'service' => $insc->getTrainee()->getService(), 'corps' => $insc->getTrainee()->getCorps(), 'bap' => $insc->getTrainee()->getBap(), 'fonction' => $insc->getTrainee()->getFonction()];
                                }
                            }

                            usort($lines[0]['inscriptions'], static fn($a, $b): int => strcasecmp((string) $a['nom'], (string) $b['nom']));
                            $entityName = 's';
                        }
                    } else {
                        $data = $this->humanReadablePropertyAccessorFactory->getAccessor($entities[0]);

                        $lines[0]['nom'] = $data->nom;
                        $lines[0]['prenom'] = $data->prenom;
                        $lines[0]['dateJour'] = date("d/m/Y");

                        // Tri des sessions par date de session
                        $sessions = $entities[0]->getSessions();

                        // Création d'un tableau intermédiaire pour comparer les dates et trier le tableau à l'aide de timestamp
                        $a = [];
                        foreach ($sessions as $k) {
                            //$date = date_create_from_format('d/m/Y', $k->dateDebut);
                            //$dateDeb = $date->format('Y-m-d');
                            $dateDeb = $k->getDatebegin()->format('Y-m-d');
                            $timestamp = strtotime((string) $dateDeb);
                            $a[$timestamp] = $k;
                        }

                        // Tri du tableau par date croissante
                        ksort($a);
                        // Tri du tableau par date décroissante
                        $b = array_reverse($a);
                        $newSessions = array_values($b);

                        foreach ($newSessions as $newSession) {
                            $lines[0]['sessions'][] = ['dateDebut' => $newSession->getDatebegin()->format('d/m/Y'), 'nombreHeures' => $newSession->getHournumber(), 'nom' => $newSession->getName(), 'domaine' => $newSession->getTraining()->getTheme()];
                        }

                        $entityName = 'formateur';
                    }
                } else {
                    $data = $this->humanReadablePropertyAccessorFactory->getAccessor($entities[0]);

                    $lines[0]['civilite'] = $data->civilite;
                    $lines[0]['nomComplet'] = $data->nomComplet;
                    $lines[0]['corps'] = $data->corps;
                    $lines[0]['dateJour'] = date("d/m/Y");
                    $lines[0]['date1insc'] = "";

                    // Tri des inscriptions par date de session
                    $inscriptions = $entities[0]->getInscriptions();

                    // Création d'un tableau intermédiaire pour comparer les dates et trier le tableau à l'aide de timestamp
                    $a = [];
                    foreach ($inscriptions as $inscription) {
                        //$date = date_create_from_format('d/m/Y', $k->session->dateDebut);
                        //$dateDeb = $date->format('Y-m-d');
                        $dateDeb = $inscription->getSession()->getDatebegin()->format('Y-m-d');
                        $timestamp = strtotime((string) $dateDeb);
                        // si formations ayant lieu le même jour, on modifie un peu le timestamp pour toutes les conserver
                        if (array_key_exists($timestamp, $a))
                            ++$timestamp;

                        $a[$timestamp] = $inscription;
                    }

                    // Tri du tableau par date croissante
                    ksort($a);
                    // Récupération de la date de la première formation suivie
                    foreach ($a as $tinsc) {
                        if (($tinsc->getPresencestatus() == 'Présent') || ($tinsc->getPresencestatus() == 'Partiel')) {
                            // on récupère la date de la première formation du stagiaire
                            $lines[0]['date1insc'] = $tinsc->getSession()->getDatebegin()->format('d/m/Y');
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
                            $tabDates = [];
                            foreach ($session->getDates() as $dateSes) {
                                // Conversion date de début de session
                                $dateDeb = strtotime(str_replace("/", "-", (string) $dateSes->getDatebegin()->format('d/m/Y')));
                                // création du tableau des dates suivant le nombre de jours à afficher
                                for ($j = 0; $j < $session->getDaynumber() + 1; ++$j) {
                                    $dateNew = date('d/m/Y', $dateDeb + $j * 86400);
                                    $tabDates[] = ["dateDeb" => $dateNew, "nbHeuresMatin" => $dateSes->getHournumbermorn(), "nbHeuresApr" => $dateSes->getHournumberafter()];
                                }
                            }

                            // calcul du nombre d'heures de présence effective
                            // On initialise le nombre d'heures de présence
                            $nbHeuresPresence = 0;
                            // Pour chaque presence, on compare avec le tableau des dates et on calcule le nombre d'heures
                            foreach ($insc->getPresences() as $pres) {
                                foreach ($tabDates as $tabDate) {
                                    if ($pres->getDatebegin()->format('d/m/Y') == $tabDate["dateDeb"]) {
                                        if ($pres->getMorning() == "Présent") {
                                            $nbHeuresPresence += $tabDate["nbHeuresMatin"];
                                        }

                                        if ($pres->getAfternoon() == "Présent") {
                                            $nbHeuresPresence += $tabDate["nbHeuresApr"];
                                        }

                                        break;
                                    }
                                }
                            }

                            $typAc = $insc->getActiontype() == null ? "" : $insc->getActiontype()->getName();


                            $lines[0]['inscriptions'][] = ['dateDebut' => $insc->getSession()->getDatebegin()->format('d/m/Y'), 'nombreHeures' => $insc->getSession()->getHournumber(), 'nombreHeuresPres' => $nbHeuresPresence, 'nom' => $insc->getSession()->getName(), 'domaine' => $insc->getSession()->getTraining()->getTheme(), "formateurs" => $formateurs, "type" => $insc->getSession()->getTraining()->getCategory(), "typeAction" => $typAc];
                        }
                    }

                    $entityName = 'stagiaire';
                }
            } else {
                // Cas de la liste des participants à une session
                $data = $this->humanReadablePropertyAccessorFactory->getAccessor($entities[0]);

                $lines[0]['dateDebut'] = $data->dateDebut;
                $lines[0]['nom'] = $data->nom;

                $inscriptions = $entities[0]->getInscriptions();
                foreach ($inscriptions as $insc) {
                    if ($insc->getInscriptionstatus() == 'Convoqué') {
                        $lines[0]['inscriptions'][] = ['nom' => $insc->getTrainee()->getLastname(), 'prenom' => $insc->getTrainee()->getFirstname(), 'nomComplet' => $insc->getTrainee()->getFullname(), 'mail' => $insc->getTrainee()->getEmail(), 'unite' => $insc->getTrainee()->getInstitution() ? $insc->getTrainee()->getInstitution()->getName() : '', 'service' => $insc->getTrainee()->getService(), 'corps' => $insc->getTrainee()->getCorps(), 'bap' => $insc->getTrainee()->getBap(), 'fonction' => $insc->getTrainee()->getFonction()];
                    }
                }

                if (isset($lines[0]['inscriptions'])) {
                    usort($lines[0]['inscriptions'], static fn($a, $b): int => strcasecmp((string) $a['nom'], (string) $b['nom']));
                }

                $entityName = 's';
            }
        } else {
            // Cas de la feuille d'émargement pour une session
            $data = $this->humanReadablePropertyAccessorFactory->getAccessor($entities[0]);

            function cmp($a, $b)
            {
                $dateDebA = strtotime(str_replace("/", "-", (string) $a->dateDebut));
                $dateDebB = strtotime(str_replace("/", "-", (string) $b->dateDebut));
                if ($dateDebA === $dateDebB) {
                    return 0;
                }

                return ($dateDebA < $dateDebA) ? -1 : 1;
            }

            $Dates = $entities[0]->getDates();
            $inscriptions = $entities[0]->getInscriptions();
            $formateurs = $entities[0]->getTrainers();

            $i = 0;
            foreach ($Dates as $date) {
                // Test sur le nombre de jours à afficher
                $dateDeb = strtotime(str_replace("/", "-", (string) $date->getDatebegin()->format('d/m/Y')));
                $dateFin = strtotime(str_replace("/", "-", (string) $date->getDateend()->format('d/m/Y')));
                $diff = abs($dateFin - $dateDeb) / 86400;

                for ($j = 0; $j < $diff + 1; ++$j) {
                    $lines[$i]['dateDebut'] = date('d/m/Y', $dateDeb + $j * 86400);
                    $lines[$i]['dateFin'] = date('d/m/Y', $dateDeb + $j * 86400);
                    $lines[$i]['horairesMatin'] = $date->getScheduleMorn();
                    $lines[$i]['horairesAprem'] = $date->getScheduleAfter();
                    $lines[$i]['lieu'] = $date->getPlace();
                    $lines[$i]['nom'] = $data->nom;

                    foreach ($formateurs as $formateur) {
                        $lines[$i]['formateurs'][] = ['nom' => $formateur->getLastname(), 'prenom' => $formateur->getFirstname()];
                    }

                    foreach ($inscriptions as $inscription) {
                        if ($inscription->getInscriptionstatus() == 'Convoqué') {
                            $lines[$i]['inscriptions'][] = ['nom' => $inscription->getTrainee()->getLastname(), 'prenom' => $inscription->getTrainee()->getFirstname(), 'nomComplet' => $inscription->getTrainee()->getFullname(), 'mail' => $inscription->getTrainee()->getEmail(), 'unite' => $inscription->getTrainee()->getInstitution() ? $inscription->getTrainee()->getInstitution()->getName() : '', 'service' => $inscription->getTrainee()->getService()];
                        }
                    }

                    if ((isset($lines[$i]['inscriptions'])) && ($lines[$i]['inscriptions'] !== null)) {
                        usort($lines[$i]['inscriptions'], static fn($a, $b): int => strcasecmp((string) $a['nom'], (string) $b['nom']));
                    }

                    ++$i;
                }
            }

            $entityName = 's';
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

/*            if (($fileTest === false) && ($fileTest2 === false) && ($fileTest3 === false) && ($fileTest4 === false) && ($fileTest5 === false) && ($fileTest6 === false)) {
                $TBS->MergeField('global', current($lines)->toArray());
            }
            else {*/
                $clsTinyButStrong->MergeField('global', current($lines));
//            }
        }

        reset($lines);

        $clsTinyButStrong->MergeBlock($entityName, $lines);

        $error = ob_get_flush();

        if ($error) {
            return ['error' => $error];
        }

        $clsTinyButStrong->Show(OPENTBS_FILE, $this->options['tempDir'] . $fileName);
        $clsTinyButStrong->_PlugIns[OPENTBS_PLUGIN]->Close();

        //do we want the file or just infos about it ?
        if ($getFile) {
            return $this->sendFile($fileName, $outputFileName, ['pdf' => $getPdf, 'return' => true]);
        }
        // file can then be taken using senFile.
        return ['fileUrl' => $fileName];
    }

    /**
     * @param array $options
     *
     * @return array{templateList: array<int, array{id: int, name: string, fileName: string}>}
     */
    public function getModalConfig($options = []): array
    {
        $templateTerm = $this->vocabularyRegistry->getVocabularyById(1); // vocabulary_publipost_template
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        $repo = $em->getRepository($templateTerm::class);
        /** @var PublipostTemplate[] $templates */
        $templates = $repo->findBy(['organization' => $this->security->getUser()->getOrganization()]);

        $files = [];
        foreach ($templates as $template) {
            $templateEntity = $template->getEntity();
            $ancestor = class_parents($this->targetClass);
            //file is added if its associated entity is an ancestor for current target class
            if ($templateEntity === $this->targetClass || in_array($templateEntity, $ancestor, true)) {
                $files[] = ['id' => $template->getId(), 'name' => $template->getName(), 'fileName' => $template->getFileName()];
            }
        }

        return ['templateList' => $files];
    }

    /**
     * @param $fileName
     * @param null $outputFileName
     *
     * @return string pdf file name, or null if error
     */
    public function toPdf($fileName, $outputFileName = null): string
    {
        if (empty($outputFileName)) {
            $outputFileName = $fileName;
        }

        //renaming output filename (for end user)
        $info = pathinfo((string) $outputFileName);
        $outputFileName = $info['filename'].'.pdf';

        // prepare the process
        $unoconvBin = $this->parameterBag->get('unoconv_bin');
        $args = [$unoconvBin, '--output='.$this->options['tempDir'].$outputFileName, $this->options['tempDir'].$fileName];
        //$process = new Process(implode(' ', $args));
        $process = new Process($args);

        // run
        try {
            $process->run();
        } catch (RuntimeException) {
            // unoconv somtimes returns 8 (SIGFPE) error code but still produces a correct output,
            // so we can ignore it.
//            if ($exception->getCode() !== 8) {
//                throw $exception;
//            }
        }

        // Suppression du test car renvoie les erreurs et les warnings (deprecated)
/*        if (!empty($process->getErrorOutput())) {
            throw new RuntimeException('The PDF file has not been generated : '.$process->getErrorOutput());
        }*/

        return $outputFileName;
    }

    /**
     * @param $template
     * @param $entities
     *
     *
     * @throws \Throwable
     */
    private function initializeOpenTbs($template, $entities): array
    {
//        $TBS = $this->container->get('opentbs');
        $clsTinyButStrong = new clsTinyButStrong;
        $clsTinyButStrong->setOption('noerr', true);
        $clsTinyButStrong->LoadTemplate($template, OPENTBS_ALREADY_UTF8);

        $classCatalog = $this->humanReadablePropertyAccessorFactory->getTermCatalog(current($entities));

        return [$clsTinyButStrong, $classCatalog];
    }

    /**
     * @param $entities
     *
     * @return array<int|string, mixed>
     *
     * @throws \Throwable
     */
    private function getTemplateLines($entities): array
    {
        $lines = [];
        foreach ($entities as $entity) {
//            if ($this->securityContext->isGranted('VIEW', $entity)) {
                $lines[$entity->getId()] = $this->humanReadablePropertyAccessorFactory->getAccessor($entity);
//            }
        }

        return $lines;
    }

    /**
     * Add global variables with publipost shorcuts.
     *
     *
     *
     * @throws \Throwable
     */
    private function computeShorcutsAndMerge(clsTinyButStrong $clsTinyButStrong, array $entities, string $classCatalog): int
    {
        $i = 0;
        if (isset($classCatalog['shorcuts'])) {
            $aliases = $classCatalog['shorcuts'];

            $propertyAccessor = new PropertyAccessor();
            foreach ($aliases as $alias => $params) {
                $arrayValues = [];
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
                            $accessor = $this->parameterBag->get('sygefor_core.human_readable_property_accessor_factory')->getAccessor($item);
                            if (is_object($item) && method_exists($item, 'getId')) {
                                $id = $item->getId();
                                $arrayValues[$id] = $accessor;
                            } else {
                                $arrayValues[] = $accessor;
                            }
                        }
                    } catch (\Exception) {
                    }

                    ++$i;
                }

                if (isset($params['sort']) && $params['sort']) {
                    usort($arrayValues, static fn($a, $b): bool => $propertyAccessor->getValue($a, $params['sort']) > $propertyAccessor->getValue($b, $params['sort']));
                }

                $clsTinyButStrong->MergeBlock($alias, $arrayValues);
                ++$i;
            }
        }

        return $i;
    }

    /**
     * @param \clsTinyButStrong        $TBS
     *
     * @return mixed|string
     */
    private function generateFinalFile($TBS, string|\App\Entity\Term\PublipostTemplate $template): mixed
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
    private function removeAccents(string $str, $charset = 'utf-8'): mixed
    {
        //converting to html elements
        $str = htmlentities($str, ENT_NOQUOTES, $charset);

        //keeping only first char after '&', so that &eacute becomes e for example
        $str = preg_replace('#&([A-Za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&[^;]+;#', '', $str); // removing not recognized chars

        $str = str_replace(' ', '_', $str);

        return str_replace("'", '-', $str);
    }

    /**
     * @param $entities
     *
     * @return never[]|array{entityName: string, lines: array<int, array{centre.nom: mixed, dateDebut: mixed, domaine: mixed, inscriptions?: array<int, array{stagiaire.nom: mixed, stagiaire.prenom: mixed, stagiaire.nomComplet: mixed, stagiaire.mail: mixed, stagiaire.unite: mixed, stagiaire.service: mixed, stagaire.corps: mixed, stagiaire.bap: mixed, stagiaire.fonction: mixed, statutInscription: mixed, statutPresence: mixed, refus: mixed, motivation: mixed}>&mixed[], listeFormateurs: mixed, nom: mixed}>}|array{entityName: string, lines: array<int, array{centreNom: mixed, dateDebut: mixed, dates?: array<int, array{dateDebut: mixed, dateFin: mixed, horairesMatin: mixed, horairesAprem: mixed, nbHeuresMatin: mixed, nbHeuresApr: mixed, lieu: mixed}>&mixed[], domaine: mixed, listeFormateurs: mixed, motivation: mixed, nom: mixed, refus: mixed, sessionCommentaires: mixed, sessionDescription: mixed, stagiaireBap: mixed, stagiaireCivilite: mixed, stagiaireCorps: mixed, stagiaireFonction: mixed, stagiaireMail: mixed, stagiaireNom: mixed, stagiaireNomComplet: mixed, stagiairePrenom: mixed, stagiaireService: mixed, stagiaireUnite: mixed, statutInscription?: mixed, statutPresence?: mixed, typeaction: mixed}>}
     */
    private function dataForTBS($entities): array
    {
        $dataRes = [];
        $lines = [];
        if ($entities[0]::class == \App\Entity\Back\Inscription::class) {
            $dataRes['entityName'] = 'inscription';
            // Construction des lignes
            $i = 0;
            foreach ($entities as $entity) {

                $lines[$i]['typeaction'] = $entity->getActiontype();
                $lines[$i]['motivation'] = $entity->getMotivation();
                $lines[$i]['refus'] = $entity->getRefuse();

                $lines[$i]['dateDebut'] = $entity->getSession()->getDatebegin();
                $lines[$i]['centreNom'] = $entity->getSession()->getTraining()->getOrganization()->getName();
                $lines[$i]['domaine'] = $entity->getSession()->getTraining()->getTheme()->getName();
                $lines[$i]['nom'] = $entity->getSession()->getName();
                $lines[$i]['sessionDescription'] = $entity->getSession()->getTraining()->getDescription();
                $lines[$i]['sessionCommentaires'] = $entity->getSession()->getComments();
                $lines[$i]['listeFormateurs'] = $entity->getSession()->getTrainersListString();

                $lines[$i]['stagiaireNom'] = $entity->getTrainee()->getLastname();
                $lines[$i]['stagiairePrenom'] = $entity->getTrainee()->getFirstname();
                $lines[$i]['stagiaireNomComplet'] = $entity->getTrainee()->getFullName();
                $lines[$i]['stagiaireMail'] = $entity->getTrainee()->getEmail();
                $lines[$i]['stagiaireUnite'] = $entity->getTrainee()->getInstitution() ? $entity->getTrainee()->getInstitution()->getName() : '';
                $lines[$i]['stagiaireCivilite'] = $entity->getTrainee()->getTitle()->getName();
                $lines[$i]['stagiaireService'] = $entity->getTrainee()->getService();
                $lines[$i]['stagiaireCorps'] = $entity->getTrainee()->getCorps();
                $lines[$i]['stagiaireBap'] = $entity->getTrainee()->getBap();
                $lines[$i]['stagiaireFonction'] = $entity->getTrainee()->getFonction();

                if($entity->getInscriptionStatus() != null)
                    $lines[$i]['statutInscription'] = $entity->getInscriptionStatus()->getName();

                if($entity->getPresenceStatus() != null)
                    $lines[$i]['statutPresence'] = $entity->getPresenceStatus()->getName();

                $datesSess = $entity->getSession()->getDates();
                foreach ($datesSess as $dateSess) {
                    $lines[$i]['dates'][] = ['dateDebut' => $dateSess->getDatebegin()->format('d/m/Y'), 'dateFin' => $dateSess->getDateend()->format('d/m/Y'), 'horairesMatin' =>  $dateSess->getSchedulemorn(), 'horairesAprem' =>  $dateSess->getScheduleafter(), 'nbHeuresMatin' =>  $dateSess->getHournumbermorn(), 'nbHeuresApr' =>  $dateSess->getHournumberafter(), 'lieu' =>  $dateSess->getPlace()];
                }

                ++$i;
            }

            $dataRes['lines'] = $lines;
        } elseif ($entities[0]::class == \App\Entity\Back\Session::class) {
            $dataRes['entityName'] = 's';
            // Construction des lignes
            $i = 0;
            foreach ($entities as $entity) {
                $data = $this->humanReadablePropertyAccessorFactory->getAccessor($entity);

                $lines[$i]['dateDebut'] = $data->dateDebut;
                $lines[$i]['nom'] = $data->nom;
                $lines[$i]['centre.nom'] = $entity->getOrganization()->getName();
                $lines[$i]['domaine'] = $entity->getTraining()->getTheme()->getName();
                $lines[$i]['listeFormateurs'] = $entity->getTrainersListString();

                $inscriptions = $entities[0]->getInscriptions();
                foreach ($inscriptions as $inscription) {
                    $lines[$i]['inscriptions'][] = ['stagiaire.nom' => $inscription->getTrainee()->getLastname(), 'stagiaire.prenom' => $inscription->getTrainee()->getFirstname(), 'stagiaire.nomComplet' => $inscription->getTrainee()->getFullname(), 'stagiaire.mail' => $inscription->getTrainee()->getEmail(), 'stagiaire.unite' => $inscription->getTrainee()->getInstitution() ? $inscription->getTrainee()->getInstitution()->getName() : '', 'stagiaire.service' => $inscription->getTrainee()->getService(), 'stagaire.corps' => $inscription->getTrainee()->getCorps(), 'stagiaire.bap' => $inscription->getTrainee()->getBap(), 'stagiaire.fonction' => $inscription->getTrainee()->getFonction(), 'statutInscription' => $inscription->getInscriptionStatus()->getName(), 'statutPresence' => $inscription->getPresenceStatus()->getName(), 'refus' => $inscription->getRefuse(), 'motivation' => $inscription->getMotivation()];
                }

                usort($lines[0]['inscriptions'], static fn($a, $b): int => strcasecmp((string) $a['nom'], (string) $b['nom']));

                ++$i;
            }

            $dataRes['lines'] = $lines;
        }

        return $dataRes;
    }
}

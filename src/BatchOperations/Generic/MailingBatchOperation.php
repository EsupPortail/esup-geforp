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
use App\Utils\HumanReadable\CustomOpenTBS;
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
    public function __construct(
        /**
     * @var Security security
     */

    private readonly Security $security, protected ParameterBagInterface $parameterBag, protected VocabularyRegistry $vocabularyRegistry, protected HumanReadablePropertyAccessorFactory $humanReadablePropertyAccessorFactory)
    {
        parent::__construct();
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
    public function sendFile(string $fileName, ?string $outputFileName = null, array $options = ['pdf' => false, 'return' => false]): string|Response|File
    {
        $fullPath = $this->options['tempDir'] . $fileName;

        if (!file_exists($fullPath)) {
            return '';
        }

        // Check directory to avoid arbitrary file access
        if (realpath(dirname($fullPath)) !== realpath($this->options['tempDir'])) {
            return new Response('Accès non autorisé : ' . dirname($fullPath), 403);
        }

        // Determine final path and output name
        $finalPath = $fullPath;
        $outputFileName ??= $fileName;

        // Convert to PDF if required
        if (!empty($options['pdf'])) {
            $pdfName = $this->toPdf($fileName);
            $finalPath = $this->options['tempDir'] . $pdfName;

            // Force output file extension to .pdf
            $outputFileName = preg_replace('/\.[^.]+$/', '.pdf', $outputFileName);
        }

        // Return the file for internal use (ex: attachment)
        if (!empty($options['return'])) {
            return new File($finalPath, false); // false = don't check existence again
        }

        // Otherwise, send it as a response to browser
        $mimeType = mime_content_type($finalPath);
        $response = new Response();

        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-Type', $mimeType);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $outputFileName . '"');
        $response->headers->set('Content-Length', (string) filesize($finalPath));

        $response->setContent(file_get_contents($finalPath));
        $response->send();

        // Clean up
        unlink($finalPath);

        return $response;
    }

    /**
     * @param string|PublipostTemplate $template $template
     * @param array $entities
     *
     * @return array
     */
    public function parseFile(string|\App\Entity\Term\PublipostTemplate $template, array $entities, $getFile = false, $outputFileName = '', $getPdf = false): array
    {
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

        if ($template instanceof \App\Entity\Term\PublipostTemplate) {
            $baseFileName = $template->getFileName();
        } else {
            // $template est un chemin de fichier
            $baseFileName = basename($template);
        }

        $fileName = $this->removeAccents($uid . '_' . ($this->currentTemplateFileName ?: $baseFileName));

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
            if (!$fileTest2) {
                if (!$fileTest3) {
                    if (!$fileTest4) {
                        if (!$fileTest5) {
                            if (!$fileTest6) {
                                //tous les autres cas
                                $tabRes = $this->dataForTBS($entities);
                                $lines = $tabRes['lines'] ?? [];
                                $entityName = ($tabRes['entityName'][0] ?? '');

                            } else {
                                // Cas de la liste des personnes en liste d'attente
                                // Cas de la liste des inscrits à une session acceptés
                                $data = $this->humanReadablePropertyAccessorFactory->getAccessor($entities[0]);

                                $lines[0]['dateDebut'] = $data->dateDebut->format('Y-m-d H:i:s');
                                $lines[0]['nom'] = $data->nom;

                                $inscriptions = $entities[0]->getInscriptions();
                                $lines[0]['inscriptions'] = [];
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
                            $lines[0]['inscriptions'] = [];
                            foreach ($inscriptions as $insc) {
                                if ($insc->getInscriptionstatus() == 'Accepté') {
                                    $lines[0]['inscriptions'][] = array('nom' => $insc->getTrainee()->getLastname(), 'prenom' => $insc->getTrainee()->getFirstname(), 'nomComplet' => $insc->getTrainee()->getFullname(), 'mail' => $insc->getTrainee()->getEmail(), 'unite' => $insc->getTrainee()->getInstitution() ? $insc->getTrainee()->getInstitution()->getName() : '', 'service' => $insc->getTrainee()->getService(), 'corps' => $insc->getTrainee()->getCorps(), 'bap' => $insc->getTrainee()->getBap(), 'fonction' => $insc->getTrainee()->getFonction(), 'motivation' => $insc->getMotivation());
                                }
                            }

                            usort($lines[0]['inscriptions'], static fn($a, $b): int => strcasecmp((string) $a['nom'], (string) $b['nom']));
                            $entityName = 's';
                        }
                    } else {
                        $data = $this->humanReadablePropertyAccessorFactory->getAccessor($entities[0]);

                        $lines[0]['nom'] = $data->nom;
                        $lines[0]['prenom'] = $data->prenom;
                        $lines[0]['dateJour'] = date("d/m/Y H:M");

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
                    $lines[0]['dateJour'] = date("d/m/y");
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
                                $dateDeb = $dateSes->getDateBegin();
                                $dateNewS = $dateDeb->format('d/m/Y');
                                $tab = explode('/', $dateNewS);
                                $dateNew = new \DateTime();
                                $dateNew->setDate($tab[2], $tab[1], $tab[0]);

                                $nbJoursDate2 = date_diff($dateSes->getDateEnd(), $dateSes->getDateBegin());
                                $nbJoursDate = $nbJoursDate2->format('%a');
                                // création du tableau des dates suivant le nombre de jours à afficher
                                for ($j = 0; $j < $nbJoursDate + 1; $j++) {
                                    $tabDates[] = array("dateDeb" => $dateNew->format('d/m/Y'), "nbHeuresMatin" => $dateSes->getHourNumberMorn(), "nbHeuresApr" => $dateSes->getHourNumberAfter());
                                    $dateNew->modify('+ 1 days');
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

                            $lines[0]['inscriptions'] = [];
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
                $lines[0]['inscriptions'] = [];
                foreach ($inscriptions as $insc) {
                    if ($insc->getInscriptionstatus() == 'Convoqué') {
                        $lines[0]['inscriptions'][] = array('nom' => $insc->getTrainee()->getLastname(), 'prenom' => $insc->getTrainee()->getFirstname(), 'nomComplet' => $insc->getTrainee()->getFullname(), 'mail' => $insc->getTrainee()->getEmail(), 'unite' => $insc->getTrainee()->getInstitution() ? $insc->getTrainee()->getInstitution()->getName() : '', 'service' => $insc->getTrainee()->getService(), 'corps' => $insc->getTrainee()->getCorps(), 'bap' => $insc->getTrainee()->getBap(), 'fonction' => $insc->getTrainee()->getFonction(), 'motivation' => $insc->getMotivation());
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

            //dump(get_class($entities[0]));
            $session = $entities[0];
            $Dates = $session->getDates();
            $inscriptions = $session->getInscriptions();
            $formateurs = $session->getTrainers();

            $i = 0;
            $dateDebuts = [];
            $dateFins = [];
            foreach ($Dates as $date) {
                // Test sur le nombre de jours à afficher
                $diffJours = (int) floor(($date->getDateend()->getTimestamp() - $date->getDatebegin()->getTimestamp()) / 86400);

                for ($j = 0; $j < $diffJours + 1; ++$j) {
                    $timestampJour = $date->getDatebegin()->getTimestamp() + $j * 86400;
                    $lines[$i]['dateDebut'] = date('d/m/Y H:m', $timestampJour);
                    $lines[$i]['dateFin'] = date('d/m/Y', $timestampJour);
                    $lines[$i]['horairesMatin'] = $date->getScheduleMorn();
                    $lines[$i]['horairesAprem'] = $date->getScheduleAfter();
                    $lines[$i]['lieu'] = $date->getPlace();
                    $lines[$i]['nom'] = $data->nom;
                    $lines[$i]['commentaires'] = $data->commentaires;

                    $lines[$i]['formateur'] = [];
                    foreach ($formateurs as $formateur) {
                        $lines[$i]['formateur'][] = ['nom' => $formateur->getLastname(), 'prenom' => $formateur->getFirstname()];
                    }

                    $lines[$i]['inscriptions'] = [];
                    foreach ($inscriptions as $inscription) {
                        if ($inscription->getInscriptionstatus() == 'Convoqué') {
                            $trainee = $inscription->getTrainee();
                            $lines[$i]['inscriptions'][] = ['nom' => $trainee->getLastname(), 'prenom' => $trainee->getFirstname(), 'nomComplet' => $trainee->getFullname(), 'mail' => $trainee->getEmail(), 'unite' => $trainee->getInstitution() ? $trainee->getInstitution()->getName() : '', 'service' => $trainee->getService()];
                        }
                    }

                    if ((isset($lines[$i]['inscriptions']))) {
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
                $clsTinyButStrong->MergeField('global', current($lines));
        }
        reset($lines);

     //$clsTinyButStrong->MergeBlock('inscriptions', $lines['inscriptions']);

        $clsTinyButStrong->MergeBlock($entityName, $lines);
        $error = ob_get_flush();
        if ($error) {
            return ['error' => $error];
        }

        if (!$fileName) {
            return ['error' => 'Nom de fichier invalide (null ou vide).'];
        }

        $clsTinyButStrong->Show(OPENTBS_FILE, $this->options['tempDir'] . $fileName);


        //do we want the file or just infos about it ?
        if ($getFile) {
            return ['file' => $this->sendFile($fileName, $outputFileName, ['pdf' => $getPdf, 'return' => true])];
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
        }

        return $outputFileName;
    }

    private function getLinesForEmargementSession(array $sessions): array
    {
        $lines = [];
        foreach ($sessions as $session) {
            foreach ($session->getInscriptionsActives(true) as $inscription) {
                $stagiaire = $inscription->getStagiaire();
                $lines[] = [
                    'nom' => $stagiaire->getNom(),
                    'prenom' => $stagiaire->getPrenom(),
                    'entreprise' => $stagiaire->getEntreprise() ?? '',
                    'sessions_id' => $session->getId(),
                    'session_dates' => $session->getDatesAsText(),
                    'session' => $session,
                    'stagiaire' => $stagiaire,
                ];
            }
        }
        return $lines;
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
            $aliases[] = $classCatalog['shorcuts'];

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
                        if ($val instanceof ArrayCollection) {
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
     *
     * @return string|array
     */
    private function removeAccents(string $str): string|array
    {//converting to html elements
        $str = htmlentities($str, ENT_NOQUOTES, 'utf-8');

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
    private function dataForTBS(array $entities = []): array
    {
        $dataRes = [];
        $lines = [];

        if (empty($entities)) {
            return ['lines' => [], 'entityName' => []];
        }

        $firstEntity = $entities[0];
        $class = get_class($firstEntity);

        if ($class === \App\Entity\Back\Inscription::class) {
            $dataRes['entityName'] = ['inscription'];
            foreach ($entities as $i => $entity) {
                $session = $entity->getSession();
                $training = $session->getTraining();
                $organization = $training->getOrganization();
                $theme = $training->getTheme();
                $trainee = $entity->getTrainee();

                $lines[$i] = [
                    'session' => [
                        'formation' => [
                            'nom' => $training->getName(),
                        ],
                        ],
                    'stagiaire' => [
                            'nom' => $trainee->getLastName(),
                    ],
                    'typeAction' => $entity->getActiontype(),
                    'motivation' => $entity->getMotivation(),
                    'refus' => $entity->getRefuse(),
                    'datesString' => $session->getDatesString(),
                    'date.debut' => $session->getDatebegin()?->format('d/m/Y'),
                    'session.nom' => $session->getName(),
                    'session.description' => $training->getDescription(),
                    'session.commentaires' => $session->getComments(),
                    'listeFormateurs' => $session->getTrainersListString(),

                    'centre.nom' => $organization?->getName(),
                    'domaine' => $theme?->getName(),

                    'stagiaire.nom' => $trainee?->getLastname(),
                    'stagiaire.prenom' => $trainee?->getFirstname(),
                    'stagiaire.nomComplet' => $trainee?->getFullName(),
                    'stagiaire.mail' => $trainee?->getEmail(),
                    'stagiaire.unite' => $trainee?->getInstitution()?->getName() ?? '',
                    'stagiaire.service' => $trainee?->getService(),
                    'stagiaire.corps' => $trainee?->getCorps(),
                    'stagiaire.bap' => $trainee?->getBap(),
                    'stagiaire.fonction' => $trainee?->getFonction(),
                    'stagiaire.civilite' => $trainee?->getTitle()?->getName() ?? '',

                    'statutInscription' => $entity->getInscriptionStatus()?->getName() ?? '',
                    'statutPresence' => $entity->getPresenceStatus()?->getName() ?? '',
                ];

                foreach ($session->getDates() as $dateSess) {
                    $lines[$i]['dates'][] = [
                        'dateDebut' => $dateSess->getDatebegin()?->format('d/m/Y') ?? '',

                        'dateFin' => $dateSess->getDateend()?->format('d/m/Y') ?? '',
                        'horairesMatin' => $dateSess->getSchedulemorn(),
                        'horairesAprem' => $dateSess->getScheduleafter(),
                        'nbHeuresMatin' => $dateSess->getHournumbermorn(),
                        'nbHeuresAprem' => $dateSess->getHournumberafter(),
                        'lieu' => $dateSess->getPlace(),
                    ];
                }
            }
        } elseif ($class === \App\Entity\Back\Trainer::class) {
            $dataRes['entityName'] = ['formation'];

            foreach ($entities as $i => $entity) {
                $lines[$i] = [
                    'civilite' => $entity->getTitle() ?? '',
                    'formateur.nom' => $entity->getLastname(),
                    'formateur.prenom' => $entity->getFirstname(),
                    'formateur.nomComplet' => $entity->getFullname(),
                    'formateur.email' => $entity->getEmail(),
                    'formateur.adresse' => $entity->getAddress(),
                    'formateur.codePostal' => $entity->getZip(),
                    'formateur.ville' => $entity->getCity(),
                    'formateur.fax' => $entity->getFaxnumber(),
                    'formateur.site' => $entity->getWebsite(),
                    'formateur.etablissement' => $entity->getInstitution(),
                    'formateur.service' => $entity->getService(),
                    'formateur.statut' => $entity->getStatus(),
                ];
            }

            $dataRes['lines'] = $lines;

        } elseif ($class === \App\Entity\Back\Session::class) {
            $dataRes['entityName'] = ['session'];
            foreach ($entities as $i => $entity) {
                $training = $entity->getTraining();
                $organization = $entity->getOrganization();
                $theme = $training->getTheme();
                $lines[$i] = [
                    'datesString' => $entity->getDatesString(),
                    'dateDebut' => $entity->getDatebegin()?->format('d/m/Y H:M'),
                    'name' => $entity->getName(),
                    'centre.nom' => $organization?->getName(),
                    'domaine' => $theme?->getName(),
                    'listeFormateurs' => $entity->getTrainersListString(),
                    'inscriptions' => [],
                ];

                foreach ($entity->getDates() as $dateSess) {
                    $lines[$i]['dates'][] = [
                        'dateDebut' => $dateSess->getDatebegin()?->format('d/m/Y H:M') ?? '',
                        'dateFin' => $dateSess->getDateend()?->format('d/m/Y H:M') ?? '',
                        'horairesMatin' => $dateSess->getSchedulemorn(),
                        'horairesAprem' => $dateSess->getScheduleafter(),
                        'nbHeuresMatin' => $dateSess->getHournumbermorn(),
                        'nbHeuresAprem' => $dateSess->getHournumberafter(),
                        'lieu' => $dateSess->getPlace(),
                    ];
                }

                foreach ($entity->getInscriptions() as $inscription) {
                    $trainee = $inscription->getTrainee();
                    $lines[$i]['inscriptions'][] = [
                        'stagiaire.nom' => $trainee?->getLastname(),
                        'stagiaire.prenom' => $trainee?->getFirstname(),
                        'fullname' => $trainee?->getFullName(),
                        'stagiaire.mail' => $trainee?->getEmail(),
                        'stagiaire.unite' => $trainee?->getInstitution()?->getName() ?? '',
                        'stagiaire.service' => $trainee?->getService(),
                        'stagiaire.corps' => $trainee?->getCorps(),
                        'stagiaire.bap' => $trainee?->getBap(),
                        'stagiaire.fonction' => $trainee?->getFonction(),
                        'stagiaire.civilite' => $trainee?->getTitle()?->getName() ?? '',

                        'statutInscription' => $inscription->getInscriptionStatus()?->getName() ?? '',
                        'statutPresence' => $inscription->getPresenceStatus()?->getName() ?? '',
                        'motivation' => $inscription->getMotivation(),
                        'refus' => $inscription->getRefuse(),
                    ];
                }

                usort($lines[$i]['inscriptions'], static fn ($a, $b): int => strcasecmp($a['stagiaire.nom'], $b['stagiaire.nom']));
            }

            $dataRes['lines'] = $lines;
        } else {
            $dataRes['lines'] = [];
            $dataRes['entityName'] = [];
        }

        return $dataRes;
    }


}

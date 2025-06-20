<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 07/04/14
 * Time: 11:15.
 */

namespace App\BatchOperations;

use App\BatchOperations\Generic\CSVBatchOperation;
use App\BatchOperations\Generic\EmailingBatchOperation;
use App\BatchOperations\Generic\MailingBatchOperation;
use App\BatchOperations\Generic\PDFBatchOperation;
use App\BatchOperations\Inscription\InscriptionStatusChangeBatchOperation;
use App\BatchOperations\SemesteredTraining\SemesteredTrainingCSVBatchOperation;
use App\Entity\Back\Internship;
use App\Entity\Back\Session;
use App\Entity\Core\AbstractTraining;
use App\Entity\Term\Trainingcategory;
use App\Kernel;
use App\Model\SemesteredTraining;
use App\Vocabulary\VocabularyRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Snappy\Pdf;
use phpDocumentor\Reflection\Types\Parent_;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Utils\HumanReadable\HumanReadablePropertyAccessorFactory;
use App\BatchOperations\Session\SessionRegistrationChangeBatchOperation;
use Twig\Environment;

/**
 * Class BatchOperationRegistry.
 */
final class BatchOperationRegistry
{
    private array $operations = [];


    public function __construct( Security $security, ManagerRegistry $managerRegistry, ParameterBagInterface $parameterBag, VocabularyRegistry $vocabularyRegistry, MailerInterface $mailer, HumanReadablePropertyAccessorFactory $humanReadablePropertyAccessorFactory, Pdf $pdf, Environment $twigEnvironment)
    {
        // Construction de la liste des batch operations 'en dur'
        $i=0;
        $conf = $parameterBag->get('batch');
        // Recuperation parametres emails et publipostage
        $confMail = $conf['mailing'];

        // operation batch : envoi email
        $humanReadablePropertyAccessorFactory->setTermCatalog($confMail);
        $emailingBatchOperation = new EmailingBatchOperation($security, $parameterBag, $vocabularyRegistry, $mailer, $humanReadablePropertyAccessorFactory);
        $emailingBatchOperation->setDoctrine($managerRegistry);
        $this->addBatchOperation($emailingBatchOperation, $i);
        ++$i;

        // operation batch : publipostage session
        $mailingBatch = new MailingBatchOperation($security, $parameterBag, $vocabularyRegistry, $humanReadablePropertyAccessorFactory, $managerRegistry);
        $mailingBatch->setDoctrine($managerRegistry);
        $mailingBatch->setTargetClass(\App\Entity\Back\Session::class);
        $mailingBatch->setOptions($confMail['session']);
        $this->addBatchOperation($mailingBatch, $i);
        ++$i;

        // operation batch : publipostage training
        $mailingBatch = new MailingBatchOperation($security, $parameterBag, $vocabularyRegistry, $humanReadablePropertyAccessorFactory, $managerRegistry);
        $mailingBatch->setDoctrine($managerRegistry);
        $mailingBatch->setTargetClass(Internship::class);
        $mailingBatch->setOptions($confMail['training']);
        $this->addBatchOperation($mailingBatch, $i);
        ++$i;

        // operation batch : publipostage trainee
        $mailingBatchTrainee = new MailingBatchOperation($security, $parameterBag, $vocabularyRegistry, $humanReadablePropertyAccessorFactory, $managerRegistry);
        $mailingBatchTrainee->setDoctrine($managerRegistry);
        $mailingBatchTrainee->setTargetClass(\App\Entity\Back\Trainee::class);
        $mailingBatchTrainee->setOptions($confMail['trainee']);
        $this->addBatchOperation($mailingBatchTrainee, $i);
        ++$i;


        // operation batch : publipostage trainer
        $mailingBatchTrainer = new MailingBatchOperation($security, $parameterBag, $vocabularyRegistry, $humanReadablePropertyAccessorFactory, $managerRegistry);
        $mailingBatchTrainer->setDoctrine($managerRegistry);
        $mailingBatchTrainer->setTargetClass(\App\Entity\Back\Trainer::class);
        $mailingBatchTrainer->setOptions($confMail['trainer']);
        $this->addBatchOperation($mailingBatchTrainer, $i);
        ++$i;

        // operation batch : publipostage inscription
        $mailingBatchInscription = new MailingBatchOperation($security, $parameterBag, $vocabularyRegistry, $humanReadablePropertyAccessorFactory, $managerRegistry);
        $mailingBatchInscription->setDoctrine($managerRegistry);
        $mailingBatchInscription->setTargetClass(\App\Entity\Back\Inscription::class);
        $mailingBatchInscription->setOptions($confMail['inscription']);
        $this->addBatchOperation($mailingBatchInscription, $i);
        ++$i;

        // operation batch : publipostage semestered_training
      //  $mailingBatchTraining = new MailingBatchOperation($security, $parameterBag, $vocabularyRegistry, $humanReadablePropertyAccessorFactory, $managerRegistry);
      //  $mailingBatchTraining->setDoctrine($managerRegistry);
       // $mailingBatchTraining->setTargetClass(\App\Entity\Core\AbstractTraining::class);
      //  $mailingBatchTraining->setOptions($confMail['training']);
      //  $this->addBatchOperation($mailingBatchTraining, $i);
     //   ++$i;

        // operation batch : changement de statut d'inscription
        $inscriptionStatusChangeBatchOperation = new InscriptionStatusChangeBatchOperation($security, $vocabularyRegistry, $emailingBatchOperation, $mailingBatchInscription);
        $inscriptionStatusChangeBatchOperation->setDoctrine($managerRegistry);
        $this->addBatchOperation($inscriptionStatusChangeBatchOperation, $i);
        ++$i;


        // Recuperation conf CSV
        $confCSV = $conf['csv'];
        // operation batch : export CSV pour les sessions
        $CSVBatchSession = new CSVBatchOperation($security);
        $CSVBatchSession->setDoctrine($managerRegistry);
        $CSVBatchSession->setTargetClass(\App\Entity\Back\Session::class);
        $CSVBatchSession->setOptions($confCSV['session']);
        $this->addBatchOperation($CSVBatchSession, $i);
        ++$i;

        // operation batch : export CSV pour les trainings
        $semesteredTrainingCSVBatchOperation = new SemesteredTrainingCSVBatchOperation($security);
        $semesteredTrainingCSVBatchOperation->setDoctrine($managerRegistry);
        $semesteredTrainingCSVBatchOperation->setTargetClass(\App\Entity\Core\AbstractTraining::class);
        $semesteredTrainingCSVBatchOperation->setOptions($confCSV['semestered_training']);
        $this->addBatchOperation($semesteredTrainingCSVBatchOperation, $i);
        ++$i;

        // operation batch : export CSV pour les inscriptions
        $CSVBatchInscription = new CSVBatchOperation($security);
        $CSVBatchInscription->setDoctrine($managerRegistry);
        $CSVBatchInscription->setTargetClass(\App\Entity\Back\Inscription::class);
        $CSVBatchInscription->setOptions($confCSV['inscription']);
        $this->addBatchOperation($CSVBatchInscription, $i);
        ++$i;

        // operation batch : export CSV pour les trainee
        $CSVBatchTrainee = new CSVBatchOperation($security);
        $CSVBatchTrainee->setDoctrine($managerRegistry);
        $CSVBatchTrainee->setTargetClass(\App\Entity\Back\Trainee::class);
        $CSVBatchTrainee->setOptions($confCSV['trainee']);
        $this->addBatchOperation($CSVBatchTrainee, $i);
        ++$i;

        // operation batch : export CSV pour les etablissements
        $CSVBatchInstitution = new CSVBatchOperation($security);
        $CSVBatchInstitution->setDoctrine($managerRegistry);
        $CSVBatchInstitution->setTargetClass(\App\Entity\Back\Institution::class);
        $CSVBatchInstitution->setOptions($confCSV['institution']);
        $this->addBatchOperation($CSVBatchInstitution, $i);
        ++$i;

        // operation batch : export CSV pour les formateurs
        $CSVBatchTrainer = new CSVBatchOperation($security);
        $CSVBatchTrainer->setDoctrine($managerRegistry);
        $CSVBatchTrainer->setTargetClass(\App\Entity\Back\Trainer::class);
        $CSVBatchTrainer->setOptions($confCSV['trainer']);
        $this->addBatchOperation($CSVBatchTrainer, $i);
        ++$i;

        // Recuperation conf PDF
        $confPDF = $conf['pdf'];
        // operation batch : export CSV pour les sessions
        $pdfBatchOperation = new PDFBatchOperation( $pdf, $security, $twigEnvironment, $parameterBag);
        $pdfBatchOperation->setDoctrine($managerRegistry);
        $pdfBatchOperation->setTargetClass(\App\Entity\Back\Inscription::class);
        $pdfBatchOperation->setOptions($confPDF['inscription.attestation']);
        $this->addBatchOperation($pdfBatchOperation, $i);
        ++$i;

        $sessionRegistrationChange = new SessionRegistrationChangeBatchOperation($managerRegistry, $security, /* autres params si besoin */);
        $sessionRegistrationChange->setDoctrine($managerRegistry);
        $this->addBatchOperation($sessionRegistrationChange, $i);
        ++$i;

        // Recuperation conf PDF training
        $confPDF = $conf['pdf'];
        // operation batch : export CSV pour les sessions
        $pdfBatchTraining = new PDFBatchOperation( $pdf, $security, $twigEnvironment, $parameterBag);
        $pdfBatchTraining->setDoctrine($managerRegistry);
        $pdfBatchTraining->setTargetClass(AbstractTraining::class);
        $pdfBatchTraining->setOptions($confPDF['training']);
        $this->addBatchOperation($pdfBatchTraining, $i);
        ++$i;


    }

    /**
     * @param $id
     */
    public function addBatchOperation(BatchOperationInterface $batchOperation, $id): void
    {
        $batchOperation->setId($id);

        //storing batch operation
        $this->operations[$id] = $batchOperation;
    }

    public function getAll(): array
    {
        return $this->operations;
    }

    /**
     * @param $id
     *
     * @return mixed
     */
    public function get($id): mixed
    {
        if (isset($this->operations[$id])) {
            return $this->operations[$id];
        }
        return $id;
    }

    /**
     * @param $servicename
     *
     * @return mixed
     */
    public function getByName(string $servicename): ?BatchOperationInterface
    {

        $map = [
            'sygefor_core.batch.email' => 0,
            'sygefor_core.batch.publipost.session' => 1,
            'sygefor_core.batch.publipost.training' => 2,
            'sygefor_core.batch.publipost.trainee' => 3,
            'sygefor_core.batch.publipost.trainer' => 4,
            'sygefor_core.batch.publipost.inscription' => 5,
            //'sygefor_core.batch.publipost.semestered_training' => 6,
            'sygefor_inscription.batch.inscription_status_change' => 6,
            'sygefor_core.batch.csv.session' => 7,
            'sygefor_core.batch.csv.semestered_training' => 8,
            'sygefor_core.batch.csv.inscription' => 9,
            'sygefor_core.batch.csv.trainee' => 10,
            'sygefor_core.batch.csv.institution' => 11,
            'sygefor_core.batch.csv.trainer' => 12,
            'sygefor_core.batch.pdf.inscription.attestation' => 13,
            'sygefor_training.batch.session_registration_change' => 14,
            'sygefor_core.batch.pdf.training' => 15,
        ];

            if (isset($map[$servicename]) && isset($this->operations[$map[$servicename]])) {
                return $this->operations[$map[$servicename]];
            }
    return null;
    }
}

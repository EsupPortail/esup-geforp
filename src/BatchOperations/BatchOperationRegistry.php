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
use App\Kernel;
use App\Vocabulary\VocabularyRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Snappy\Pdf;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Utils\HumanReadable\HumanReadablePropertyAccessorFactory;
use Twig\Environment;

/**
 * Class BatchOperationRegistry.
 */
final class BatchOperationRegistry
{
    private array $operations = [];

    public function __construct(Security $security, ParameterBagInterface $parameterBag, VocabularyRegistry $vocabularyRegistry, ManagerRegistry $managerRegistry, MailerInterface $mailer, HumanReadablePropertyAccessorFactory $humanReadablePropertyAccessorFactory, Pdf $pdf, Environment $twigEnvironment)
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
        $pdfBatchOperation = new PDFBatchOperation($pdf, $security, $twigEnvironment, $parameterBag);
        $pdfBatchOperation->setDoctrine($managerRegistry);
        $pdfBatchOperation->setTargetClass(\App\Entity\Back\Inscription::class);
        $pdfBatchOperation->setOptions($confPDF['inscription.attestation']);
        $this->addBatchOperation($pdfBatchOperation, $i);
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
    public function getByName($servicename): mixed
    {
        $id = 100000;
        switch ($servicename) {
            case 'sygefor_core.batch.email':
                $id = 0;
                break;
            case 'sygefor_core.batch.publipost.session':
                $id = 1;
                break;
            case 'sygefor_core.batch.publipost.trainee':
                $id = 2;
                break;
            case 'sygefor_core.batch.publipost.trainer':
                $id = 3;
                break;
            case 'sygefor_core.batch.publipost.inscription':
                $id = 4;
                break;
            case 'sygefor_inscription.batch.inscription_status_change':
                $id = 5;
                break;
            case 'sygefor_core.batch.csv.session':
                $id = 6;
                break;
            case 'sygefor_core.batch.csv.semestered_training':
                $id = 7;
                break;
            case 'sygefor_core.batch.csv.inscription':
                $id = 8;
                break;
            case 'sygefor_core.batch.csv.trainee':
                $id = 9;
                break;
            case 'sygefor_core.batch.csv.institution':
                $id = 10;
                break;
            case 'sygefor_core.batch.csv.trainer':
                $id = 11;
                break;
            case 'sygefor_core.batch.pdf.inscription.attestation':
                $id = 12;
                break;

        }

        if (isset($this->operations[$id])) {
            return $this->operations[$id];
        }
        return $id;
    }
}

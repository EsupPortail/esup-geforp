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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Security;
use App\Utils\HumanReadable\HumanReadablePropertyAccessorFactory;
use Twig\Environment;

/**
 * Class BatchOperationRegistry.
 */
class BatchOperationRegistry
{
    /**
     * @var array
     */
    private $operations = array();

    public function __construct(Security $security, ParameterBagInterface $parameterBag, ContainerInterface $container, VocabularyRegistry $vocabularyRegistry, ManagerRegistry $doctrine, MailerInterface $mailer, HumanReadablePropertyAccessorFactory $hrpa, Pdf $pdf, Environment $twig)
    {
        $this->operations = array();

        // Construction de la liste des batch operations 'en dur'
        $i=0;
        $conf = $container->getParameter('batch');
        // Recuperation parametres emails et publipostage
        $confMail = $conf['mailing'];

        // operation batch : envoi email
        $hrpa->setTermCatalog($confMail);
        $emailingBatch = new EmailingBatchOperation($security, $parameterBag, $vocabularyRegistry, $mailer, $hrpa);
        $emailingBatch->setDoctrine($doctrine);
        $this->addBatchOperation($emailingBatch, $i);
        $i++;

        // operation batch : publipostage session
        $mailingBatch = new MailingBatchOperation($security, $parameterBag, $vocabularyRegistry, $hrpa);
        $mailingBatch->setContainer($container);
        $mailingBatch->setDoctrine($doctrine);
        $mailingBatch->setTargetClass('App\Entity\Back\Session');
        $mailingBatch->setOptions($confMail['session']);
        $this->addBatchOperation($mailingBatch, $i);
        $i++;

        // operation batch : publipostage trainee
        $mailingBatchTrainee = new MailingBatchOperation($security, $parameterBag, $vocabularyRegistry, $hrpa);
        $mailingBatchTrainee->setContainer($container);
        $mailingBatchTrainee->setDoctrine($doctrine);
        $mailingBatchTrainee->setTargetClass('App\Entity\Back\Trainee');
        $mailingBatchTrainee->setOptions($confMail['trainee']);
        $this->addBatchOperation($mailingBatchTrainee, $i);
        $i++;

        // operation batch : publipostage trainer
        $mailingBatchTrainer = new MailingBatchOperation($security, $parameterBag, $vocabularyRegistry, $hrpa);
        $mailingBatchTrainer->setContainer($container);
        $mailingBatchTrainer->setDoctrine($doctrine);
        $mailingBatchTrainer->setTargetClass('App\Entity\Back\Trainer');
        $mailingBatchTrainer->setOptions($confMail['trainer']);
        $this->addBatchOperation($mailingBatchTrainer, $i);
        $i++;

        // operation batch : publipostage inscription
        $mailingBatchInscription = new MailingBatchOperation($security, $parameterBag, $vocabularyRegistry, $hrpa);
        $mailingBatchInscription->setContainer($container);
        $mailingBatchInscription->setDoctrine($doctrine);
        $mailingBatchInscription->setTargetClass('App\Entity\Back\Inscription');
        $mailingBatchInscription->setOptions($confMail['inscription']);
        $this->addBatchOperation($mailingBatchInscription, $i);
        $i++;

        // operation batch : changement de statut d'inscription
        $operation = new InscriptionStatusChangeBatchOperation($security, $vocabularyRegistry, $emailingBatch, $mailingBatchInscription);
        $operation->setDoctrine($doctrine);
        $this->addBatchOperation($operation, $i);
        $i++;

        // Recuperation conf CSV
        $confCSV = $conf['csv'];
        // operation batch : export CSV pour les sessions
        $CSVBatchSession = new CSVBatchOperation($security);
        $CSVBatchSession->setDoctrine($doctrine);
        $CSVBatchSession->setTargetClass('App\Entity\Back\Session');
        $CSVBatchSession->setOptions($confCSV['session']);
        $this->addBatchOperation($CSVBatchSession, $i);
        $i++;

        // operation batch : export CSV pour les trainings
        $CSVBatchTraining = new SemesteredTrainingCSVBatchOperation($security);
        $CSVBatchTraining->setDoctrine($doctrine);
        $CSVBatchTraining->setTargetClass('App\Entity\Core\AbstractTraining');
        $CSVBatchTraining->setOptions($confCSV['semestered_training']);
        $this->addBatchOperation($CSVBatchTraining, $i);
        $i++;

        // operation batch : export CSV pour les inscriptions
        $CSVBatchInscription = new CSVBatchOperation($security);
        $CSVBatchInscription->setDoctrine($doctrine);
        $CSVBatchInscription->setTargetClass('App\Entity\Back\Inscription');
        $CSVBatchInscription->setOptions($confCSV['inscription']);
        $this->addBatchOperation($CSVBatchInscription, $i);
        $i++;

        // operation batch : export CSV pour les trainee
        $CSVBatchTrainee = new CSVBatchOperation($security);
        $CSVBatchTrainee->setDoctrine($doctrine);
        $CSVBatchTrainee->setTargetClass('App\Entity\Back\Trainee');
        $CSVBatchTrainee->setOptions($confCSV['trainee']);
        $this->addBatchOperation($CSVBatchTrainee, $i);
        $i++;

        // operation batch : export CSV pour les etablissements
        $CSVBatchInstitution = new CSVBatchOperation($security);
        $CSVBatchInstitution->setDoctrine($doctrine);
        $CSVBatchInstitution->setTargetClass('App\Entity\Back\Institution');
        $CSVBatchInstitution->setOptions($confCSV['institution']);
        $this->addBatchOperation($CSVBatchInstitution, $i);
        $i++;

        // operation batch : export CSV pour les formateurs
        $CSVBatchTrainer = new CSVBatchOperation($security);
        $CSVBatchTrainer->setDoctrine($doctrine);
        $CSVBatchTrainer->setTargetClass('App\Entity\Back\Trainer');
        $CSVBatchTrainer->setOptions($confCSV['trainer']);
        $this->addBatchOperation($CSVBatchTrainer, $i);
        $i++;

        // Recuperation conf PDF
        $confPDF = $conf['pdf'];
        // operation batch : export CSV pour les sessions
        $PDFBatchAttestation = new PDFBatchOperation($pdf, $security, $twig, $parameterBag);
        $PDFBatchAttestation->setDoctrine($doctrine);
        $PDFBatchAttestation->setTargetClass('App\Entity\Back\Inscription');
        $PDFBatchAttestation->setOptions($confPDF['inscription.attestation']);
        $this->addBatchOperation($PDFBatchAttestation, $i);
        $i++;

    }

    /**
     * @param BatchOperationInterface $batchOperation
     * @param $id
     */
    public function addBatchOperation(BatchOperationInterface $batchOperation, $id)
    {
        $batchOperation->setId($id);

        //storing batch operation
        $this->operations[$id] = $batchOperation;
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->operations;
    }

    /**
     * @param $id
     *
     * @return array|void
     */
    public function get($id)
    {
        if (isset($this->operations[$id])) {
            return $this->operations[$id];
        }

        return;
    }

    /**
     * @param $servicename
     *
     * @return array|void
     */
    public function getByName($servicename)
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

        return;
    }
}

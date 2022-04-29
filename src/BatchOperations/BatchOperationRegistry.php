<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 07/04/14
 * Time: 11:15.
 */

namespace App\BatchOperations;

use App\BatchOperations\Generic\EmailingBatchOperation;
use App\BatchOperations\Generic\MailingBatchOperation;
use App\BatchOperations\Inscription\InscriptionStatusChangeBatchOperation;
use App\Vocabulary\VocabularyRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Security;
use App\Utils\HumanReadable\HumanReadablePropertyAccessorFactory;

/**
 * Class BatchOperationRegistry.
 */
class BatchOperationRegistry
{
    /**
     * @var array
     */
    private $operations = array();

    public function __construct(Security $security, ParameterBagInterface $parameterBag, ContainerInterface $container, VocabularyRegistry $vocabularyRegistry, ManagerRegistry $doctrine, MailerInterface $mailer, HumanReadablePropertyAccessorFactory $hrpa)
    {
        $this->operations = array();

        // Construction de la liste des batch operations 'en dur'
        $i=0;
        $conf = $container->getParameter('batch');

        // operation batch : envoi email
        $confMail = $conf['mailing'];
        $hrpa->setTermCatalog($confMail);
        $emailingBatch = new EmailingBatchOperation($security, $parameterBag, $vocabularyRegistry, $mailer, $hrpa);
        $emailingBatch->setDoctrine($doctrine);
        $this->addBatchOperation($emailingBatch, $i);
        $i++;

        // operation batch : publipostage session
        $mailingBatch = new MailingBatchOperation($security, $parameterBag, $vocabularyRegistry, $hrpa);
        $mailingBatch->setContainer($container);
        $mailingBatch->setDoctrine($doctrine);
        $mailingBatch->setTargetClass('App\Entity\Session');
        $mailingBatch->setOptions($confMail['session']);
        $this->addBatchOperation($mailingBatch, $i);
        $i++;

        // operation batch : publipostage trainee
        $mailingBatchTrainee = new MailingBatchOperation($security, $parameterBag, $vocabularyRegistry, $hrpa);
        $mailingBatchTrainee->setContainer($container);
        $mailingBatchTrainee->setDoctrine($doctrine);
        $mailingBatchTrainee->setTargetClass('App\Entity\Trainee');
        $mailingBatchTrainee->setOptions($confMail['trainee']);
        $this->addBatchOperation($mailingBatchTrainee, $i);
        $i++;

        // operation batch : publipostage trainer
        $mailingBatchTrainer = new MailingBatchOperation($security, $parameterBag, $vocabularyRegistry, $hrpa);
        $mailingBatchTrainer->setContainer($container);
        $mailingBatchTrainer->setDoctrine($doctrine);
        $mailingBatchTrainer->setTargetClass('App\Entity\Trainer');
        $mailingBatchTrainer->setOptions($confMail['trainer']);
        $this->addBatchOperation($mailingBatchTrainer, $i);
        $i++;

        // operation batch : changement de statut d'inscription
        $operation = new InscriptionStatusChangeBatchOperation($security, $vocabularyRegistry, $emailingBatch);
        $operation->setDoctrine($doctrine);
        $this->addBatchOperation($operation, $i);
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
            case 'sygefor_inscription.batch.inscription_status_change':
                $id = 4;
                break;
        }
        if (isset($this->operations[$id])) {
            return $this->operations[$id];
        }

        return;
    }
}

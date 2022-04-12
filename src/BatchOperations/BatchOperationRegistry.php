<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 07/04/14
 * Time: 11:15.
 */

namespace App\BatchOperations;

use App\BatchOperations\Generic\EmailingBatchOperation;
use App\BatchOperations\Inscription\InscriptionStatusChangeBatchOperation;
use App\Vocabulary\VocabularyRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;
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

    public function __construct(Security $security, ContainerInterface $container, VocabularyRegistry $vocabularyRegistry, ManagerRegistry $doctrine, MailerInterface $mailer, HumanReadablePropertyAccessorFactory $hrpa)
    {
        $this->operations = array();

        // Construction de la liste des batch operations 'en dur'
        $i=0;
        $conf = $container->getParameter('batch');
        $hrpa->setTermCatalog($conf['mailing']);
        $emailingBatch = new EmailingBatchOperation($security, $vocabularyRegistry, $mailer, $hrpa);
        $emailingBatch->setDoctrine($doctrine);
        $this->addBatchOperation($emailingBatch, $i);
        $i++;
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
            case 'sygefor_inscription.batch.inscription_status_change':
                $id = 1;
                break;
        }
        if (isset($this->operations[$id])) {
            return $this->operations[$id];
        }

        return;
    }
}

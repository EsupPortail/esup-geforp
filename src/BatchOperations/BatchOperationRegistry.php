<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 07/04/14
 * Time: 11:15.
 */

namespace App\BatchOperations;

use App\BatchOperations\Inscription\InscriptionStatusChangeBatchOperation;
use App\Vocabulary\VocabularyRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

/**
 * Class BatchOperationRegistry.
 */
class BatchOperationRegistry
{
    /**
     * @var array
     */
    private $operations = array();

    public function __construct(Security $security, VocabularyRegistry $vocabularyRegistry, ManagerRegistry $doctrine)
    {
        $this->operations = array();

        // Construction de la liste des batch operations 'en dur'
        $i=0;
        $operation = new InscriptionStatusChangeBatchOperation($security, $vocabularyRegistry);
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
            case 'sygefor_inscription.batch.inscription_status_change':
                $id = 0;
                break;
        }
        if (isset($this->operations[$id])) {
            return $this->operations[$id];
        }

        return;
    }
}

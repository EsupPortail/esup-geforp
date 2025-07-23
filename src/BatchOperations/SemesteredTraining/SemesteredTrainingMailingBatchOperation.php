<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 28/04/14
 * Time: 10:41.
 */
namespace App\BatchOperations\SemesteredTraining;

use App\BatchOperations\Generic\MailingBatchOperation as BaseMailingBatchOperation;
use App\Model\SemesteredTraining;

/**
 * Class MailingBatchOperation.
 */
final class SemesteredTrainingMailingBatchOperation extends BaseMailingBatchOperation
{
    private $idList;

    /**
     * Getting objects list.
     *
     * @param array $idList
     *
     * @return \App\Model\SemesteredTraining[]
     */
    protected function getObjectList($idList): array
    {
        return SemesteredTraining::getSemesteredTrainingsByIds($this->idList, $this->doctrine->getManager());
    }
}

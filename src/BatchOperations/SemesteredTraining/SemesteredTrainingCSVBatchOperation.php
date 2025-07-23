<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 28/04/14
 * Time: 10:43.
 */
namespace App\BatchOperations\SemesteredTraining;

use App\BatchOperations\Generic\CSVBatchOperation as BaseCSVBatchOperation;
use App\Model\SemesteredTraining;

final class SemesteredTrainingCSVBatchOperation extends BaseCSVBatchOperation
{
    /**
     * @param $idList
     *
     * @return App\Model\SemesteredTraining[]
     */
    protected function getObjectList($idList): array
    {
        $em = $this->doctrine->getManager();

        return SemesteredTraining::getSemesteredTrainingsByIds($idList, $em);
    }
}

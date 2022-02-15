<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 28/04/14
 * Time: 10:43.
 */

namespace CoreBundle\BatchOperations\SemesteredTraining;

use CoreBundle\BatchOperations\Generic\CSVBatchOperation as BaseCSVBatchOperation;
use CoreBundle\Model\SemesteredTraining;

class SemesteredTrainingCSVBatchOperation extends BaseCSVBatchOperation
{
    /**
     * @param $idList
     *
     * @return \Sygefor\Bundle\CoreBundle\Model\SemesteredTraining[]
     */
    protected function getObjectList($idList)
    {
        return SemesteredTraining::getSemesteredTrainingsByIds($idList, $this->em);
    }
}

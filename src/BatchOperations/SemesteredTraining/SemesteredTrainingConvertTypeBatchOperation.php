<?php

namespace CoreBundle\BatchOperations\SemesteredTraining;

use CoreBundle\BatchOperations\Training\ConvertTypeBatchOperation as BaseConvertTypeBatchOperation;
use CoreBundle\Model\SemesteredTraining;

class SemesteredTrainingConvertTypeBatchOperation extends BaseConvertTypeBatchOperation
{
    protected function getObjectList($idList)
    {
        return SemesteredTraining::getTrainingsByIds($idList, $this->em, array());
    }
}

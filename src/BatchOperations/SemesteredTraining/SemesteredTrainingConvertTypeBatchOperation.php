<?php

namespace App\BatchOperations\SemesteredTraining;

use App\BatchOperations\Training\ConvertTypeBatchOperation as BaseConvertTypeBatchOperation;
use App\Model\SemesteredTraining;

final class SemesteredTrainingConvertTypeBatchOperation extends BaseConvertTypeBatchOperation
{
    protected function getObjectList($idList = []): array
    {
        return SemesteredTraining::getTrainingsByIds($idList, $this->doctrine->getManager());
    }
}

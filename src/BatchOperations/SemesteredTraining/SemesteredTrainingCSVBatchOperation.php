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
use Doctrine\Persistence\ManagerRegistry;
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

        if (is_string($idList)) {
            $idList = $this->parseIds($idList);
        } elseif (!is_array($idList)) {
            $idList = [];
        }

        // Normalisation des IDs simples en id_year_semester
        $now = new \DateTime();
        $currentYear = (int) $now->format('Y');
        $currentSemester = ((int) $now->format('m') <= 6) ? 1 : 2;

        $normalized = [];
        foreach ($idList as $id) {
            $id = trim((string)$id, "\"' "); // nettoie ""1495""
            if (ctype_digit($id)) {
                $id = sprintf('%d_%d_%d', (int)$id, $currentYear, $currentSemester);
            }
            $normalized[] = $id;
        }

        error_log('SemesteredTrainingCSVBatchOperation::getObjectList normalized: ' . var_export($normalized, true));

        return SemesteredTraining::getSemesteredTrainingsByIds($normalized, $em);
    }


    /**
     * @param string|null $rawIds
     * @param string $delimiter
     * @return string[]
     */
    public static function parseIds(array $rawIds, int $year = null, int $semester = null): array
    {
        $year ??= (int) date('Y');        // Par défaut l'année en cours
        $semester ??= ((int) date('m') <= 6 ? 1 : 2); // Par défaut semestre courant

        $ids = [];
        foreach ($rawIds as $rawId) {
            $id = trim((string)$rawId, "\"' ");
            if (!is_numeric($id)) {
                continue; // Ignore les non-numeriques
            }

            $ids[] = sprintf('%d_%d_%d', $id, $year, $semester);
        }

        return $ids;
    }
}

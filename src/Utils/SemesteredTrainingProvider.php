<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 23/04/14
 * Time: 11:30.
 */

namespace App\Utils;

use Doctrine\Common\Persistence\ManagerRegistry;
use FOS\ElasticaBundle\Doctrine\ORM\Provider;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;
use FOS\ElasticaBundle\Provider\IndexableInterface;
use App\Model\SemesteredTraining;

/**
 * Class SemesteredTrainingProvider.
 */
final class SemesteredTrainingProvider extends Provider
{
    /**
     * @var array<string, string>
     */
    private const OPTIONS = ['indexName' => 'sygefor3', 'typeName' => 'semestered_training'];
    /**
     * SemesteredTrainingProvider constructor.
     *
     * @param ObjectPersisterInterface $objectPersister
     * @param IndexableInterface       $indexable
     */
    public function __construct(ObjectPersisterInterface $objectPersister, IndexableInterface $indexable, \Doctrine\Persistence\ManagerRegistry $managerRegistry)
    {
        parent::__construct($objectPersister, $indexable, \App\Entity\Core\AbstractTraining::class, self::OPTIONS, $managerRegistry);
    }

    /**
     * @param object $queryBuilder
     * @param int    $limit
     * @param int    $offset
     *
     * @return array
     */
    public function fetchSlice($queryBuilder, $limit, $offset)
    {
        $trainings = parent::fetchSlice($queryBuilder, $limit, $offset);
        $semTrains = [];

        foreach ($trainings as $training) {
            $tmpSemTrains = SemesteredTraining::getSemesteredTrainingsForTraining($training);
            $semTrains = array_merge($semTrains, $tmpSemTrains);
        }

        return $semTrains;
    }
}

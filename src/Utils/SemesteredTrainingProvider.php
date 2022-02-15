<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 23/04/14
 * Time: 11:30.
 */

namespace CoreBundle\Utils;

use Doctrine\Common\Persistence\ManagerRegistry;
use FOS\ElasticaBundle\Doctrine\ORM\Provider;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;
use FOS\ElasticaBundle\Provider\IndexableInterface;
use CoreBundle\Model\SemesteredTraining;

/**
 * Class SemesteredTrainingProvider.
 */
class SemesteredTrainingProvider extends Provider
{
    /**
     * SemesteredTrainingProvider constructor.
     *
     * @param ObjectPersisterInterface $objectPersister
     * @param IndexableInterface       $indexable
     * @param ManagerRegistry          $managerRegistry
     */
    public function __construct(ObjectPersisterInterface $objectPersister, IndexableInterface $indexable, ManagerRegistry $managerRegistry)
    {
        $options = array(
          'indexName' => 'sygefor3',
          'typeName' => 'semestered_training',
        );
        parent::__construct($objectPersister, $indexable, 'Sygefor\Bundle\CoreBundle\Entity\AbstractTraining', $options, $managerRegistry);
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
        $semTrains = array();

        foreach ($trainings as $train) {
            $tmpSemTrains = SemesteredTraining::getSemesteredTrainingsForTraining($train);
            $semTrains = array_merge($semTrains, $tmpSemTrains);
        }

        return $semTrains;
    }
}

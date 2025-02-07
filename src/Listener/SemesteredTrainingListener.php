<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 17/04/14
 * Time: 10:06.
 */
namespace App\Listener;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\Events;
use FOS\ElasticaBundle\Doctrine\Listener;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;
use FOS\ElasticaBundle\Provider\IndexableInterface;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTraining;
use App\Model\SemesteredTraining;

/**
 * Class SemesteredTrainingListener.
 */
final class SemesteredTrainingListener extends Listener
{
    public $events;
    /**
     * @var mixed[]|string[]
     */
    public $scheduledForDeletion = [];
    public $scheduledForUpdate;
    public $scheduledForInsertion;
    /**
     * @var string[]
     */
    private const EVENTS = [Events::preRemove, Events::postPersist, Events::postUpdate, Events::preFlush, Events::postFlush];
    /**
     *
     */
    public function __construct(ObjectPersisterInterface $objectPersister, IndexableInterface $indexable, Type $type, array $config = [], $logger = null)
    {
        $config = ['identifier' => 'id', 'indexName'  => 'sygefor3', 'typeName'   => 'semestered_training'];

        parent::__construct($objectPersister, self::EVENTS, $indexable, $config);
    }

    /**
     * Provides unified method for retrieving a doctrine object from an EventArgs instance.
     *
     *
     * @throws \RuntimeException if no valid getter is found.
     *
     * @return object Entity | Document
     */
    private function getDoctrineObject(EventArgs $eventArgs)
    {
        if (method_exists($eventArgs, 'getObject')) {
            return $eventArgs->getObject();
        }
        if (method_exists($eventArgs, 'getEntity')) {
            return $eventArgs->getEntity();
        }
        elseif (method_exists($eventArgs, 'getDocument')) {
            return $eventArgs->getDocument();
        }

        throw new \RuntimeException('Unable to retrieve object from EventArgs.');
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return $this->events;
    }

    public function preRemove(EventArgs $eventArgs): void
    {
        $object = $this->getDoctrineObject($eventArgs);

        if (in_array('Sygefor\Bundle\TrainingBundle\Entity\Training\AbstractTraining', class_parents($object::class), true)) {
            $semTrainings = SemesteredTraining::getSemesteredTrainingsForTraining($object);
            foreach ($semTrainings as $semT) {
                $this->scheduledForDeletion [] = $semT->getId();
            }
            
            $this->scheduledForDeletion[] = $object->getId() . '_' . $object->getFirstSessionPeriodYear() . '_' . $object->getFirstSessionPeriodSemester();
        } elseif ($object::class === 'Sygefor\Bundle\TrainingBundle\Entity\Session\AbstractSession') {
            $training = $object->getTraining();
            if ($training) {
                // remove all semestered training associated to this training because of root id change possibility
                $semTrainings = SemesteredTraining::getSemesteredTrainingsForTraining($training);
                foreach ($semTrainings as $semTraining) {
                    $this->scheduledForDeletion [] = $semTraining->getId();
                }

                $this->scheduledForDeletion[] = $training->getId() . '_' . $training->getFirstSessionPeriodYear() . '_' . $training->getFirstSessionPeriodSemester();
                $this->scheduledForDeletion[] = $training->getId() . '_' . $object->getYear() . '_' . $object->getSemester();

                // we need to update the semesteredTraining its associated with
                $date                        = $object->getDatebegin();
                $training                    = $object->getTraining();
                $year                        = $date->format('Y');
                $semester                    = ($date->format('m') < 6) ? 1 : 2;
                $semesteredTraining          = new SemesteredTraining($year, $semester, $training);
                $this->scheduledForUpdate [] = $semesteredTraining;
            }
        }
    }

    public function postPersist(EventArgs $eventArgs): void
    {
        $object = $this->getDoctrineObject($eventArgs);

        if (in_array('Sygefor\Bundle\TrainingBundle\Entity\Training\AbstractTraining', class_parents($object::class), true)) {
            $semTrainings                = SemesteredTraining::getSemesteredTrainingsForTraining($object);
            $this->scheduledForInsertion = array_merge($this->scheduledForInsertion, $semTrainings);
        } elseif ($object::class === 'Sygefor\Bundle\TrainingBundle\Entity\Session\AbstractSession') {
            //building SemesteredTraining object
            $training = $object->getTraining();
            if ($training) {
                /** @var \DateTime $date */
                $date     = $object->getDatebegin();
                $year     = $date->format('Y');
                $semester = ($date->format('m') < 6) ? 1 : 2;

                /** @var SemesteredTraining $semesteredTraining */
                $semesteredTraining = new SemesteredTraining($year, $semester, $training);

                // delete initial semestered training if needed
                $keepInitialSemesteredTraining = false;
                foreach ($training->getSessions() as $session) {
                    if ((int) $session->getSemester() === $training->getFirstSessionPeriodSemester() && (int) $session->getYear() === $training->getFirstSessionPeriodYear()) {
                        $keepInitialSemesteredTraining = true;
                        break;
                    }
                }

                if ( ! $keepInitialSemesteredTraining) {
                    $this->scheduledForDeletion[] = $training->getId() . '_' . $training->getFirstSessionPeriodYear() . '_' . $training->getFirstSessionPeriodSemester();
                }

                $this->scheduledForUpdate[] = $semesteredTraining;
            }
        }
    }

    public function postUpdate(EventArgs $eventArgs): void
    {
        /** @var AbstractSession $object */
        $object = $this->getDoctrineObject($eventArgs);

        if (in_array('Sygefor\Bundle\TrainingBundle\Entity\Training\AbstractTraining', class_parents($object::class), true)) {
            $semTrainings             = SemesteredTraining::getSemesteredTrainingsForTraining($object);
            $this->scheduledForUpdate = array_merge($this->scheduledForUpdate, $semTrainings);
        } elseif ($object::class === 'Sygefor\Bundle\TrainingBundle\Entity\Session\AbstractSession') {
            $training = $object->getTraining();
            if ($training) {
/*                $query = new Match();
                $query->setField('training.id', $training->getId());

                //deleting objects
                $this->index->deleteByQuery($query);
*/
                //inserting new objects
                $semTrainings                = SemesteredTraining::getSemesteredTrainingsForTraining($training);
                $this->scheduledForInsertion = array_merge($this->scheduledForInsertion, $semTrainings);
            }
        }
    }
}

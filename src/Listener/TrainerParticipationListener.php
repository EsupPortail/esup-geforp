<?php

namespace Sygefor\Bundle\TrainingBundle\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Sygefor\Bundle\TrainingBundle\Entity\Session\AbstractParticipation;
use Sygefor\Bundle\TrainerBundle\Entity\AbstractTrainer;

/**
 * This listener sync shared informations between Trainee and Inscription.
 */
class TrainerParticipationListener implements EventSubscriber
{
    private $entities = array();

    /**
     * Returns hash of events, that this listener is bound to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::prePersist,
            Events::postUpdate,
            Events::postFlush,
        );
    }

    /**
     * @param $entity
     *
     * @return bool
     */
    protected function isTrainer($entity)
    {
        return $entity instanceof AbstractTrainer;
    }

    /**
     * @param $entity
     *
     * @return bool
     */
    protected function isParticipation($entity)
    {
        return $entity instanceof AbstractParticipation;
    }

    /**
     * When a participation is created, copy organization and is_organization
     * from the Trainer entity.
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        /** @var AbstractParticipation $entity */
        $entity = $eventArgs->getEntity();
        if ($this->isParticipation($entity)) {
            $entity->setIsOrganization($entity->getTrainer()->getIsOrganization());
            $entity->setOrganization($entity->getTrainer()->getOrganization());
        }
    }

    /**
     * When a trainer is updated, we keep it in mind for an update on postflush event
     * for future sessions.
     */
    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if ($this->isTrainer($entity)) {
            $em = $eventArgs->getEntityManager();
            // get the update field list
            $uow = $em->getUnitOfWork();
            $uow->computeChangeSets();
            $changes = array_keys($uow->getEntityChangeSet($entity));

            // check any organization or is_organization field changed
            foreach ($changes as $property) {
                if ($property === 'isOrganization' || $property === 'organization' && !in_array($entity, $this->entities, true)) {
                    $this->entities[] = $entity;

                    return;
                }
            }
        }
    }

    /**
     * All entities that where stored are updated.
     *
     * @param PostFlushEventArgs $eventArgs
     */
    public function postFlush(PostFlushEventArgs $eventArgs)
    {
        $em = $eventArgs->getEntityManager();

        if (!empty($this->entities)) {
            foreach ($this->entities as $entity) {
                // update current inscriptions
                $query = $em
                    ->createQuery('SELECT p FROM SygeforTrainingBundle:Session\\AbstractParticipation p
                                  JOIN p.session s
                                  WHERE p.trainer = :trainer AND s.dateBegin >= CURRENT_TIMESTAMP()')
                    ->setParameter('trainer', $entity);

                foreach ($query->getResult() as $participation) {
                    $participation->setIsOrganization($entity->getIsOrganization());
                    $participation->setOrganization($entity->getOrganization());
                }
            }
            $this->entities = array();
            $em->flush();
        }
    }
}

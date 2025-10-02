<?php

namespace App\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use App\Entity\Core\AbstractParticipation;
use App\Entity\Core\AbstractTrainer;

/**
 * This listener sync shared informations between Trainee and Inscription.
 */
final class TrainerParticipationListener implements EventSubscriber
{
    private array $entities = [];

    /**
     * Returns hash of events, that this listener is bound to.
     *
     */
    public function getSubscribedEvents(): array
    {
        return [Events::prePersist, Events::postUpdate, Events::postFlush];
    }

    /**
     * @param $entity
     *
     * @return bool
     */
    private function isTrainer($entity)
    {
        return $entity instanceof AbstractTrainer;
    }

    /**
     * @param $entity
     *
     * @return bool
     */
    private function isParticipation($entity)
    {
        return $entity instanceof AbstractParticipation;
    }

    /**
     * When a participation is created, copy organization and is_organization
     * from the Trainer entity.
     */
    public function prePersist(LifecycleEventArgs $lifecycleEventArgs): void
    {
        /** @var AbstractParticipation $entity */
        $entity = $lifecycleEventArgs->getEntity();
        if ($this->isParticipation($entity)) {
            $entity->setIsOrganization($entity->getTrainer()->getIsOrganization());
            $entity->setOrganization($entity->getTrainer()->getOrganization());
        }
    }

    /**
     * When a trainer is updated, we keep it in mind for an update on postflush event
     * for future sessions.
     */
    public function postUpdate(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $entity = $lifecycleEventArgs->getEntity();
        if ($this->isTrainer($entity)) {
            $entityManager = $lifecycleEventArgs->getEntityManager();
            // get the update field list
            $unitOfWork = $entityManager->getUnitOfWork();
            $unitOfWork->computeChangeSets();
            $changes = array_keys($unitOfWork->getEntityChangeSet($entity));

            // check any organization or is_organization field changed
            foreach ($changes as $change) {
                if ($change === 'isOrganization' || $change === 'organization' && !in_array($entity, $this->entities, true)) {
                    $this->entities[] = $entity;

                    return;
                }
            }
        }
    }

    /**
     * All entities that where stored are updated.
     *
     */
    public function postFlush(PostFlushEventArgs $postFlushEventArgs): void
    {
        $entityManager = $postFlushEventArgs->getEntityManager();

        if ($this->entities !== []) {
            foreach ($this->entities as $entity) {
                // update current inscriptions
                $query = $entityManager
                    ->createQuery('SELECT p FROM App\Entity\Core\AbstractParticipation p
                                  JOIN p.session s
                                  WHERE p.trainer = :trainer AND s.dateBegin >= CURRENT_TIMESTAMP()')
                    ->setParameter('trainer', $entity);

                foreach ($query->getResult() as $participation) {
                    $participation->setIsOrganization($entity->getIsOrganization());
                    $participation->setOrganization($entity->getOrganization());
                }
            }

            $this->entities = [];
            $entityManager->flush();
        }
    }
}

<?php

namespace App\EventListener\ORM;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\PersistentCollection;
use App\Entity\Core\AbstractTrainer;
use Symfony\Component\PropertyAccess\PropertyAccessor;

final class OrganizationChangedListener implements EventSubscriber
{
    private array $entities = [];

    /**
     * Returns hash of events, that this listener is bound to.
     *
     */
    public function getSubscribedEvents(): array
    {
        return [Events::postUpdate, Events::postFlush];
    }

    /**
     * @param $entity
     *
     */
    private function containsOrganization($entity): bool
    {
        return method_exists($entity, 'getOrganization');
    }

    /**
     * When a entity is updated, we keep it in mind for an update on postflush event if organization has changed
     * for future sessions.
     */
    public function postUpdate(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $entity = $lifecycleEventArgs->getEntity();
        if ($this->containsOrganization($entity)) {
            $entityManager = $lifecycleEventArgs->getEntityManager();
            // get the update field list
            $unitOfWork = $entityManager->getUnitOfWork();
            $unitOfWork->computeChangeSets();
            $changes = array_keys($unitOfWork->getEntityChangeSet($entity));

            // check any organization or is_organization field changed
            foreach ($changes as $change) {
                if ($change === 'organization' && !in_array($entity, $this->entities, true)) {
                    $this->entities[] = $entity;

                    return;
                }
            }
        }
    }

    /**
     * For all entities with an organization changed, we remove entities related to this organization.
     *
     */
    public function postFlush(PostFlushEventArgs $postFlushEventArgs): void
    {
        if ($this->entities !== []) {
            $em = $postFlushEventArgs->getEntityManager();
            $propertyAccessor = new PropertyAccessor();
            foreach ($this->entities as $entity) {
                $metadata = $em->getClassMetadata($entity::class);
                // read entity properties metadata to find related object attached to an organization
                foreach ($metadata->associationMappings as $fieldName => $fieldMD) {
                    if (!empty($fieldMD['targetEntity']) && !in_array($fieldName, $this->getExcludedProperties($entity::class), true)) {
                        $propertyMetadata = $em->getClassMetadata($fieldMD['targetEntity']);
                        // we check if property is related to an organization
                        if (isset($propertyMetadata->associationMappings['organization'])) {
                            // get the value
                            $value = $propertyAccessor->getValue($entity, $fieldName);
                            // remove array collection items not related to the new organization
                            if ($value instanceof PersistentCollection) {
                                foreach ($value as $item) {
                                    if (!is_object($item)) {
                                        continue;
                                    }
                                    if (!method_exists($item, 'getOrganization')) {
                                        continue;
                                    }
                                    if ($item->getOrganization() === null) {
                                        continue;
                                    }
                                    if ($item->getOrganization() === $entity->getOrganization()) {
                                        continue;
                                    }
                                    $value->removeElement($item);
                                }
                            }

                            // remove properties related to another organization
//                            elseif (method_exists($value, 'getOrganization')) {
//                                if ($value->getOrganization() !== null && $value->getOrganization() !== $entity->getOrganization()) {
//                                    $propertyAccessor->setValue($value, 'setOrganization', null);
//                                }
//                            }
                        }
                    }
                }
            }

            $this->entities = [];
            $em->flush();
        }
    }

    /**
     * Exclude some check because we want to keep them if organization changes.
     *
     * @param $class
     *
     * @return array
     */
    private function getExcludedProperties($class)
    {
        $excludedProperties = [AbstractTrainer::class => ['participations']];

        if (isset($excludedProperties[$class])) {
            return $excludedProperties[$class];
        }

        return [];
    }
}

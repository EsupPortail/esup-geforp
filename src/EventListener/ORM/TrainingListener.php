<?php

namespace App\EventListener\ORM;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;
use App\Entity\Core\AbstractTraining;
use App\Utils\TrainingTypeRegistry;

/**
 * Populate the Training discriminator map
 * + auto-increment local number.
 */
final class TrainingListener implements EventSubscriber
{
    /**
     * Constructor.
     */
    public function __construct(protected TrainingTypeRegistry $trainingTypeRegistry)
    {
    }

    /**
     * Returns hash of events, that this listener is bound to.
     *
     */
    public function getSubscribedEvents(): array
    {
        return [Events::prePersist, Events::loadClassMetadata];
    }

    /**
     * Populate the Training discriminator map.
     *
     * @param LoadClassMetadataEventArgs $loadClassMetadataEventArgs The event arguments
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $loadClassMetadataEventArgs): void
    {
        $classMetadata = $loadClassMetadataEventArgs->getClassMetadata();
        if (!$classMetadata->reflClass instanceof \ReflectionClass) {
            return;
        }

        if ($classMetadata->getName() === AbstractTraining::class) {
            // fill the discriminator map with types from the registry
            $map = [];
            foreach ($this->trainingTypeRegistry->getTypes() as $key => $type) {
                $map[$key] = $type['class'];
            }

            $classMetadata->setDiscriminatorMap($map);

            // update material trait to map trainings
            $classMetadata->associationMappings['materials']['mappedBy'] = 'training';
        }
    }

    /**
     * When a inscription is created, copy all the professional situation
     * from the Trainee entity.
     *
     */
    public function prePersist(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $entity = $lifecycleEventArgs->getEntity();
        if ($entity instanceof AbstractTraining && !$entity->getNumber()) {
            $entityManager = $lifecycleEventArgs->getEntityManager();
            $query = $entityManager->createQuery('SELECT MAX(t.number) FROM '.AbstractTraining::class.' t WHERE t.organization = :organization')
                ->setParameter('organization', $entity->getOrganization());
            $max = (int) $query->getSingleScalarResult();
            $entity->setNumber($max + 1);
        }
    }
}

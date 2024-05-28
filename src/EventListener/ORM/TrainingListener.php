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
class TrainingListener implements EventSubscriber
{
    protected $registry;

    /**
     * Constructor.
     */
    public function __construct(TrainingTypeRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Returns hash of events, that this listener is bound to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::prePersist,
            Events::loadClassMetadata,
        );
    }

    /**
     * Populate the Training discriminator map.
     *
     * @param LoadClassMetadataEventArgs $eventArgs The event arguments
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $eventArgs)
    {
        $classMetadata = $eventArgs->getClassMetadata();
        if (null === $classMetadata->reflClass) {
            return;
        }

        if ($classMetadata->getName() === AbstractTraining::class) {
            // fill the discriminator map with types from the registry
            $map = array();
            foreach ($this->registry->getTypes() as $key => $type) {
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
     * @param LifecycleEventArgs $eventArgs
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        if ($entity instanceof AbstractTraining && !$entity->getNumber()) {
            $em = $eventArgs->getEntityManager();
            $query = $em->createQuery('SELECT MAX(t.number) FROM '.AbstractTraining::class.' t WHERE t.organization = :organization')
                ->setParameter('organization', $entity->getOrganization());
            $max = (int) $query->getSingleScalarResult();
            $entity->setNumber($max + 1);
        }
    }
}

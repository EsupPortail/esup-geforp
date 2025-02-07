<?php

namespace App\Listener;

use App\Entity\Back\Internship;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventSubscriber;
//use Doctrine\ORM\Event\LifecycleEventArgs;
//use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use App\Entity\Core\AbstractTraining;

/**
 * Populate the Training discriminator map
 * + auto-increment local number.
 */
#[AsDoctrineListener(event: Events::loadClassMetadata)]
#[AsDoctrineListener(event: Events::prePersist)]final class TrainingListener implements EventSubscriber
{
    /**
     * Returns hash of events, that this listener is bound to.
     *
     */
    public function getSubscribedEvents(): array
    {
        return [Events::loadClassMetadata, Events::prePersist];
    }

    /**
     * Populate the Training discriminator map.
     *
     * @param LoadClassMetadataEventArgs $loadClassMetadataEventArgs The event arguments
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $loadClassMetadataEventArgs): void
    {
        $classMetadata = $loadClassMetadataEventArgs->getClassMetadata();
        if (null === $classMetadata->reflClass) {
            return;
        }

        if($classMetadata->getName() === \App\Entity\Core\AbstractTraining::class) {
            // fill the discriminator map with types from the registry
            $map = [];

                $map['internship'] = Internship::class;

            $classMetadata->setDiscriminatorMap($map);
        }
    }

    /**
     * Increment the local training number.
     *
     * @param LifecycleEventArgs $lifecycleEventArgs The event arguments
     */
    public function prePersist(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $training = $lifecycleEventArgs->getEntity();
        if($training instanceof AbstractTraining && ! $training->getNumber()) {
            $em    = $lifecycleEventArgs->getEntityManager();
            $query = $em->createQuery("SELECT MAX(t.number) FROM App\Entity\Core\AbstractTraining t WHERE t.organization = :organization")
              ->setParameter('organization', $training->getOrganization());
            $max = (int) $query->getSingleScalarResult();
            $training->setNumber($max + 1);
        }
    }
}

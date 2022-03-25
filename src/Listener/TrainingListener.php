<?php

namespace App\Listener;

use App\Entity\Internship;
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
class TrainingListener implements EventSubscriber
{
    /**
     * Returns hash of events, that this listener is bound to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::loadClassMetadata,
            Events::prePersist,
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

        if($classMetadata->getName() === 'App\Entity\Core\AbstractTraining') {
            // fill the discriminator map with types from the registry
            $map = array();

                $map['internship'] = Internship::class;

            $classMetadata->setDiscriminatorMap($map);
        }
    }

    /**
     * Increment the local training number.
     *
     * @param LifecycleEventArgs $eventArgs The event arguments
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        dump('prepersist');
        $training = $eventArgs->getEntity();
        if($training instanceof AbstractTraining && ! $training->getNumber()) {
            $em    = $eventArgs->getEntityManager();
            $query = $em->createQuery("SELECT MAX(t.number) FROM App\Entity\Core\AbstractTraining t WHERE t.organization = :organization")
              ->setParameter('organization', $training->getOrganization());
            $max = (int) $query->getSingleScalarResult();
            $training->setNumber($max + 1);
        }
    }
}

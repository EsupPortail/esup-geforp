<?php

namespace App\EventListener\ORM;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use App\Entity\Core\AbstractSession;

/**
 * Populate the Training discriminator map
 * + auto-increment local number.
 */
class SessionListener implements EventSubscriber
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

        if ($classMetadata->getName() === AbstractSession::class) {
            // update material trait to map sessions
            $classMetadata->associationMappings['materials']['mappedBy'] = 'session';
        }
    }
}

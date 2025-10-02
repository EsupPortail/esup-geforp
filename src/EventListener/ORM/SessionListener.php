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
final class SessionListener implements EventSubscriber
{
    /**
     * Returns hash of events, that this listener is bound to.
     *
     */
    public function getSubscribedEvents(): array
    {
        return [Events::loadClassMetadata];
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

        if ($classMetadata->getName() === AbstractSession::class) {
            // update material trait to map sessions
            $classMetadata->associationMappings['materials']['mappedBy'] = 'session';
        }
    }
}

<?php

namespace App\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use App\Entity\Core\AbstractSession;

/**
 * Remove empty module when removing a session.
 */
final class SessionListener implements EventSubscriber
{
    /**
     * Returns hash of events, that this listener is bound to.
     *
     */
    public function getSubscribedEvents(): array
    {
        return [Events::preRemove];
    }

    /**
     * Increment the local training number.
     *
     * @param LifecycleEventArgs $lifecycleEventArgs The event arguments
     */
    public function preRemove(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $entity = $lifecycleEventArgs->getEntity();
        if ($entity instanceof AbstractSession) {
        }
    }
}

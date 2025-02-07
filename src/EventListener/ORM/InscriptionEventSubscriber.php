<?php

namespace App\EventListener\ORM;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Exception\InvalidArgumentException;
use App\Entity\Core\AbstractInscription;

/**
 * Inscription serialization event subscriber.
 */
final class InscriptionEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [['event' => 'serializer.post_serialize', 'method' => 'onPostSerialize']];
    }

    /**
     * On post serialize, add inscription price.
     *
     */
    public function onPostSerialize(ObjectEvent $objectEvent): void
    {
        $inscription = $objectEvent->getObject();
        if ($inscription instanceof AbstractInscription) {
        }
    }
}

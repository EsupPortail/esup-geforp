<?php

namespace App\EventListener;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Exception\InvalidArgumentException;
use App\Entity\Core\AbstractTraining;
use JMS\Serializer\Metadata\StaticPropertyMetadata;

/**
 * Training serialization event subscriber.
 */
final class TrainingEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [['event' => 'serializer.post_serialize', 'method' => 'onPostSerialize']];
    }

    /**
     * On post serialize, add type.
     *
     */
    public function onPostSerialize(ObjectEvent $objectEvent): void
    {
        $training = $objectEvent->getObject();
        if ($training instanceof AbstractTraining) {
            try {
                //$event->getVisitor()->addData('type', $training->getType());
                $objectEvent->getVisitor()->visitProperty(new StaticPropertyMetadata('', 'type', null), $training->getType());
            } catch (InvalidArgumentException) {
                // nothing to do
            }
        }
    }
}

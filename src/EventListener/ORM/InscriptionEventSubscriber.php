<?php

namespace CoreBundle\EventListener\ORM;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Exception\InvalidArgumentException;
use CoreBundle\Entity\AbstractInscription;

/**
 * Inscription serialization event subscriber.
 */
class InscriptionEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            array('event' => 'serializer.post_serialize', 'method' => 'onPostSerialize'),
        );
    }

    /**
     * On post serialize, add inscription price.
     *
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $inscription = $event->getObject();
        if ($inscription instanceof AbstractInscription) {
            try {
            } catch (InvalidArgumentException $e) {
                // nothing to do
            }
        }
    }
}

<?php

namespace App\EventListener\Serializer;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Context;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTraining;

/**
 * Training serialization event subscriber.
 */
class TrainingEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            array(
                'event' => 'serializer.pre_serialize',
                'method' => 'onPreSerialize',
            ),
        );
    }

    /**
     * On API pre serialize, remove unwanted sessions from the training.
     *
     * @param PreSerializeEvent $event
     */
    public function onPreSerialize(PreSerializeEvent $event)
    {
        $training = $event->getObject();
        if ($training instanceof AbstractTraining && self::isApiGroup($event->getContext())) {
            $sessions = $training->getSessions();
            foreach ($sessions as $key => $session) {
                if ($session->isDisplayOnline() === false && $session->getRegistration() !== AbstractSession::REGISTRATION_PRIVATE) {
                    unset($sessions[$key]);
                }
            }
            $training->setSessions(new ArrayCollection(array_values($sessions->toArray())));
        }
    }

    /**
     * @param Context $context
     *
     * @return bool
     */
    public static function isApiGroup(Context $context)
    {
        $groups = $context->getAttribute('groups');
        foreach ($groups as $group) {
        //foreach ($groups->getOrElse(array()) as $group) {
            if ($group === 'api' || strpos($group, 'api.') === 0) {
                return true;
            }
        }

        return false;
    }
}

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
final class TrainingEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [['event' => 'serializer.pre_serialize', 'method' => 'onPreSerialize']];
    }

    /**
     * On API pre serialize, remove unwanted sessions from the training.
     *
     */
    public function onPreSerialize(PreSerializeEvent $preSerializeEvent): void
    {
        $training = $preSerializeEvent->getObject();
        if ($training instanceof AbstractTraining && self::isApiGroup($preSerializeEvent->getContext())) {
            $sessions = $training->getSessions();
            foreach ($sessions as $key => $session) {
                if ($session->isDisplayOnline() === false && $session->getRegistration() !== AbstractSession::REGISTRATION_PRIVATE) {
                    unset($sessions[$key]);
                }
            }

            $training->setSessions(new ArrayCollection(array_values($sessions->toArray())));
        }
    }

    public static function isApiGroup(Context $context): bool
    {
        $groups = $context->getAttribute('groups');
        foreach ($groups as $group) {
            //foreach ($groups->getOrElse(array()) as $group) {
            //Changement de conditionnel et ajout de la méthode pour vérifier si la chaine commence par une sous-chaine,
            if ($group === 'api') {
                return true;
            }
            if (str_starts_with((string) $group, 'api.')) {
                return true;
            }
        }

        return false;
    }
}

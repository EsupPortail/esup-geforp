<?php

namespace App\EventListener\Serializer;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use App\Entity\Core\AbstractInscription;
use App\Entity\Core\AbstractTrainee;

/**
 * Trainee serialization event subscriber.
 */
final class TraineeEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [['event' => 'serializer.post_serialize', 'method' => 'onPostSerialize']];
    }

    /**
     * On api.profile post serialize, add some data to the trainee.
     *
     */
    public function onPostSerialize(ObjectEvent $objectEvent): void
    {
        //$groups = $event->getContext()->attributes->get('groups');
        if ($objectEvent->getContext()->hasAttribute('groups')) {
            $groups = $objectEvent->getContext()->getAttribute('groups');
            $trainee = $objectEvent->getObject();
            if ($trainee instanceof AbstractTrainee && in_array('api.token', $groups, true)) {
                $inscriptions = [];
                /** @var AbstractInscription $inscription */
                foreach ($trainee->getInscriptions() as $inscription) {
                    $inscriptions[] = ['id' => $inscription->getId(), 'session' => $inscription->getSession()->getId(), 'inscriptionStatus' => $inscription->getInscriptionstatus()->getId()];
                }

                $objectEvent->getVisitor()->getResult('registrations', $inscriptions);
            }
        }
    }
}

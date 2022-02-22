<?php

namespace App\EventListener\Serializer;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use App\Entity\Core\AbstractInscription;
use App\Entity\Core\AbstractTrainee;

/**
 * Trainee serialization event subscriber.
 */
class TraineeEventSubscriber implements EventSubscriberInterface
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
     * On api.profile post serialize, add some data to the trainee.
     *
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $groups = $event->getContext()->attributes->get('groups');
        $trainee = $event->getObject();
        if ($trainee instanceof AbstractTrainee && in_array('api.token', (array) $groups->getOrElse(array()), true)) {
            $inscriptions = array();
            /** @var AbstractInscription $inscription */
            foreach ($trainee->getInscriptions() as $inscription) {
                $inscriptions[] = array(
                    'id' => $inscription->getId(),
                    'session' => $inscription->getSession()->getId(),
                    'inscriptionStatus' => $inscription->getInscriptionStatus()->getId(),
                );
            }
            $event->getVisitor()->addData('registrations', $inscriptions);
        }
    }
}

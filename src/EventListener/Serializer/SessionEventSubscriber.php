<?php

namespace App\EventListener\Serializer;

use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use App\Entity\Core\AbstractSession;
use App\EventListener\Serializer\TrainingEventSubscriber;

/**
 * Session serialization event subscriber.
 */
class SessionEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            array('event' => 'serializer.pre_serialize', 'method' => 'onPreSerialize'),
        );
    }

    /**
     * On API pre serialize, add allMaterial property.
     *
     * @param PreSerializeEvent $event
     */
    public function onPreSerialize(PreSerializeEvent $event)
    {
        $allMaterials = new ArrayCollection();
        /** @var AbstractSession $session */
        $session = $event->getObject();
        if ($session instanceof AbstractSession && TrainingEventSubscriber::isApiGroup($event->getContext())) {
            $training = $session->getTraining();
            foreach ($session->getMaterials() as $material) {
                $allMaterials->add($material);
            }
            foreach ($training->getMaterials() as $material) {
                $allMaterials->add($material);
            }
            $session->setAllMaterials($allMaterials);
        }
    }
}

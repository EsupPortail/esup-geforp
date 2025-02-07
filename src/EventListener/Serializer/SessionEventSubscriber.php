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
final class SessionEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [['event' => 'serializer.pre_serialize', 'method' => 'onPreSerialize']];
    }

    /**
     * On API pre serialize, add allMaterial property.
     *
     */
    public function onPreSerialize(PreSerializeEvent $preSerializeEvent): void
    {
        $allMaterials = new ArrayCollection();
        /** @var AbstractSession $session */
        $session = $preSerializeEvent->getObject();
        if ($session instanceof AbstractSession && TrainingEventSubscriber::isApiGroup($preSerializeEvent->getContext())) {
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

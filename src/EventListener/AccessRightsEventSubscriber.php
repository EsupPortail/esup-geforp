<?php

namespace App\EventListener;

use JMS\Serializer\Context;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use App\AccessRight\SerializedAccessRights;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class AccessRightsEventSubscriber.
 */
final class AccessRightsEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public function __construct(protected AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [['event' => 'serializer.post_serialize', 'method' => 'onPostSerialize']];
    }

    /**
     * If the object is a instance of SerializedAccessRights, add access rights to the
     * serialized object.
     *
     */
    public function onPostSerialize(ObjectEvent $objectEvent): void
    {
        if (!$this->isApiGroup($objectEvent->getContext())) {
            $object = $objectEvent->getObject();
            if ($object instanceof SerializedAccessRights) {
                //$event->getVisitor()->addData('_accessRights', array(
                $objectEvent->getVisitor()->visitProperty(new StaticPropertyMetadata('', '_accessRights', null), ['view' => $this->authorizationChecker->isGranted('VIEW', $object), 'edit' => $this->authorizationChecker->isGranted('EDIT', $object), 'delete' => $this->authorizationChecker->isGranted('DELETE', $object)]);
            }
        }
    }

    private function isApiGroup(Context $context): bool
    {
//        $groups = $context->attributes->get('groups');
//        foreach ($groups->getOrElse(array()) as $group) {
       if ($context->hasAttribute('groups')) {
           $groups = $context->getAttribute('groups');
           foreach ($groups as $group) {
               if ($group === 'api') {
                   return true;
               }
               if (str_starts_with((string) $group, 'api.')) {
                   return true;
               }
           }
       }

        return false;
    }
}

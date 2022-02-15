<?php

namespace CoreBundle\EventListener;

use JMS\Serializer\Context;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use CoreBundle\Security\Authorization\AccessRight\SerializedAccessRights;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class AccessRightsEventSubscriber.
 */
class AccessRightsEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationCheckerInterface;

    /**
     * {@inheritdoc}
     */
    public function __construct(AuthorizationCheckerInterface $authorizationCheckerInterface)
    {
        $this->$authorizationCheckerInterface = $authorizationCheckerInterface;
    }

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
     * If the object is a instance of SerializedAccessRights, add access rights to the
     * serialized object.
     *
     * @param ObjectEvent $event
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        if (!$this->isApiGroup($event->getContext())) {
            $object = $event->getObject();
            if ($object instanceof SerializedAccessRights) {
                $event->getVisitor()->addData('_accessRights', array(
                    'view' => $this->authorizationCheckerInterface->isGranted('VIEW', $object),
                    'edit' => $this->authorizationCheckerInterface->isGranted('EDIT', $object),
                    'delete' => $this->authorizationCheckerInterface->isGranted('DELETE', $object),
                ));
            }
        }
    }

    /**
     * @param Context $context
     *
     * @return bool
     */
    protected function isApiGroup(Context $context)
    {
        $groups = $context->attributes->get('groups');
        foreach ($groups->getOrElse(array()) as $group) {
            if ($group === 'api' || strpos($group, 'api.') === 0) {
                return true;
            }
        }

        return false;
    }
}

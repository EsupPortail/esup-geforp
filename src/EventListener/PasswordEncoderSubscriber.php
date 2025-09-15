<?php

namespace App\EventListener;

use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

/**
 * Class TraineeListener.
 */
final class PasswordEncoderSubscriber implements EventSubscriber
{
    /**
     * @var PasswordHasherFactoryInterface
     */
    private PasswordHasherFactoryInterface $encoderFactory;

    /**
     * TraineeListener constructor.
     *
     * @param PasswordHasherFactoryInterface $encoderFactory
     */
    public function __construct(PasswordHasherFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * Returns hash of events, that this listener is bound to.
     *
     */
    public function getSubscribedEvents(): array
    {
        return [Events::prePersist, Events::preUpdate];
    }

    public function prePersist(PostPersistEventArgs $args): void
    {
        $this->preUpdate($args);
    }

    public function preUpdate(PostPersistEventArgs $args): void
    {
        $object = $args->getObject();
        if (!($object instanceof UserInterface)) {
            return;
        }

        $plainPassword = $object->getPassword();
        if (!empty($plainPassword)) {
            $encoder = $this->encoderFactory->getEncoder($object);
            $object->setPassword($encoder->encodePassword($plainPassword, $object->getSalt()));
            $object->eraseCredentials();
        }
    }
}

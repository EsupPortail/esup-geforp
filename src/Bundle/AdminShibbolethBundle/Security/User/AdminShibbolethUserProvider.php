<?php

namespace App\Bundle\AdminShibbolethBundle\Security\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Persistence\ManagerRegistry;
use App\Bundle\AdminShibbolethBundle\Security\User\AdminShibbolethUserProviderInterface;
use App\Entity\Core\User;

class AdminShibbolethUserProvider implements AdminShibbolethUserProviderInterface
{
    private $registry;

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function loadUserByUsername($login)
    {
        $entityManager = $this->registry->getManagerForClass(User::class);
        $user = $entityManager->getRepository(User::class)->findOneBy(['username' => $login]);
        return $user;
    }

    public function loadUser($credentials)
    {
        return $this->loadUserByUsername($credentials['username']);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return User::class === $class;
    }
}

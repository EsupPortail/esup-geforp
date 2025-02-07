<?php

namespace App\Security;

use App\Entity\Core\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AdminShibbolethUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager)
    {
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function loadUserByEppn(string $eppn): UserInterface
    {
        // TODO: Implement loadUserByUsername() method.
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['username' => $eppn]);

        if (!$user) {
            throw new UserNotFoundException(sprintf('User with eppn "%s" not found.', $eppn));
        }

        return $user;
    }


    public function loadUserByIdentifier($identifier): UserInterface
    {
	 $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['username' => $identifier]);
dump($identifier);
        return $user;
    }

    public function supportsClass($class): bool
    {
        return User::class === $class;
    }

}


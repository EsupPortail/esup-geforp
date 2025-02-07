<?php

namespace App\Bundle\AdminShibbolethBundle\Security\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Persistence\ManagerRegistry;
use App\Bundle\AdminShibbolethBundle\Security\User\AdminShibbolethUserProviderInterface;
use App\Entity\Core\User;

final readonly class AdminShibbolethUserProvider implements AdminShibbolethUserProviderInterface
{
    public function __construct(private ManagerRegistry $managerRegistry)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
dump($identifier);
        $entityManager = $this->managerRegistry->getManagerForClass(User::class);
        $us =  $entityManager->getRepository(User::class)->findOneBy(['username' => $identifier]);
	dump($us);
        return $entityManager->getRepository(User::class)->findOneBy(['username' => $identifier]);
    }

    public function loadUser($credentials): UserInterface
    {
        return $this->loadUserByIdentifier($credentials['username']);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', $user::class)
            );
        }

        return $this->loadUserByIdentifier(getUserIdentifier());
    }

    public function supportsClass($class): bool
    {
        return User::class === $class;
    }
}

<?php

namespace App\Bundle\ShibbolethBundle\Security\User;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Bundle\ShibbolethBundle\Security\User\ShibbolethUserProviderInterface;
use App\Bundle\ShibbolethBundle\Security\ShibbolethGuardAuthenticator;

final class ShibbolethUserProvider implements ShibbolethUserProviderInterface
{

    public function loadUserByIdentifier($identifier): ShibbolethUser
    {
	$credentials = [];
        $roles = [];
        return new ShibbolethUser($identifier, $credentials, $roles);
    }

    public function loadUser($credentials): ShibbolethUser
    {
        if (!isset($credentials['username'])) {
            throw new \InvalidArgumentException('Username not provided.');
        }
        $roles = [];

        return new ShibbolethUser($credentials['username'], $credentials, $roles);
    }

    public function refreshUser(UserInterface $user): ShibbolethUser
    {
        if (!$user instanceof ShibbolethUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', $user::class)
            );
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass($class): bool
    {
        return ShibbolethUser::class === $class;
    }
}

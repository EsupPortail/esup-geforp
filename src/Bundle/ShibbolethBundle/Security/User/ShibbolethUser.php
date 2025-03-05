<?php

namespace App\Bundle\ShibbolethBundle\Security\User;

use App\Entity\Core\User;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

final class ShibbolethUser implements UserInterface, EquatableInterface, \Stringable
{
    public function __construct(private $username, private readonly array $credentials, private array $roles)
    {
    }

    public function setRoles($roles): void
    {
        $this->roles[] = $roles;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
dump($roles);
        // guarantees that a user always has at least one role for security
        $roles[] = 'ROLE_SHIB_AUTHENTICATED';

        $this->roles = array_unique($roles);
        return $this->roles;
    }

    public function getPassword(): null
    {
        return null;
    }

    public function getSalt(): null
    {
        return null;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getCredentials(): array
    {
        return $this->credentials;
    }

    public function eraseCredentials(): void
    {
    }

    public function isEqualTo(UserInterface $user): bool
    {
        if (!$user instanceof ShibbolethUser) {
            return false;
        }
        return $this->username === $user->getUserIdentifier();
    }

    public function __toString(): string
    {
        return $this->username;
    }

    public function getUserIdentifier(): string
    {
        return $this->username;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: denoix
 * Date: 29/03/18
 * Time: 13:39
 */

namespace App\Bundle\ShibbolethBundle\Security\User;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;

class ShibbolethUser implements UserInterface, EquatableInterface
{
    private $username;
    private $password;
    private $salt;
    private $roles;
    private $credentials;

    public function __construct($username, $password, $salt, array $credentials, array $roles)
    {
        $this->username = $username;
        $this->password = $password;
        $this->salt = $salt;
        $this->roles = $roles;
        $this->credentials = $credentials;
    }

    public function getRoles()
    {
        $roles = $this->roles;

        // guarantees that a user always has at least one role for security
        $roles[] = 'ROLE_SHIB_AUTHENTICATED';

        $this->roles = array_unique($roles);
        return $this->roles;
    }

    public function getPassword()
    {
        return null;
    }

    public function getSalt()
    {
        return null;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getCredentials()
    {
        return $this->credentials;
    }

    public function eraseCredentials()
    {
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof ShibbolethUser) {
            return false;
        }


        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }

    public function __toString()
    {
        return (string) $this->getUsername();
    }
}
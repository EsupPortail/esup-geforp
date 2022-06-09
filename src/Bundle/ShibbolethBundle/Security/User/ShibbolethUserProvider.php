<?php
/**
 * Created by PhpStorm.
 * User: denoix
 * Date: 29/03/18
 * Time: 13:43
 */

namespace App\Bundle\ShibbolethBundle\Security\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Bundle\ShibbolethBundle\Security\User\ShibbolethUserProviderinterface;


class ShibbolethUserProvider implements ShibbolethUserProviderinterface
{

    public function loadUserByUsername($login)
    {
        $roles = array();
        return new ShibbolethUser($login, '', '', array(), $roles);
    }

    public function loadUser($credentials)
    {
        $roles = array();
        return new ShibbolethUser($credentials['username'], '', '', $credentials, $roles);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof ShibbolethUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return ShibbolethUser::class === $class;
    }
}
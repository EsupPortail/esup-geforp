<?php
/**
 * Created by PhpStorm.
 * User: denoix
 * Date: 29/03/18
 * Time: 13:43
 */

namespace App\Bundle\AdminShibbolethBundle\Security\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Bundle\AdminShibbolethBundle\Security\User\ShibbolethUserProviderInterface;
use App\Entity\Core\User;
use Doctrine\ORM\EntityRepository;

class ShibbolethUserProvider implements ShibbolethUserProviderInterface
{

    public function loadUserByUsername($login)
    {
/*        $roles = array();
        return new ShibbolethUser($login, '', '', array(), $roles);*/

        return $this->createQueryBuilder('u')
            ->where('u.username = :username')
            ->setParameter('username', $login)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function loadUser($credentials)
    {
/*        $roles = array();
        return new ShibbolethUser($credentials['username'], '', '', $credentials, $roles);*/
        return $this->createQueryBuilder('u')
            ->where('u.username = :username')
            ->setParameter('username', $credentials['username'])
            ->getQuery()
            ->getOneOrNullResult();
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
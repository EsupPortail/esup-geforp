<?php
/**
 * Created by PhpStorm.
 * User: denoix
 * Date: 29/03/18
 * Time: 13:43
 */

namespace App\Bundle\ShibbolethBundle\Security\AdminUser;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Bundle\ShibbolethBundle\Security\AdminUser\ShibbolethAdminUserProviderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Entity\Core\User;

class ShibbolethAdminUserProvider extends ServiceEntityRepository implements ShibbolethAdminUserProviderInterface
{

    public function loadUserByUsername($login)
    {
        $entityManager = $this->getEntityManager();

        return $entityManager->createQuery(
            'SELECT u
                FROM App\Entity\Core\User u
                WHERE u.username = :query'
        )
            ->setParameter('query', $login)
            ->getOneOrNullResult();
    }

    public function loadUser($credentials)
    {
        $entityManager = $this->getEntityManager();

        return $entityManager->createQuery(
            'SELECT u
                FROM App\Entity\Core\User u
                WHERE u.username = :query'
        )
            ->setParameter('query', $credentials['username'])
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
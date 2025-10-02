<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException; // remplacer par userNotFoundException
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class AccountRepository.
 *
 * @see http://symfony.com/fr/doc/current/cookbook/security/entity_provider.html
 */
final class AccountRepository extends EntityRepository implements UserProviderInterface
{
    /**
     * @var string
     */
    private const CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    /**
     * @param $identifier
     *
     * @throws UserNotFoundException
     *
     * @throws NonUniqueResultException
     *
     * @return mixed
     */
    public function loadUserByIdentifier($identifier): UserInterface
    {
        $query = $this
          ->createQueryBuilder('t')
          ->where('LOWER(t.email) = LOWER(:email)')
          ->setParameter('email', $identifier)
          ->getQuery();

        try {
            $user = $query->getSingleResult();
        } catch (NoResultException $noResultException) {
            $message = sprintf(
                'Unable to find an active trainee identified by "%s".',
                $identifier
            );
            throw new UserNotFoundException($message, 0, $noResultException);
        } catch (NonUniqueResultException $nonUniqueResultException) {
            $message = sprintf('Multiple users found with the identifier "%s".', $identifier);
            throw new NonUniqueResultException($message, 0, $nonUniqueResultException);
        }

        return $user;
    }

    /**
     *
     * @throws UnsupportedUserException
     *
     * @return object
     */
    public function refreshUser(UserInterface $user): \Symfony\Component\Security\Core\User\UserInterface
    {
        $class = $user::class;
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }

        return $this->find($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        if ($this->getEntityName() === $class) {
            return true;
        }
        return is_subclass_of($class, $this->getEntityName());
    }

    /**
     * Generate a password.
     *
     *
     */
    public static function generatePassword(int $length = 12): string
    {
        $count = mb_strlen((string) self::CHARS);

        for ($i = 0, $result = ''; $i < $length; ++$i) {
            $index = random_int(0, $count - 1);
            $result .= mb_substr((string) self::CHARS, $index, 1);
        }

        return $result;
    }
}

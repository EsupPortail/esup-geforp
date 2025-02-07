<?php

namespace App\Repository;

use App\Entity\Term\Publictype;
use App\Entity\Term\Theme;
use App\Entity\Term\Title;
use App\Entity\Back\Institution;
use App\Entity\Back\Internship;
use App\Entity\Back\Organization;
use App\Entity\Back\Participation;
use App\Entity\Back\Trainer;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class TraineeRepository.
 *
 * @see http://symfony.com/fr/doc/current/cookbook/security/entity_provider.html
 */
final class TraineeRepository extends EntityRepository implements UserProviderInterface
{
    /**
     * @var string
     */
    private const CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    /**
     *
     * @throws UserNotFoundException
     *
     * @throws NonUniqueResultException
     * @return mixed
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $query = $this
          ->createQueryBuilder('t')
          ->where('t.email = :email')
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
        if ( ! $this->supportsClass($class)) {
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
    public static function generatePassword(int $length = 8): string
    {
        $count = mb_strlen((string) self::CHARS);

        for ($i = 0, $result = ''; $i < $length; ++$i) {
            $index = random_int(0, $count - 1);
            $result .= mb_substr((string) self::CHARS, $index, 1);
        }

        return $result;
    }

}

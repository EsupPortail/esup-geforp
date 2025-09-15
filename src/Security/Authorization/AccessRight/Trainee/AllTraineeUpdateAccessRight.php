<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 20/03/14
 * Time: 16:46.
 */
namespace App\Security\Authorization\AccessRight\Trainee;

use App\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class AllTraineeUpdateAccessRight extends AbstractAccessRight
{
    public function getLabel(): string
    {
        return 'Modifier les stagiaires de tous les établissements';
    }

    /**
     * Checks if the access right supports the given class.
     *
     * @param string
     *
     */
    public function supportsClass($class): bool
    {
        if ($class === \App\Entity\Back\Trainee::class) {
            return true;
        }

        return false;
    }

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $attribute = null, $object = null): bool
    {
        return $attribute === 'EDIT';
    }
}

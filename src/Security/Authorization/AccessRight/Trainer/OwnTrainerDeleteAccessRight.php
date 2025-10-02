<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 17/07/14
 * Time: 11:50.
 */
namespace App\Security\Authorization\AccessRight\Trainer;

use App\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class OwnTrainerDeleteAccessRight extends AbstractAccessRight
{
    public function getLabel(): string
    {
        return 'Supprimer les formateurs de son propre centre';
    }

    /**
     * Checks if the access right supports the given class.
     *
     * @param string
     *
     */
    public function supportsClass($class): bool
    {
        return $class === \App\Entity\Back\Trainer::class;
    }

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $object = null, $attribute): bool
    {
        if ($attribute !== 'DELETE') return false;

        if ($object) {
            return $object->getOrganization() === $token->getUser()->getOrganization();
        }

        return true;
    }
}

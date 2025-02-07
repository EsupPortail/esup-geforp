<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 17/07/14
 * Time: 11:51.
 */
namespace App\Security\Authorization\AccessRight\Trainer;

use App\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class AllTrainerUpdateAccessRight extends AbstractAccessRight
{
    public function getLabel(): string
    {
        return 'Modification des formateurs de tous les centres';
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
    public function isGranted(TokenInterface $token, $attribute = null, $object = null): bool
    {
        return $attribute === 'EDIT';
    }
}

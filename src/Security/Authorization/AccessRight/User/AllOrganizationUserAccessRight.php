<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 20/03/14
 * Time: 16:46.
 */
namespace App\Security\Authorization\AccessRight\User;

use App\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class AllOrganizationUserAccessRight extends AbstractAccessRight
{
    public function getLabel(): string
    {
        return 'Gestion des utilisateurs de tous les centres';
    }

    /**
     * Checks if the access right supports the given class.
     *
     * @param string
     *
     */
    public function supportsClass($class): bool
    {
        return \App\Entity\Core\User::class === $class;
    }

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $attribute = null, $object = null): true
    {
        return true;
    }

}

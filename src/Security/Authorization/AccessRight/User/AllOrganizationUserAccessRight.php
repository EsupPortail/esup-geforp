<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 20/03/14
 * Time: 16:46.
 */

namespace App\Security\Authorization\AccessRight\User;

use App\Security\Authorization\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use App\Entity\User;

class AllOrganizationUserAccessRight extends AbstractAccessRight
{
    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Gestion des utilisateurs de tous les centres';
    }

    /**
     * Checks if the access right supports the given class.
     *
     * @param string
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return User::class === $class;
    }

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $object = null, $attribute)
    {
        return true;
    }
}

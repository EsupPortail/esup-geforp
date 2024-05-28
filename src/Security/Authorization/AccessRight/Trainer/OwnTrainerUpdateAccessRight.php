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

class OwnTrainerUpdateAccessRight extends AbstractAccessRight
{
    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Modifier les formateurs de son propre centre';
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
        if ($class === 'App\Entity\Back\Trainer') {
            return true;
        }

        return false;
    }

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $object = null, $attribute)
    {
        if ($attribute !== 'EDIT') return false;
        if ($object) {
            return $object->getOrganization() === $token->getUser()->getOrganization();
        } else {
            return true;
        }
    }
}

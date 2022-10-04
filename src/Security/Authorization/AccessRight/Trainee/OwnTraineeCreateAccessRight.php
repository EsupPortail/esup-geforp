<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 20/03/14
 * Time: 15:42.
 */
namespace App\Security\Authorization\AccessRight\Trainee;

use App\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OwnTraineeCreateAccessRight extends AbstractAccessRight
{
    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Création des stagiaires de son propre centre';
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
        if ($class === 'App\Entity\Back\Trainee') {
            return true;
        }
    }

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $object = null, $attribute)
    {
        if ($attribute !== 'CREATE') return false;
        if ($object) {
            return $object->getOrganization() === $token->getUser()->getOrganization();
        } else {
            return true;
        }
    }
}

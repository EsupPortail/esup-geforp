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

class OwnTraineeUpdateAccessRight extends AbstractAccessRight
{
    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Modifier les stagiaires de son propre établissement';
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
        if ($attribute !== 'EDIT') return false;
        if ($object) {
            $ownInst= $token->getUser()->getOrganization()->getInstitution();
            if ($object->getInstitution() === $ownInst)
                return true;

            $visuInst = $token->getUser()->getOrganization()->getInstitution()->getVisuinstitutions();
            foreach($visuInst as $inst) {
                if ($object->getInstitution() === $inst)
                    return true;
            }

            return false;
        } else {
            return true;
        }
    }
}

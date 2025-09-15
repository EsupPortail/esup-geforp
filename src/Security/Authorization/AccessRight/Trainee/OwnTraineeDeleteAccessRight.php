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

final class OwnTraineeDeleteAccessRight extends AbstractAccessRight
{
    public function getLabel(): string
    {
        return 'Suppression des stagiaires de son propre établissement';
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
        if ($attribute !== 'DELETE') return false;

        if ($object) {
            $ownInst= $token->getUser()->getOrganization()->getInstitution();
            if ($object->getInstitution() === $ownInst)
                return true;

            $visuInst = $token->getUser()->getOrganization()->getInstitution()->getVisuinstitutions();
            return in_array($object->getInstitution(), $visuInst, true);
        }
        return true;
    }
}

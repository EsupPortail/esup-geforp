<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 20/03/14
 * Time: 15:42.
 */
namespace App\Security\Authorization\AccessRight\Inscription;

use App\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class OwnInscriptionCreateAccessRight extends AbstractAccessRight
{
    public function getLabel(): string
    {
        return 'Créer les inscriptions aux formations de son propre centre';
    }

    /**
     * Checks if the access right supports the given class.
     *
     * @param string
     *
     */
    public function supportsClass($class): bool
    {
        return $class === \App\Entity\Back\Inscription::class;
    }

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $attribute = null, $object = null): bool
    {
        if ($attribute !== 'CREATE') {
            return false;
        }

        if ($object) {
            return $object->getSession()->getTraining()->getOrganization() === $token->getUser()->getOrganization();
        }

        return true;
    }
}

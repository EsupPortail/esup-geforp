<?php

namespace App\Security\Authorization\AccessRight\User;

use App\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class OwnOrganizationUserAccessRight extends AbstractAccessRight
{
    public function getLabel(): string
    {
        return 'Gestion des utilisateurs de son propre centre';
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
     *
     * La signature de la méthode doit respecter l'ordre des paramètres :
     * TokenInterface $token, $object (optionnel), $attribute
     */
    public function isGranted(TokenInterface $token, $object = null, $attribute): bool
    {
        if ($object) {
            // Vérifie si l'organisation de l'objet correspond à celle de l'utilisateur authentifié
            return $object->getOrganization() === $token->getUser()->getOrganization();
        }

        // Si aucun objet n'est fourni, l'accès est accordé par défaut
        return true;
    }
}

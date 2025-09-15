<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 20/03/14
 * Time: 15:42.
 */
namespace App\Security\Authorization\AccessRight\Training;

use App\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class OwnTrainingDeleteAccessRight extends AbstractAccessRight
{
    public function getLabel(): string
    {
        return 'Supprimer les formations de son propre centre';
    }

    /**
     * Checks if the access right supports the given class.
     *
     * @param string
     *
     */
    public function supportsClass($class): bool
    {
        if ($class === \App\Entity\Back\Internship::class) {
            return true;
        }

        if ($class === \App\Entity\Back\Session::class) {
            return true;
        }

        try {
            $reflectionClass = new \ReflectionClass($class);

            return $reflectionClass->isSubclassOf(\App\Entity\Back\Internship::class);
        } catch (\ReflectionException){
            return false;
        }
    }

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $attribute = null, $object = null): bool
    {
        if ($attribute !== 'DELETE') return false;

        if (!$object) {
            return true;
        }

        if (method_exists($object, 'getOrganization')) {
            return $object->getOrganization() === $token->getUser()->getOrganization();
        }

        return $object->getTraining()->getOrganization() === $token->getUser()->getOrganization();
    }
}

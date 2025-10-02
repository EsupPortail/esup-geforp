<?php

namespace App\Security\Authorization\AccessRight\Vocabulary;

use App\Vocabulary\VocabularyInterface;
use App\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class NationalVocabularyAccessRight extends AbstractAccessRight
{
    public function getLabel(): string
    {
        return 'Gestion des vocabulaires nationaux';
    }

    /**
     * Checks if the access right supports the given class.
     *
     * @param string
     *
     */
    public function supportsClass($class): bool
    {
        if ($class === \App\Vocabulary\VocabularyInterface::class) {
            return true;
        }

        try {
            $reflectionClass = new \ReflectionClass($class);

            return $reflectionClass->isSubclassOf(\App\Entity\Term\VocabularyInterface::class);
        }
        catch (\ReflectionException) {
            return false;
        }
    }

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $object = null, $attribute): bool
    {
        if (!is_object($object)) {
            return false;
        }

        if ($object->getVocabularyStatus() === VocabularyInterface::VOCABULARY_NATIONAL) {
            return true;
        }

            return !$object->getOrganization();
        }

}

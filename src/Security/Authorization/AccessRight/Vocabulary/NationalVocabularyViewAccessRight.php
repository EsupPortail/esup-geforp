<?php

namespace App\Security\Authorization\AccessRight\Vocabulary;

use App\Entity\Term\VocabularyInterface;
use App\Security\Authorization\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class NationalVocabularyViewAccessRight extends AbstractAccessRight
{
    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Voir les vocabulaires de tous les centres';
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
        if ($class === VocabularyInterface::class) {
            return true;
        }

        try {
            $refl = new \ReflectionClass($class);

            return $refl->isSubclassOf(VocabularyInterface::class);
        } catch (\ReflectionException $re) {
            return false;
        }
    }

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $object = null, $attribute)
    {
        if (is_string($token)) {
            return $attribute === 'VIEW';
        } elseif ($object) {
            return $attribute === 'VIEW' && (
                $object->getVocabularyStatus() === VocabularyInterface::VOCABULARY_NATIONAL ||
                ($object->getVocabularyStatus() !== VocabularyInterface::VOCABULARY_NATIONAL && !$object->getOrganization()));
        } else {
            return $attribute === 'VIEW';
        }
    }
}

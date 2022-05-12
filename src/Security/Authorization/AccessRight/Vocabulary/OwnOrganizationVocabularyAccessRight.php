<?php

namespace App\Security\Authorization\AccessRight\Vocabulary;

use App\Vocabulary\VocabularyInterface;
use App\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OwnOrganizationVocabularyAccessRight extends AbstractAccessRight
{
    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Gestion des vocabulaires locaux de son propre centre';
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
        if ($class === 'App\Vocabulary\VocabularyInterface') {
            return true;
        }

        try {
            $refl = new \ReflectionClass($class);

            return $refl->isSubclassOf('App\Vocabulary\VocabularyInterface');
        }
        catch (\ReflectionException $re) {
            return false;
        }

        return false;
    }

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $object = null, $attribute)
    {
        if (is_string($object)) {
            return true;
        }
        else if ($object) {
            return $object->getVocabularyStatus() !== VocabularyInterface::VOCABULARY_NATIONAL && $object->getOrganization() === $token->getUser()->getOrganization();
        }
        else {
            return true;
        }
    }
}

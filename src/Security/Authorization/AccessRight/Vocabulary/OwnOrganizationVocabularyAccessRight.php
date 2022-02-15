<?php

namespace App\Security\Authorization\AccessRight\Vocabulary;

use App\Security\Authorization\AccessRight\AbstractAccessRight;
use App\Entity\Term\VocabularyInterface;
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
        if ($class === VocabularyInterface::class) {
            return true;
        }

        try {
            $refl = new \ReflectionClass($class);

            return $refl->isSubclassOf(VocabularyInterface::class);
        } catch (\ReflectionException $re) {
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
        } elseif ($object) {
            if ($object->getVocabularyStatus() !== VocabularyInterface::VOCABULARY_NATIONAL) {
                if (!$object->getOrganization()) {
                    return false;
                }

                return $object->getOrganization()->getId() === $token->getUser()->getOrganization()->getId();
            }

            return false;
        } else {
            return true;
        }
    }
}

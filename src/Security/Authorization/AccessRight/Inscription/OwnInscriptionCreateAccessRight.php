<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 20/03/14
 * Time: 15:42.
 */

namespace Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\Inscription;

use Sygefor\Bundle\CoreBundle\Entity\AbstractInscription;
use Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OwnInscriptionCreateAccessRight extends AbstractAccessRight
{
    protected $supportedClass = AbstractInscription::class;
    protected $supportedOperation = 'CREATE';

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Créer les inscriptions aux formations de son propre centre';
    }

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $object = null, $attribute)
    {
        if ($attribute !== $this->supportedOperation) {
            return false;
        }
        if ($object) {
            return $object->getSession()->getTraining()->getOrganization()->getId() === $token->getUser()->getOrganization()->getId();
        }

        return true;
    }
}

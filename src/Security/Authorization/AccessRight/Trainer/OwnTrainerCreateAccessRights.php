<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 17/07/14
 * Time: 11:50.
 */

namespace Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\Trainer;

use Sygefor\Bundle\CoreBundle\Entity\AbstractTrainer;
use Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OwnTrainerCreateAccessRights extends AbstractAccessRight
{
    protected $supportedClass = AbstractTrainer::class;
    protected $supportedOperation = 'CREATE';

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Créer les intervenants de son propre centre';
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
            return $object->getOrganization()->getId() === $token->getUser()->getOrganization()->getId();
        } else {
            return true;
        }
    }
}

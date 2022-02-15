<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 20/03/14
 * Time: 15:42.
 */

namespace Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\Trainee;

use Sygefor\Bundle\CoreBundle\Entity\AbstractTrainee;
use Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OwnTraineeDeleteAccessRight extends AbstractAccessRight
{
    protected $supportedClass = AbstractTrainee::class;
    protected $supportedOperation = 'DELETE';

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Suppression des stagiaires de son propre centre';
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
            return $object->getOrganization() && $object->getOrganization()->getId() === $token->getUser()->getOrganization()->getId();
        } else {
            return true;
        }
    }
}

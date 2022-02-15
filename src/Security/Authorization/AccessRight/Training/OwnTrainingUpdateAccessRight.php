<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 20/03/14
 * Time: 15:42.
 */

namespace Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\Training;

use Sygefor\Bundle\CoreBundle\Entity\AbstractSession;
use Sygefor\Bundle\CoreBundle\Entity\AbstractTraining;
use Sygefor\Bundle\CoreBundle\Model\SemesteredTraining;
use Sygefor\Bundle\CoreBundle\Security\Authorization\AccessRight\AbstractAccessRight;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class OwnTrainingUpdateAccessRight extends AbstractAccessRight
{
    protected $supportedClass = array(
        AbstractTraining::class,
        AbstractSession::class,
        SemesteredTraining::class,
    );
    protected $supportedOperation = 'EDIT';

    /**
     * @return string
     */
    public function getLabel()
    {
        return 'Modifier les formations de son propre centre';
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
            if (method_exists($object, 'getOrganization')) {
                return $object->getOrganization()->getId() === $token->getUser()->getOrganization()->getId();
            } else {
                return $object->getTraining()->getOrganization()->getId() === $token->getUser()->getOrganization()->getId();
            }
        } else {
            return true;
        }
    }
}

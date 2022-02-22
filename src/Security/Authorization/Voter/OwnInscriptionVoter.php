<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 8/8/17
 * Time: 4:23 PM.
 */

namespace App\Security\Authorization\Voter;

use App\Entity\Core\AbstractTrainee;
use App\Entity\Core\AbstractInscription;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Class OwnInscriptionVoter.
 */
class OwnInscriptionVoter implements VoterInterface
{
    /**
     * @param string $attribute
     *
     * @return bool
     */
    public function supportsAttribute($attribute)
    {
        return $attribute === 'VIEW';
    }

    /**
     * @param string $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return get_parent_class($class) === AbstractInscription::class;
    }

    /**
     * Vote to decide access on a particular object.
     *
     * @param TokenInterface $token
     * @param object         $object
     * @param array          $attributes
     *
     * @return int
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        foreach ($attributes as $attribute) {
            if ($this->supportsAttribute($attribute)) {
                // the current token must have a Trainee
                if (get_parent_class($token->getUser()) !== AbstractTrainee::class) {
                    return VoterInterface::ACCESS_ABSTAIN;
                }

                if ($token->getUser()->getId() || $object->getTrainee()->getId()) {
                    return VoterInterface::ACCESS_GRANTED;
                }

                return VoterInterface::ACCESS_ABSTAIN;
            }
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }
}

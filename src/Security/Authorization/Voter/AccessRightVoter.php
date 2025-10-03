<?php

namespace App\Security\Authorization\Voter;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use App\AccessRight\AccessRightRegistry;
use App\Entity\Core\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Class AccessRightVoter.
 */
final readonly class AccessRightVoter implements VoterInterface
{
    /**
     * Construct.
     */
    function __construct(private AccessRightRegistry $accessRightRegistry, private ?EntityManager $entityManager = null)
    {
    }

    /**
     * @param string $attribute
     *
     */
    public function supportsAttribute($attribute): bool
    {
        return true;
    }

    /**
     * @param string $class
     *
     */
    public function supportsClass($class): bool
    {
        return true;
    }

    /**
     * Vote to decide access on a particular object.
     *
     * @param object $object
     *
     */
    public function vote(TokenInterface $token, $object, array $attributes): int
    {
        // the current token must have a User
        if (!($token->getUser() instanceof User)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }
	
        // support of Doctrine namespace alias
        if (is_string($object) && strpos($object, ':') && $this->entityManager instanceof \Doctrine\ORM\EntityManager) {
            [$alias, $class] = explode(':', $object);
            $namespace = $this->entityManager->getConfiguration()->getEntityNamespace($alias);
            $object = $namespace . '\\' . $class;
        }
        // Run overs user access rights
        foreach ($attributes as $attribute) {
            foreach ($token->getUser()->getAccessRights() as $accessRightId) {
                //$className = is_string($object) ? $object : get_class($object);
                $className = is_string($object) ? $object : ClassUtils::getRealClass($object::class);
                //$accessRight = $this->registry->getAccessRightById($accessRightId);
                $id = $this->accessRightRegistry->getByName($accessRightId);
                $accessRight = $this->accessRightRegistry->getAccessRightById($id);
                if (!$accessRight) {
                    continue;
                }
                if (!$accessRight->supportsClass($className)) {
                    continue;
                }
                if (!$accessRight->supportsAttribute($attribute)) {
                    continue;
                }
                if ($accessRight->isGranted($token, is_object($object) ? $object : null, $attribute)) {
                    return VoterInterface::ACCESS_GRANTED;
                }
            }
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }
}

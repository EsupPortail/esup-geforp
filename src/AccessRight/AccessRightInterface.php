<?php

namespace App\AccessRight;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Interface AccessRightInterface.
 */
interface AccessRightInterface
{
    public function getLabel(): string;

    /**
     * Checks if the access right supports the given class.
     *
     * @param string
     *
     */
    public function supportsClass($class): bool;

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $attribute, $object = null);

    public function setId(int $id);

    public function getId(): int;

    /**
     * Checks if the access right supports the given attribute.
     *
     *
     */
    public function supportsAttribute(string $attribute): bool;
}

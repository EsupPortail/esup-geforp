<?php

namespace App\AccessRight;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Class AbstractAccessRight.
 */
abstract class AbstractAccessRight implements AccessRightInterface
{
    private int $id;

    public abstract function getLabel(): string;

    /**
     * Checks if the access right supports the given class.
     *
     * @param string
     *
     */
    public abstract function supportsClass($class): bool;

    /**
     * Returns the vote for the given parameters.
     */
    public abstract function isGranted(TokenInterface $token, $attribute = null, $object = null);

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Checks if the access right supports the given attribute.
     *
     *
     */
    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, ['VIEW', 'EDIT', 'ADD', 'REMOVE', 'CREATE', 'DELETE', 'MANAGEDUPLICATE'], true);
    }
}

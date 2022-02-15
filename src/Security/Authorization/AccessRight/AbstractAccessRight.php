<?php

/**
 * Auteur: Blaise de Carné - blaise@concretis.com.
 */

namespace CoreBundle\Security\Authorization\AccessRight;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Class AbstractAccessRight.
 */
abstract class AbstractAccessRight implements AccessRightInterface
{
    protected $supportedClass = 'Sygefor\Bundle\CoreBundle\Entity\Entity';
    protected $supportedOperation = 'OPERATE';

    /**
     * @var int
     */
    private $id;

    /**
     * @return string
     */
    abstract public function getLabel();

    /**
     * Checks if the access right supports the given class.
     *
     * @param string
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        $classes = array();
        if (!is_array($this->supportedClass)) {
            $classes = [$this->supportedClass];
        } else {
            $classes = $this->supportedClass;
        }

        foreach ($classes as $supportedClass) {
            $parentClass = get_parent_class($class);
            if (!$parentClass) {
                $parentClass = $class;
            }

            if ($parentClass === $supportedClass) {
                return true;
            }
        }
    }

    /**
     * Returns the vote for the given parameters.
     */
    public function isGranted(TokenInterface $token, $object = null, $attribute)
    {
        if ($attribute !== $this->supportedOperation) {
            return false;
        }

        return true;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Checks if the access right supports the given attribute.
     *
     * @param string $attribute
     *
     * @return bool
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute, array('VIEW', 'EDIT', 'ADD', 'REMOVE', 'CREATE', 'DELETE'), true);
    }
}

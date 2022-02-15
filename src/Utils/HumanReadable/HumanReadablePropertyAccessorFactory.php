<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 05/06/14
 * Time: 15:13.
 */

namespace CoreBundle\Utils\HumanReadable;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManager;

/**
 * Class HumanReadablePropertyAccessorFactory.
 */
class HumanReadablePropertyAccessorFactory
{
    protected $termCatalog;

    /** @var EntityManager */
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct($em)
    {
        $this->em = $em;
    }

    /**
     * Factory is given an alternate version of configuration array,
     *  indexed by each entry corresponding className.
     *
     * @param $termCatalog
     */
    public function setTermCatalog($termCatalog)
    {
        foreach ($termCatalog as $confEntry) {
            $class = $this->getClassName($confEntry['class']);
            if (!empty($confEntry['parent']) && !empty($termCatalog[$confEntry['parent']])) {
                $this->termCatalog[$class] = $termCatalog[$confEntry['parent']];
            } else {
                $this->termCatalog[$class] = $confEntry;
            }
        }
    }

    /**
     * @param $class
     *
     * @throws \Exception
     *
     * @return
     */
    public function getTermCatalog($class = null)
    {
        if ($class) {
            if (!isset($this->termCatalog[$this->getClassName($class)])) {
                throw new \Exception('no catalog for this class : '.$class);
            }

            return $this->termCatalog[$this->getClassName($class)];
        }

        return $this->termCatalog;
    }

    /**
     * creates an accessor for the given object.
     *
     * @param $object
     *
     * @return HumanReadablePropertyAccessor
     */
    public function getAccessor($object)
    {
        $propertyAccessor = new HumanReadablePropertyAccessor($object);
        $propertyAccessor->setAccessorFactory($this);

        return $propertyAccessor;
    }

    /**
     * Returns.
     *
     * @param bool $includeExcludedEntities
     *
     * @return array
     */
    public function getKnownEntities($includeExcludedEntities = true)
    {
        $entityTypes = array();
        foreach ($this->termCatalog as $entity) {
            if ($includeExcludedEntities || (!isset($entity['excludeFromFormType']) || $entity['excludeFromFormType'] !== true)) {
                $entityTypes[$entity['class']] = ucfirst($entity['alias']);
            }
        }

        usort($entityTypes, function ($a, $b) {
            return $a > $b;
        });

        $orderedEntityTypes = array();
        foreach ($entityTypes as $label) {
            foreach ($this->termCatalog as $entity) {
                if (ucfirst($entity['alias']) === $label) {
                    $orderedEntityTypes[$entity['class']] = $label;
                    break;
                }
            }
        }

        return $orderedEntityTypes;
    }

    /**
     * Returns true if given class has an entry in term catalog.
     *
     * @param string $className
     *
     * @return bool
     */
    public function hasEntry($className)
    {
        $class = $this->getClassName($className);

        return isset($this->termCatalog[$class]);
    }

    /**
     * @param null $class
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function getEntityAlias($class = null)
    {
        if (!isset($this->termCatalog[$this->getClassName($class)])) {
            throw new \Exception('no catalog for this class : '.$class);
        } elseif (!isset($this->termCatalog[$this->getClassName($class)]['alias'])) {
            return null;
        } else {
            return $this->termCatalog[$this->getClassName($class)]['alias'];
        }
    }

    /**
     * returns the corresponding property for given class/alias, null if not found in catalog.
     *
     * @param $class
     * @param $alias
     *
     * @return string|null
     */
    public function getPropertyForAlias($class, $alias)
    {
        $class = $this->getClassName($class);
        if (isset($this->termCatalog[$class]) && isset($this->termCatalog[$class]['fields'][$alias])) {
            return $this->termCatalog[$class]['fields'][$alias]['property'];
        }

        return null;
    }

    /**
     * Returns mail path for entity if defined, null otherwise.
     *
     * @param $class
     *
     * @return string|null
     */
    public function getMailPath($class)
    {
        $class = $this->getClassName($class);
        if (isset($this->termCatalog[$class])) {
            if (isset($this->termCatalog[$class]['emailPath'])) {
                return $this->termCatalog[$class]['emailPath'];
            } elseif (isset($this->termCatalog[$class]['fields']) &&
                isset($this->termCatalog[$class]['fields']['email'])) {
                return 'email';
            }
        }

        return null;
    }

    /**
     * returns the corresponding format for given class/alias (typically date formats), null if not found in catalog.
     *
     * @param $class
     * @param $alias
     *
     * @return string|null
     */
    public function getFormatForAlias($class, $alias)
    {
        $class = $this->getClassName($class);
        if (isset($this->termCatalog[$class]) &&
            isset($this->termCatalog[$class]['fields'][$alias]) &&
            isset($this->termCatalog[$class]['fields'][$alias]['format'])) {
            return $this->termCatalog[$class]['fields'][$alias]['format'];
        }

        return null;
    }

    /**
     * returns the corresponding type for given class/alias (typically date formats), null if not found in catalog.
     *
     * @param $class
     * @param $alias
     *
     * @return string|null
     */
    public function getTypeForAlias($class, $alias)
    {
        $class = $this->getClassName($class);
        if (isset($this->termCatalog[$class]) &&
            isset($this->termCatalog[$class]['fields'][$alias]) &&
            isset($this->termCatalog[$class]['fields'][$alias]['type'])
        ) {
            return $this->termCatalog[$class]['fields'][$alias]['type'];
        }

        return null;
    }

    /**
     * Provides the class real name (useful for proxy classes).
     *
     * @param $className
     *
     * @return string
     */
    protected function getClassName($className)
    {
        try {
            $absClassName = $this->em->getClassMetadata($className)->getName();
        } catch (MappingException $e) {
            $absClassName = $className;
        }

        return $absClassName;
    }
}

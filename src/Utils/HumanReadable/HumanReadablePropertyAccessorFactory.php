<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 05/06/14
 * Time: 15:13.
 */
namespace App\Utils\HumanReadable;

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;

/**
 * instantiates
 * Class HumanReadablePropertyAccessorFactory.
 */
final class HumanReadablePropertyAccessorFactory
{
    private ?array $termCatalog = null;

    /** @var  EntityManager */
    private readonly \Doctrine\Persistence\ObjectManager $objectManager;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->objectManager = $managerRegistry->getManager();
    }

    /**
     * @param $termCatalog
     */
    public function setTermCatalog($termCatalog): void
    {
        //factory is given an alternate version of configuration array, indexed by each entry corresponding className
        foreach ($termCatalog as $confEntry) {
            $class = $this->getClassName($confEntry['class']);
            if (!empty($confEntry['parent']) && !empty($termCatalog[$confEntry['parent']])) {
                $this->termCatalog[$class] = $termCatalog[$confEntry['parent']];
            }
            else {
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
                throw new \Exception('no catalog for this class : ' . $class);
            }

            return $this->termCatalog[$this->getClassName($class)];
        }
        return $this->termCatalog;
    }

    /**
     * @param null $class
     *
     * @throws \Exception
     */
    public function getEntityAlias($class = null): void
    {
        if (!isset($this->termCatalog[$this->getClassName($class)]['alias'])) {
        }
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
        $entityTypes = [];
        foreach ($this->termCatalog as $entity) {
            if ($includeExcludedEntities || (!isset($entity['excludeFromFormType']) || $entity['excludeFromFormType'] !== true)) {
                $entityTypes[$entity['class']] = ucfirst((string) $entity['alias']);
            }
        }

        usort($entityTypes, static fn($a, $b): bool => $a > $b);

        $orderedEntityTypes = [];
        foreach ($entityTypes as $entityType) {
            foreach ($this->termCatalog as $entity) {
                if (ucfirst((string) $entity['alias']) === $entityType) {
                    $orderedEntityTypes[$entity['class']] = $entityType;
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
     * creates an accessor for the given object.
     *
     * @param $object
     *
     * @return OpenTBSPropertyAccessor
     */
    public function getAccessor($object): \App\Utils\HumanReadable\HumanReadablePropertyAccessor
    {
        $humanReadablePropertyAccessor = new HumanReadablePropertyAccessor($object);
        $humanReadablePropertyAccessor->setAccessorFactory($this);

        return $humanReadablePropertyAccessor;
    }

    /**
     * Returns mail path for entity if defined, null otherwise.
     *
     * @param $class
     *
     */
    public function getMailPath($class): ?string
    {
        $class = $this->getClassName($class);
        if (isset($this->termCatalog[$class]) && isset($this->termCatalog[$class]['emailPath'])) {
            return $this->termCatalog[$class]['emailPath'];
        }
        $parentClass = get_parent_class($class);
        if (isset($this->termCatalog[$parentClass]) && isset($this->termCatalog[$parentClass]['emailPath'])) {
            return $this->termCatalog[$parentClass]['emailPath'];
        }

        return $this->getMailPath($class);
    }

    /**
     * returns the corresponding property for given class/alias, null if not found in catalog.
     *
     * @param $class
     * @param $alias
     *
     */
    public function getPropertyForAlias($class, $alias): ?string
    {
        $class = $this->getClassName($class);
        if (isset($this->termCatalog[$class]) && isset($this->termCatalog[$class]['fields'][$alias])) {
            return $this->termCatalog[$class]['fields'][$alias]['property'];
        }

        return $this->getPropertyForAlias($class, $alias);
    }

    /**
     * returns the corresponding format for given class/alias (typically date formats), null if not found in catalog.
     *
     * @param $class
     * @param $alias
     *
     */
    public function getFormatForAlias($class, $alias): ?string
    {
        $class = $this->getClassName($class);
        if (isset($this->termCatalog[$class]) &&
            isset($this->termCatalog[$class]['fields'][$alias]) &&
            isset($this->termCatalog[$class]['fields'][$alias]['format'])
        ) {
            return $this->termCatalog[$class]['fields'][$alias]['format'];
        }

        return $this->getFormatForAlias($class, $alias);
    }

    /**
     * returns the corresponding type for given class/alias (typically date formats), null if not found in catalog.
     *
     * @param $class
     * @param $alias
     *
     */
    public function getTypeForAlias($class, $alias): ?string
    {
        $class = $this->getClassName($class);
        if (!isset($this->termCatalog[$class])) {
            return null;
        }
        if (!isset($this->termCatalog[$class]['fields'][$alias])) {
            return null;
        }
        if (!isset($this->termCatalog[$class]['fields'][$alias]['type'])) {
            return null;
        }
        return $this->termCatalog[$class]['fields'][$alias]['type'];
    }

    /**
     * Provides the class real name (useful for proxy classes).
     *
     * @param $className
     *
     * @return string
     */
    private function getClassName($className)
    {
        try {
            $absClassName = $this->objectManager->getClassMetadata($className)->getName();
        }
        catch (\Doctrine\Persistence\Mapping\MappingException) {
            $absClassName = $className;
        }

        return $absClassName;
    }
}

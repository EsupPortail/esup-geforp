<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 01/09/14
 * Time: 14:43.
 */
namespace App\MappingProvider;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class CascadeUpdater.
 *
 * @todo : HANDLE COMPOSITE OR CUSTOM ID CONFIGURATION
 */
final class MappingProvider
{
    /**
     * @var
     */
    private array $entities = [];

    /**
     * @var
     */
    private array $visitedEntities = [];

    /**
     * @param $mapping
     *
     * @internal param $em
     * @param mixed[] $mapping
     */
    public function __construct(
        /** @var  array mapping elastica mapping */
        protected $mapping,
        private readonly Container $container
    )
    {
    }

    /**
     * returns an array of mapping for given class if known.
     *
     * @param $class
     */
    public function getMappingByClass($class)
    {
        $class = $this->aliasToClass($class);
        foreach ($this->mapping as $indexMapping) {
            foreach ($indexMapping['types'] as $mapElt) {
                if (empty($mapElt['config']['persistence'])) {
                    continue;
                }
                if ($class !== $mapElt['config']['persistence']['model']) {
                    continue;
                }
                return $mapElt['mapping']['properties'];
            }
        }
    }

    /**
     * @param $class
     *
     */
    public function getMappingNameByClass($class): int|null|string
    {
        $class = $this->aliasToClass($class);
        foreach ($this->mapping as $index => $indexMapping) {
            foreach ($indexMapping['types'] as $key => $mapElt) {
                if (empty($mapElt['config']['persistence'])) {
                    continue;
                }
                if ($class !== $mapElt['config']['persistence']['model']) {
                    continue;
                }
                return [$index, $key];
            }
        }

        return $this->getMappingNameByClass($class);
    }

    /**
     * @param $entityClass
     * @param $path
     *
     * @return bool
     */
    public function classMappingContainsPath($entityClass, $path)
    {
        $class = $this->aliasToClass($entityClass);

        //if no dots are present in path, checking properties
        if(strpos((string) $path, '.') <= 0){
            $tmp = $this->getMappingByClass($class);

            return ! empty($tmp[$path]);
        }
        $steps = explode('.', (string) $path);
        $tmp   = $this->getMappingByClass($class);
        array_shift($steps);
        return $this->classMappingArrayContainsPath($tmp, $steps);
    }

    /**
     * triggering index updates.
     */
/*    public function updateIndex()
    {
        foreach ($this->entities as $class => $entities) {
            list($index, $type) = $this->getMappingNameByClass($class);
            if($index && $type) {
                $serviceId = 'fos_elastica.object_persister.' . $index . '.' . $type;
                if ($this->container->has($serviceId) && ! empty($entities)) {
                    $this->container->get($serviceId)->replaceMany($entities);
                }
            }
        }
        $this->container->get('fos_elastica.index')->refresh();
    }
*/
    /**
     * @return string
     */
    public function getStats()
    {
        $str = '';
        foreach ($this->entities as $key => $class) {
            $str .= $key . ': [' . (is_countable($class) ? count($class) : 0) . '] ';
            foreach ($class as $ent) {
                $str .= $ent->getId() . ' ';
            }

            $str .= "\n";
        }

        return $str;
    }

    /**
     * @param $mappingArray
     * @param $pathArray
     *
     * @return bool
     */
    private function classMappingArrayContainsPath($mappingArray, array $pathArray)
    {
        if (empty($mappingArray[$pathArray[0]])) {
            return false;
        }

        //from now on, we can say an entry exists in mapping for current path.
        if ( (is_countable($pathArray) ? count($pathArray) : 0) === 1 ){
            return true;
        }
        array_pop($pathArray);
        return $this->classMappingArrayContainsPath($mappingArray[$pathArray[0]]['properties'], $pathArray);
    }

    /**
     * @param $class
     *
     * @return string
     */
    private function aliasToClass($class)
    {
        if (strpos((string) $class, ':') > 0) {
            return $this->container->get('doctrine.orm.entity_manager')->getClassMetadata($class)->getName();
        }

        return $class;
    }

    /**
     * @param $entityClass
     * @param $entityProperty
     * @param $entityId
     * @param bool  $onlyManyToMany
     * @param array $path
     */
    public function findLinkedEntities($entityClass, $entityProperty, $entityId, $onlyManyToMany = false, $path = []): void
    {
        /** @var ClassMetaData $metadata */
        $metadata                = $this->container->get('doctrine.orm.entity_manager')->getClassMetadata($entityClass);
        $this->visitedEntities[] = $metadata->getReflectionClass()->getName();

        //@todo hm: filter using input properties :
        // a targetentity whose mapping doesnt include any of the entityproperties has no interest to be inspected
        foreach ($metadata->associationMappings as $fieldMD) {
            //if already visited, we stop
            if ( ! empty($fieldMD['targetEntity']) && ! in_array($fieldMD['targetEntity'], $this->visitedEntities, true)) {
                //for relations of the type "many to many" OR any relation
                $tmpArr = $path;

                //getting next entity field that targets current entity
                $invPath = ( empty($fieldMD['inversedBy'])) ? $fieldMD['mappedBy'] : $fieldMD['inversedBy'];

                if ( ! empty($invPath)) {
                    $tmpArr[] = $invPath;

                    // we can continue exploration if next entity refers to current entity/field AND
                    if ($this->classMappingContainsPath($fieldMD['targetEntity'], implode('.', $tmpArr))) {
                        $vIds = $this->getConcernedEntities($fieldMD, $entityId);
                        $this->findLinkedEntities($fieldMD['targetEntity'], [$invPath], $vIds, false, $tmpArr);
                    }
                }
            }
        }
    }

    /**
     * @param $entity
     *
     * @return array<int, mixed[]>
     *
     * @todo : HANDLE COMPOSITE OR CUSTOM ID CONFIGURATION
     */
    public function getPostDeletionCommandLines($entity): array
    {
        $metaData = $this->container->get('doctrine.orm.entity_manager')->getClassMetadata($entity::class);
        $commands = [];
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach($metaData->associationMappings as $fieldName => $fieldMD) {
            $invPath = ( empty($fieldMD['inversedBy']) ) ? $fieldMD['mappedBy']  : $fieldMD['inversedBy'];
            if ( ! empty($invPath)) {
                $value = $propertyAccessor->getValue($entity, $fieldName);
                if($value) {
                    if ($value instanceof \Traversable) {
                        foreach($value as $tEntity) {
                            if(method_exists($tEntity, 'getId')) {
                                $commands[] = [$tEntity->getId(), $tEntity::class, $invPath];
                            }
                        }
                    } elseif (method_exists($value, 'getId')) {
                        $commands[] = [$value->getId(), $value::class, $invPath];
                    }
                }
            }
        }

        return $commands;
    }

    /**
     * @param $fieldMD
     * @param $id
     *
     *
     * @todo : HANDLE COMPOSITE OR CUSTOM ID CONFIGURATION
     */
    private function getConcernedEntities(array $fieldMD, $id): array
    {
        $type    = $fieldMD['targetEntity'];
        $invPath = ( empty($fieldMD['inversedBy'])) ? $fieldMD['mappedBy']  : $fieldMD['inversedBy'];
        if ( ! empty($invPath)) {
            $property = $invPath;
            $query    = $this->container->get('doctrine.orm.entity_manager')->getRepository($type)->createQueryBuilder('e')
                //->select('e.id')
                ->leftJoin('e.' . $property, 'ej')
                ->where('ej.id IN (:ids)')->setParameter('ids',  $id)
                ->getQuery();
        }
        else {
            $query = $this->container->get('doctrine.orm.entity_manager')->getRepository($type)->createQueryBuilder('e')
                //->select('e.id')
                ->where('e.id IN (:ids)')->setParameter('ids', $id)
                ->getQuery();
        }

        if (empty($this->entities[$type])) {
            $this->entities[$type] = [];
        }

        $res = $query->getResult();

        $returnedIds = [];
        foreach ($res as $re) {
            if (method_exists($re, 'getId')) {
                $returnedIds [] = $re->getId();
            }
        }

        $this->entities[$type] = array_merge($this->entities[$type], $res);

        return $returnedIds;
    }
}

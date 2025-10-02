<?php

namespace App\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Class TreeTraitListener.
 */
final class TreeTraitListener implements EventSubscriber
{
    /**
     * Returns hash of events, that this listener is bound to.
     *
     */
    public function getSubscribedEvents(): array
    {
        return [Events::loadClassMetadata];
    }

    /**
     * Adds mapping to the publishable and publications.
     *
     * @param LoadClassMetadataEventArgs $loadClassMetadataEventArgs The event arguments
     */
    public function loadClassMetadata(LoadClassMetadataEventArgs $loadClassMetadataEventArgs): void
    {
        $classMetadata = $loadClassMetadataEventArgs->getClassMetadata();
        if (!$classMetadata->reflClass instanceof \ReflectionClass) {
            return;
        }

        if ($this->isTree($classMetadata)) {
            $this->mapTree($classMetadata);
        }
    }

    /**
     * Checks if entity is a tree.
     *
     *
     */
    private function isTree(ClassMetadata $classMetadata): bool
    {
        $traits = $classMetadata->reflClass->getTraits();
        return array_key_exists('Sygefor\\Bundle\\CoreBundle\\Entity\\Term\\TreeTrait', $traits);
    }

    /**
     * Map the tree entity.
     *
     */
    private function mapTree(ClassMetadata $classMetadata): void
    {
        if (!$classMetadata->hasAssociation('parent')) {
            $classMetadata->mapManyToOne(['fieldName' => 'parent', 'targetEntity' => $classMetadata->name, 'inversedBy' => 'children', 'joinColumns' => [['name' => 'parent_id', 'referencedColumnName' => 'id', 'onDelete' => 'SET NULL']]]);
        }

        if (!$classMetadata->hasAssociation('children')) {
            $classMetadata->mapOneToMany(['fieldName' => 'children', 'mappedBy' => 'parent', 'orderBy' => ['lft' => 'ASC'], 'targetEntity' => $classMetadata->name]);
        }

        if ($classMetadata->customRepositoryClassName === null) {
            $classMetadata->setCustomRepositoryClass(\Gedmo\Tree\Entity\Repository\NestedTreeRepository::class);
        }
    }
}

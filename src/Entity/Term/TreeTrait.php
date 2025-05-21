<?php

namespace App\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Trait TreeTrait.
 */
trait TreeTrait
{
    /**
     * @Gedmo\TreeLeft
     * @ORM\Column(name="lft", type="integer")
     * @Serializer\Exclude
     */
    #[ORM\Column(name: "lft", type: "integer")]
    private $lft;

    /**
     * @Gedmo\TreeLevel
     * @ORM\Column(name="lvl", type="integer")
     * @Serializer\Exclude
     */
    #[ORM\Column(name: "lvl", type: "integer")]
    private mixed $lvl;

    /**
     * @Gedmo\TreeRight
     * @ORM\Column(name="rgt", type="integer")
     * @Serializer\Exclude
     */
    #[ORM\Column(name: "rgt", type: "integer")]
    private $rgt;

    /**
     * @Gedmo\TreeRoot
     * @ORM\Column(name="root", type="integer", nullable=true)
     * @Serializer\Exclude
     */
    #[ORM\Column(name: "root", type: "integer", nullable: true)]
    private mixed $root;

    /**
     * @Gedmo\TreeParent
     * @Serializer\Exclude
     * This property will be mapped by the TreeTraitListener
     * _ORM\ManyToOne(targetEntity="__SELF__", inversedBy="children")
     * _ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     */
    #[ORM\ManyToOne(targetEntity: "__SELF__", inversedBy: "children")]
    #[ORM\JoinColumn(name: "parent_id", referencedColumnName: "id", onDelete: "CASCADE")]
    private mixed $parent;

    /**
     * @Serializer\Groups({"api"})
     * This property will be mapped by the TreeTraitListener
     * _ORM\OneToMany(targetEntity="__SELF__", mappedBy="parent")
     * _ORM\OrderBy({"lft" = "ASC"})
     */
    #[Groups(['api'])]
    #[ORM\OneToMany(mappedBy: "parent", targetEntity: "__SELF__")]
    #[ORM\OrderBy(["lft" => "ASC"])]
    private mixed $children;

    /**
     * @param null $parent
     */
    public function setParent($parent = null): void
    {
        $this->parent = $parent;
    }

    /**
     * @return mixed
     */
    public function getParent(): mixed
    {
        return $this->parent;
    }

    /**
     * @return mixed
     */
    public function getChildren(): mixed
    {
        return $this->children;
    }

    public function hasChildren(): bool
    {
        return (bool) count($this->children);
    }

    /**
     * @return mixed
     */
    public function getLvl(): mixed
    {
        return $this->lvl;
    }

    /**
     * @return mixed
     */
    public function getRoot(): mixed
    {
        return $this->root;
    }

    /**
     * @return mixed
     */
    public function getRootEntity(): mixed
    {
        $entity = $this;
        while ($entity->getParent()) {
            $entity = $entity->getParent();
        }

        return $entity;
    }

    /**
     * @return mixed
     */
    public function belongTo($entity): bool
    {
        if ($this === $entity) {
            return true;
        }


        return (bool) $this->getParent()->belongTo($entity);
    }
}
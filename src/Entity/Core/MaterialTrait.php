<?php
/**
 * Created by PhpStorm.
 * BaseUser: erwan
 * Date: 8/1/17
 * Time: 12:07 PM.
 */

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\ArrayCollection;

trait MaterialTrait
{
    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AbstractMaterial", mappedBy="entity", cascade={"remove", "persist"})
     * @ORM\JoinColumn(nullable=true)
     * @Serializer\Exclude
     */
    protected $materials;

    /**
     * @param ArrayCollection $materials
     */
    public function setMaterials($materials)
    {
        $this->materials = $materials;
    }

    /**
     * @param AbstractMaterial $materials
     */
    public function addMaterial($materials)
    {
        $this->materials->add($materials);
    }

    /**
     * @return ArrayCollection
     */
    public function getMaterials()
    {
        return $this->materials;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"Default", "training", "session", "api.training", "api.attendance"})
     *
     * @return array
     */
    public function getPublicMaterials()
    {
        $materials = array();
        foreach ($this->materials as $material) {
            if ($material->getIsPublic()) {
                $materials[] = $material;
            }
        }

        return $materials;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"Default", "training", "session", "api.attendance"})
     *
     * @return array
     */
    public function getPrivateMaterials()
    {
        $materials = array();
        foreach ($this->materials as $material) {
            if (!$material->getIsPublic()) {
                $materials[] = $material;
            }
        }

        return $materials;
    }

    /**
     * @param AbstractMaterial $material
     *
     * @return bool
     */
    public function removeMaterial(AbstractMaterial $material)
    {
        if ($this->materials->contains($material)) {
            $this->materials->removeElement($material);

            return true;
        }

        return false;
    }
}

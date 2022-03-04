<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\Core\Term\PublicType;
use App\Entity\Core\AbstractTraining;
use App\Form\InternshipType;

/**
 * Stage.
 *
 * @ORM\Entity
 * @ORM\Table(name="internship")
 */
class Internship extends AbstractTraining
{
    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Core\Term\PublicType")
     * @ORM\JoinTable(name="internship__internship_public_type",
     *      joinColumns={@ORM\JoinColumn(name="intership_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="public_type_id", referencedColumnName="id")}
     * )
     * @Serializer\Groups({"training", "inscription", "api"})
     */
    protected $publicTypes;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Core\Term\PublicType")
     * @ORM\JoinTable(name="internship__internship_public_type_restrict",
     *      joinColumns={@ORM\JoinColumn(name="intership_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="public_type_restrict_id", referencedColumnName="id")}
     * )
     * @Serializer\Groups({"training", "inscription", "api"})
     */
    protected $publicTypesRestrict;

    /**
     * @var string
     * @ORM\Column(name="prerequisites", type="text", nullable=true)
     * @Serializer\Groups({"training", "api"})
     */
    protected $prerequisites;

    /**
     * @ORM\Column(name="designated_public", type="boolean", nullable=true)
     *
     * @var bool
     * @Serializer\Groups({"training", "api"})
     */
    protected $designatedPublic;


    public function __construct()
    {
        $this->publicTypes = new ArrayCollection();
        $this->publicTypesRestrict = new ArrayCollection();

        parent::__construct();
    }

    public function __clone()
    {
        $this->publicTypes = new ArrayCollection();
        $this->publicTypesRestrict = new ArrayCollection();

        parent::__construct();
    }

    /**
     * @param mixed $publicTypes
     */
    public function setPublicTypes($publicTypes)
    {
        $this->publicTypes = $publicTypes;
    }

    /**
     * @return mixed
     */
    public function getPublicTypes()
    {
        return $this->publicTypes;
    }

    /**
     * @param PublicType $publicType
     */
    public function addPublicType($publicType)
    {
        if (!$this->publicTypes->contains($publicType)) {
            $this->publicTypes->add($publicType);
        }
    }

    /**
     * @param PublicType $publicType
     */
    public function removePublicType($publicType)
    {
        if ($this->publicTypes->contains($publicType)) {
            $this->publicTypes->removeElement($publicType);
        }
    }

    /**
     * HumanReadablePropertyAccessor helper : provides a list of public_old types as string
     * @return String
     */
    public function getPublicTypesListString()
    {
        if (empty($this->publicTypes)) return "";
        $ptNames = array();
        foreach ($this->publicTypes as $pt) {
            $ptNames[] = $pt->getName();
        }

        return implode(", ", $ptNames);
    }

    /**
     * @param mixed $publicTypesRestrict
     */
    public function setPublicTypesRestrict($publicTypesRestrict)
    {
        $this->publicTypesRestrict = $publicTypesRestrict;
    }

    /**
     * @return mixed
     */
    public function getPublicTypesRestrict()
    {
        return $this->publicTypesRestrict;
    }

    /**
     * @param PublicTypeRestrict $publicTypesRestrict
     */
    public function addPublicTypeRestrict($publicTypesRestrict)
    {
        if (!$this->publicTypesRestrict->contains($publicTypesRestrict)) {
            $this->publicTypesRestrict->add($publicTypesRestrict);
        }
    }

    /**
     * @param PublicTypeRestrict $publicTypesRestrict
     */
    public function removePublicTypeRestrict($publicTypesRestrict)
    {
        if ($this->publicTypesRestrict->contains($publicTypesRestrict)) {
            $this->publicTypesRestrict->removeElement($publicTypesRestrict);
        }
    }

    /**
     * HumanReadablePropertyAccessor helper : provides a list of public_old types as string
     * @return String
     */
    public function getPublicTypesRestrictListString()
    {
        if (empty($this->publicTypesRestrict)) return "";
        $ptNames = array();
        foreach ($this->publicTypesRestrict as $pt) {
            $ptNames[] = $pt->getName();
        }

        return implode(", ", $ptNames);
    }

    /**
     * @return mixed
     */
    public function getPrerequisites()
    {
        return $this->prerequisites;
    }

    /**
     * @param mixed $prerequisites
     */
    public function setPrerequisites($prerequisites)
    {
        $this->prerequisites = $prerequisites;
    }

    /**
     * @return mixed
     */
    public function getDesignatedPublic()
    {
        return $this->designatedPublic;
    }

    /**
     * @param mixed $designatedPublic
     */
    public function setDesignatedPublic($designatedPublic)
    {
        $this->designatedPublic = $designatedPublic;
    }

    /**
     * @return string
     */
    static public function getType()
    {
        return 'internship';
    }

    /**
     * @return string
     */
    static public function getTypeLabel()
    {
        return 'Stage';
    }

    /**
     * @return string
     */
    static public function getFormType()
    {
        return InternshipType::class;
    }
}

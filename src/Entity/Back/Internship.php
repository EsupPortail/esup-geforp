<?php

namespace App\Entity\Back;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\Term\Publictype;
use App\Entity\Core\AbstractTraining;
use App\Form\Type\InternshipType;

/**
 * Stage.
 *
 * @ORM\Entity
 * @ORM\Table(name="internship")
 */
class Internship extends AbstractTraining
{
    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Term\Publictype")
     * @ORM\JoinTable(name="internship__internship_publictype",
     *      joinColumns={@ORM\JoinColumn(name="intership_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="publictype_id", referencedColumnName="id")}
     * )
     * @Serializer\Groups({"training", "inscription", "api"})
     */
    protected $publictypes;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Term\Publictype")
     * @ORM\JoinTable(name="internship__internship_publictyperestrict",
     *      joinColumns={@ORM\JoinColumn(name="intership_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="publictyperestrict_id", referencedColumnName="id")}
     * )
     * @Serializer\Groups({"training", "inscription", "api"})
     */
    protected $publictypesrestrict;

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
    protected $designatedpublic;


    public function __construct()
    {
        $this->publictypes = new ArrayCollection();
        $this->publictypesrestrict = new ArrayCollection();

        parent::__construct();
    }

    public function __clone()
    {
        $this->publictypes = new ArrayCollection();
        $this->publictypesrestrict = new ArrayCollection();

        parent::__construct();
    }

    /**
     * @param mixed $Publictypes
     */
    public function setPublictypes($Publictypes)
    {
        $this->publictypes = $Publictypes;
    }

    /**
     * @return mixed
     */
    public function getPublictypes()
    {
        return $this->publictypes;
    }

    /**
     * @param Publictype $Publictype
     */
    public function addPublictype($Publictype)
    {
        if (!$this->publictypes->contains($Publictype)) {
            $this->publictypes->add($Publictype);
        }
    }

    /**
     * @param Publictype $Publictype
     */
    public function removePublictype($Publictype)
    {
        if ($this->publictypes->contains($Publictype)) {
            $this->publictypes->removeElement($Publictype);
        }
    }

    /**
     * HumanReadablePropertyAccessor helper : provides a list of public_old types as string
     * @return String
     */
    public function getPublictypesListString()
    {
        if (empty($this->publictypes)) return "";
        $ptNames = array();
        foreach ($this->publictypes as $pt) {
            $ptNames[] = $pt->getName();
        }

        return implode(", ", $ptNames);
    }

    /**
     * @param mixed $Publictypesrestrict
     */
    public function setPublictypesrestrict($Publictypesrestrict)
    {
        $this->publictypesrestrict = $Publictypesrestrict;
    }

    /**
     * @return mixed
     */
    public function getPublictypesrestrict()
    {
        return $this->publictypesrestrict;
    }

    /**
     * @param Publictype $Publictype
     */
    public function addPublictyperestrict($Publictype)
    {
        if (!$this->publictypesrestrict->contains($Publictype)) {
            $this->publictypesrestrict->add($Publictype);
        }
    }

    /**
     * @param Publictype $Publictype
     */
    public function removePublictyperestrict($Publictype)
    {
        if ($this->publictypesrestrict->contains($Publictype)) {
            $this->publictypesrestrict->removeElement($Publictype);
        }
    }

    /**
     * HumanReadablePropertyAccessor helper : provides a list of public_old types as string
     * @return String
     */
    public function getPublictypesRestrictListString()
    {
        if (empty($this->publictypesrestrict)) return "";
        $ptNames = array();
        foreach ($this->publictypesrestrict as $pt) {
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
    public function getDesignatedpublic()
    {
        return $this->designatedpublic;
    }

    /**
     * @param mixed $designatedpublic
     */
    public function setDesignatedpublic($designatedPublic)
    {
        $this->designatedpublic = $designatedPublic;
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

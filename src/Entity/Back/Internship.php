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
 */
#[ORM\Table(name: 'internship')]
#[ORM\Entity]
class Internship extends AbstractTraining
{
    /**
     * @Serializer\Groups({"training", "inscription", "api"})
     * @var \Doctrine\Common\Collections\Collection<\App\Entity\Term\Publictype>
     */
    #[ORM\JoinTable(name: 'internship__internship_publictype')]
    #[ORM\JoinColumn(name: 'intership_id')]
    #[ORM\InverseJoinColumn(name: 'publictype_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: \App\Entity\Term\Publictype::class)]
    protected \Doctrine\Common\Collections\Collection $publictypes;

    /**
     * @Serializer\Groups({"training", "inscription", "api"})
     * @var \Doctrine\Common\Collections\Collection<\App\Entity\Term\Publictype>
     */
    #[ORM\JoinTable(name: 'internship__internship_publictyperestrict')]
    #[ORM\JoinColumn(name: 'intership_id')]
    #[ORM\InverseJoinColumn(name: 'publictyperestrict_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: \App\Entity\Term\Publictype::class)]
    protected \Doctrine\Common\Collections\Collection $publictypesrestrict;

    /**
     * @Serializer\Groups({"training", "api"})
     */
    #[ORM\Column(name: 'prerequisites', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $prerequisites = null;

    /**
     *
     * @Serializer\Groups({"training", "api"})
     */
    #[ORM\Column(name: 'designated_public', type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected ?bool $designatedpublic = null;


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

    public function setPublictypes(mixed $Publictypes): void
    {
        $this->publictypes = $Publictypes;
    }

    /**
     * @return mixed
     */
    public function getPublictypes(): \Doctrine\Common\Collections\Collection
    {
        return $this->publictypes;
    }

    /**
     * @param Publictype $Publictype
     */
    public function addPublictype($Publictype): void
    {
        if (!$this->publictypes->contains($Publictype)) {
            $this->publictypes->add($Publictype);
        }
    }

    /**
     * @param Publictype $Publictype
     */
    public function removePublictype($Publictype): void
    {
        if ($this->publictypes->contains($Publictype)) {
            $this->publictypes->removeElement($Publictype);
        }
    }

    /**
     * HumanReadablePropertyAccessor helper : provides a list of public_old types as string
     */
    public function getPublictypesListString(): string
    {
        if (empty($this->publictypes)) return "";

        $ptNames = [];
        foreach ($this->publictypes as $publictype) {
            $ptNames[] = $publictype->getName();
        }

        return implode(", ", $ptNames);
    }

    public function setPublictypesrestrict(mixed $Publictypesrestrict): void
    {
        $this->publictypesrestrict = $Publictypesrestrict;
    }

    /**
     * @return mixed
     */
    public function getPublictypesrestrict(): \Doctrine\Common\Collections\Collection
    {
        return $this->publictypesrestrict;
    }

    /**
     * @param Publictype $Publictype
     */
    public function addPublictyperestrict($Publictype): void
    {
        if (!$this->publictypesrestrict->contains($Publictype)) {
            $this->publictypesrestrict->add($Publictype);
        }
    }

    /**
     * @param Publictype $Publictype
     */
    public function removePublictyperestrict($Publictype): void
    {
        if ($this->publictypesrestrict->contains($Publictype)) {
            $this->publictypesrestrict->removeElement($Publictype);
        }
    }

    /**
     * HumanReadablePropertyAccessor helper : provides a list of public_old types as string
     */
    public function getPublictypesRestrictListString(): string
    {
        if (empty($this->publictypesrestrict)) return "";

        $ptNames = [];
        foreach ($this->publictypesrestrict as $pt) {
            $ptNames[] = $pt->getName();
        }

        return implode(", ", $ptNames);
    }

    /**
     * @return mixed
     */
    public function getPrerequisites(): ?string
    {
        return $this->prerequisites;
    }

    public function setPrerequisites(mixed $prerequisites): void
    {
        $this->prerequisites = $prerequisites;
    }

    /**
     * @return mixed
     */
    public function getDesignatedpublic(): ?bool
    {
        return $this->designatedpublic;
    }

    /**
     * @param mixed $designatedpublic
     */
    public function setDesignatedpublic(?bool $designatedPublic): void
    {
        $this->designatedpublic = $designatedPublic;
    }

    static public function getType(): string
    {
        return 'internship';
    }

    static public function getTypeLabel(): string
    {
        return 'Stage';
    }

    static public function getFormType(): string
    {
        return InternshipType::class;
    }
}

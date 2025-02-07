<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\ArrayCollection;
use App\Form\Type\AbstractTrainingType;
use App\Entity\Core\AbstractInstitution;
use App\Entity\Core\Material;
use App\Entity\Term\Supervisor;
use App\Entity\Term\Tag;
use App\Entity\Term\Trainingcategory;
use Symfony\Component\Validator\Constraints as Assert;
use App\AccessRight\SerializedAccessRights;

/**
 * @method getMaterials()
 */
#[ORM\Table(name: 'training')]
#[ORM\UniqueConstraint(name: 'organization_number', columns: ['number', 'organization_id'])]
#[ORM\Entity]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([])]
abstract class AbstractTraining implements SerializedAccessRights, \Stringable
{
    // Hook timestampable behavior : updates createdAt, updatedAt fields
    use TimestampableTrait;

//    use MaterialTrait;
    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    /**
     * @var AbstractOrganization
     * @Serializer\Groups({"Default", "training", "api"})
     */
    #[ORM\ManyToOne(targetEntity: 'AbstractOrganization')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    protected $organization;

    /**
     * @var \Doctrine\Common\Collections\Collection<\App\Entity\Core\AbstractSession>
     * @Serializer\Groups({"training", "api.training"})
     */
    #[ORM\OneToMany(targetEntity: 'AbstractSession', mappedBy: 'training', cascade: ['persist', 'remove'])]
    protected \Doctrine\Common\Collections\Collection $sessions;

    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'number', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected ?int $number = null;

    /**
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'name', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Vous devez renseigner un intitulé.')]
    protected ?string $name = null;

    /**
     * @Serializer\Groups({"training", "session", "inscription", "api"})
     */
    #[ORM\ManyToOne(targetEntity: \App\Entity\Term\Theme::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Vous devez renseigner une thématique.')]
    protected ?\App\Entity\Term\Theme $theme = null;

    /**
     *
     * @Serializer\Groups({"training", "api"})
     */
    #[ORM\Column(name: 'program', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $program = null;

    /**
     *
     * @Serializer\Groups({"training", "api"})
     */
    #[ORM\Column(name: 'description', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $description = null;

    /**
     *
     * @Serializer\Groups({"training", "api"})
     */
    #[ORM\Column(name: 'teaching_methods', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $teachingmethods = null;

    /**
     *
     * @Serializer\Groups({"training", "api"})
     */
    #[ORM\ManyToOne(targetEntity: \App\Entity\Core\AbstractInstitution::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    protected ?\App\Entity\Core\AbstractInstitution $institution = null;

    /**
     * @Serializer\Groups({"training", "api.training", "session"})
     */
    #[ORM\ManyToOne(targetEntity: \App\Entity\Term\Supervisor::class)]
    protected ?\App\Entity\Term\Supervisor $supervisor = null;

    /**
     * @Serializer\Groups({"training", "api"})
     */
    #[ORM\ManyToOne(targetEntity: \App\Entity\Term\Trainingcategory::class)]
    #[ORM\JoinColumn]
    protected ?\App\Entity\Term\Trainingcategory $category = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<\App\Entity\Term\Tag>
     * @Serializer\Groups({"training", "api"})
     */
    #[ORM\JoinTable(name: 'training__training_tag')]
    #[ORM\JoinColumn(name: 'training_id', onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'tag_id', referencedColumnName: 'id', onDelete: 'cascade')]
    #[ORM\ManyToMany(targetEntity: \App\Entity\Term\Tag::class)]
    protected \Doctrine\Common\Collections\Collection $tags;

    /**
     *
     * @Serializer\Groups({"training", "api"})
     */
    #[ORM\Column(name: 'interventionType', type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    protected ?string $interventiontype = null;

    /**
     *
     * @Serializer\Groups({"training"})
     */
    #[ORM\Column(name: 'externalInitiative', type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected ?bool $externalinitiative = null;

    /**
     *
     * @Serializer\Groups({"training", "api"})
     */
    #[ORM\Column(name: 'firstSessionPeriodSemester', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[Assert\NotNull]
    protected int $firstsessionperiodsemester;

    /**
     *
     * @Serializer\Groups({"training", "api"})
     */
    #[ORM\Column(name: 'firstSessionPeriodYear', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[Assert\NotNull]
    protected ?int $firstsessionperiodyear = null;

    /**
     *
     * @Serializer\Groups({"training"})
     */
    #[ORM\Column(name: 'comments', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $comments = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<\App\Entity\Core\Material>
     * @Serializer\Groups({"training", "session", "api.attendance"})
     */
    #[ORM\OneToMany(targetEntity: \App\Entity\Core\Material::class, mappedBy: 'training', cascade: ['remove', 'persist'])]
    #[ORM\JoinColumn]
    protected \Doctrine\Common\Collections\Collection $materials;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->firstsessionperiodyear = (new \DateTime())->format('Y');
        $this->firstsessionperiodsemester = ((new \DateTime())->format('m') > 6 ? 2 : 1);
        $this->sessions = new ArrayCollection();
        $this->materials = new ArrayCollection();
        $this->tags     = new ArrayCollection();
    }

    /**
     * cloning magic function.
     */
    public function __clone()
    {
        $this->id = null;
        $this->setCreatedat(new \DateTime());

        //sessions are not copied.
        $this->materials = new ArrayCollection();
        $this->sessions = new ArrayCollection();
        $this->tags     = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public static function getFormType()
    {
        return AbstractTrainingType::class;
    }

    /**
     * @param $addMethod
     * @param ArrayCollection $arrayCollection
     */
    public function duplicateArrayCollection($addMethod, $arrayCollection): void
    {
        foreach ($arrayCollection as $item) {
            if (method_exists($this, $addMethod)) {
                $this->$addMethod($item);
            }
        }
    }

    /**
     * Copy all properties from a training except id and number.
     *
     * @param AbstractTraining $originalTraining
     */
    public function copyProperties($originalTraining): void
    {
        foreach (array_keys(get_object_vars($this)) as $key) {
            if (!($key !== 'id' && $key !== 'number' && $key !== 'sessions')) {
                continue;
            }
            if ($key === 'session') {
                continue;
            }
            if (!isset($originalTraining->$key)) {
                continue;
            }
            $this->$key = $originalTraining->$key;
        }
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return AbstractOrganization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param AbstractOrganization $organization
     */
    public function setOrganization($organization): void
    {
        $this->organization = $organization;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @param ArrayCollection $sessions
     */
    public function setSessions($sessions): void
    {
        $this->sessions = $sessions;
    }

    /**
     * @param AbstractSession $session
     */
    public function addSession(mixed $session): void
    {
        $this->sessions->add($session);
    }

    /**
     * @param AbstractSession $session
     */
    public function removeSession(mixed $session): void
    {
        $this->sessions->removeElement($session);
    }

    /**
     * @return ArrayCollection
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    public function setNumber(mixed $number): void
    {
        $this->number = $number;
    }

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @return mixed
     */
    public function getTheme()
    {
        return $this->theme;
    }

    public function setTheme(mixed $theme): void
    {
        $this->theme = $theme;
    }

    /**
     * @return string
     */
    public function getProgram()
    {
        return $this->program;
    }

    /**
     * @param string $program
     */
    public function setProgram($program): void
    {
        $this->program = $program;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): void
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getTeachingmethods()
    {
        return $this->teachingmethods;
    }

    /**
     * @param string $teachingMethods
     */
    public function setTeachingmethods($teachingmethods): void
    {
        $this->teachingmethods = $teachingmethods;
    }

    /**
     * @return AbstractInstitution
     */
    public function getInstitution()
    {
        return $this->institution;
    }

    /**
     * @param AbstractInstitution $institution
     */
    public function setInstitution($institution): void
    {
        $this->institution = $institution;
    }

    /**
     * @return Supervisor
     */
    public function getSupervisor()
    {
        return $this->supervisor;
    }

    /**
     * @param Supervisor $supervisor
     */
    public function setSupervisor($supervisor): void
    {
        $this->supervisor = $supervisor;
    }

    /**
     * @return Trainingcategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Trainingcategory $category
     */
    public function setCategory($category): void
    {
        $this->category = $category;
    }

    /**
     * @return ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param ArrayCollection $tags
     */
    public function setTags($tags): void
    {
        $this->tags = $tags;
    }

    /**
     * @param Tag $tag
     *
     * @return bool
     */
    public function addTag($tag)
    {
        if ( ! $this->tags->contains($tag)) {
            $this->tags->add($tag);

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getInterventiontype()
    {
        return $this->interventiontype;
    }

    /**
     * @param string $interventiontype
     */
    public function setInterventiontype($interventiontype): void
    {
        $this->interventiontype = $interventiontype;
    }

    /**
     * @return boolean
     */
    public function isexternalinitiative()
    {
        return $this->externalinitiative;
    }

    /**
     * @param boolean $externalinitiative
     */
    public function setExternalinitiative($externalinitiative): void
    {
        $this->externalinitiative = $externalinitiative;
    }

    /**
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param string $comments
     */
    public function setComments($comments): void
    {
        $this->comments = $comments;
    }

    /**
     * @return int
     */
    public function getFirstsessionperiodsemester()
    {
        return $this->firstsessionperiodsemester;
    }

    public function setFirstsessionperiodsemester(int $firstsessionperiodsemester): void
    {
        $this->firstsessionperiodsemester = $firstsessionperiodsemester;
    }

    /**
     * @return int
     */
    public function getFirstsessionperiodyear()
    {
        return $this->firstsessionperiodyear;
    }

    /**
     * @param int $firstSessionPeriodYear
     */
    public function setFirstsessionperiodyear($firstSessionPeriodYear): void
    {
        $this->firstsessionperiodyear = $firstSessionPeriodYear;
    }

    /**
     * @param ArrayCollection $materials
     */
    public function setMaterials($materials): void
    {
        $this->materials = $materials;
    }

    /**
     * @param Material $material
     */
    public function addMaterial(mixed $material): void
    {
        $material->setTraining($this);
        $this->materials->add($material);
    }

    /**
     * Used for duplicate training choose type form.
     *
     * @return string
     */
    public function getDuplicatedType()
    {
        return static::getType();
    }

    /**
     * Used for duplicate training choose type form.
     */
    public function setDuplicatedType($type): void
    {
    }

    /**
     * @return string
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"Default", "api"})
     */
    public static function getTypeLabel()
    {
        return 'Formation';
    }

    /**
     * @return string
     *                Serializer : via listener to include in all cases
     */
    public static function getType()
    {
        return 'training';
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"session", "training"})
     */
    public function getLastsession(): mixed
    {

        $dateTime = new \DateTime();

        $result = null;
        $maxdif = 9_999_999_999;
        foreach ($this->sessions as $session) {
            $dif = $dateTime->getTimestamp() - $session->getDatebegin()->getTimeStamp();
            if (($dif > 0) && ($dif < $maxdif)) {
                $result = $session;
                $maxdif = $dif;
            }
        }

        return $result;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"session", "training"})
     */
    public function getNextsession(): mixed
    {

        $dateTime = new \DateTime();

        $result = null;
        $maxdif = 9_999_999_999;
        foreach ($this->sessions as $session) {
            $dif = $session->getDatebegin()->getTimestamp() - $dateTime->getTimeStamp();
            if (($dif > 0) && ($dif < $maxdif)) {
                $result = $session;
                $maxdif = $dif;
            }
        }

        return $result;
    }

    /**
     * @return mixed
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"session", "training"})
     */
    public function getSessionscount()
    {
        return count($this->sessions);
    }

    /**
     * @return mixed
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"session", "training"})
     */
    public function getTrainers()
    {
        $trainers = [];
        if ($this->sessions) {
            foreach ($this->sessions as $session) {
                if (!$session->getParticipations()) {
                    continue;
                }
                if ($session->getParticipations()->count() <= 0) {
                    continue;
                }
                foreach ($session->getParticipations() as $participation) {
                    // do not add several times the same trainer
                    $trainers[$participation->getTrainer()->getId()] = $participation->getTrainer();
                }
            }
        }

        return $trainers;
    }
}

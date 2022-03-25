<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\ArrayCollection;
use App\Form\Type\AbstractTrainingType;
use App\Entity\Core\AbstractInstitution;
use App\Entity\Core\AbstractMaterial;
use App\Entity\Core\Term\Supervisor;
use App\Entity\Core\Term\Tag;
use App\Entity\Core\Term\TrainingCategory;
use Symfony\Component\Validator\Constraints as Assert;
use App\Security\AccessRight\SerializedAccessRights;

/**
 * @ORM\Entity
 * @ORM\Table(name="training", uniqueConstraints={@ORM\UniqueConstraint(name="organization_number", columns={"number", "organization_id"})})
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({})
 * Traduction: Formation
 */
abstract class AbstractTraining implements SerializedAccessRights
{
    // Hook timestampable behavior : updates createdAt, updatedAt fields
    use TimestampableEntity;

//    use MaterialTrait;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"Default", "api"})
     */
    private $id;

    /**
     * @var AbstractOrganization
     * @ORM\ManyToOne(targetEntity="AbstractOrganization")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotBlank()
     * @Serializer\Groups({"Default", "training", "api"})
     */
    protected $organization;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AbstractSession", mappedBy="training", cascade={"persist", "remove"})
     * @Serializer\Groups({"training", "api.training"})
     */
    protected $sessions;

    /**
     * @ORM\Column(name="number", type="integer")
     * @Serializer\Groups({"Default", "api"})
     */
    protected $number;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank(message="Vous devez renseigner un intitulé.")
     *
     * @var string
     * @Serializer\Groups({"Default", "api"})
     */
    protected $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\Term\Theme")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotBlank(message="Vous devez renseigner une thématique.")
     * @Serializer\Groups({"training", "session", "inscription", "api"})
     */
    protected $theme;

    /**
     * @ORM\Column(name="program", type="text", nullable=true)
     *
     * @var string
     * @Serializer\Groups({"training", "api"})
     */
    protected $program;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     *
     * @var string
     * @Serializer\Groups({"training", "api"})
     */
    protected $description;

    /**
     * @ORM\Column(name="teaching_methods", type="text", nullable=true)
     *
     * @var string
     * @Serializer\Groups({"training", "api"})
     */
    protected $teachingMethods;

    /**
     * @var AbstractInstitution Institution
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\AbstractInstitution")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @Serializer\Groups({"training", "api"})
     */
    protected $institution;

    /**
     * @var Supervisor
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\Term\Supervisor")
     * @Serializer\Groups({"training", "api.training", "session"})
     */
    protected $supervisor;

    /**
     * @var TrainingCategory
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\Term\TrainingCategory")
     * @ORM\JoinColumn(nullable=true)
     * @Serializer\Groups({"training", "api"})
     */
    protected $category;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="App\Entity\Core\Term\Tag")
     * @ORM\JoinTable(name="training__training_tag",
     *      joinColumns={@ORM\JoinColumn(name="training_id", referencedColumnName="id", onDelete="cascade")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="id", onDelete="cascade")}
     * )
     * @Serializer\Groups({"training", "api"})
     */
    protected $tags;

    /**
     * @ORM\Column(name="interventionType", type="string", length=255, nullable=true)
     *
     * @var string
     * @Serializer\Groups({"training", "api"})
     */
    protected $interventionType;

    /**
     * @ORM\Column(name="externalInitiative", type="boolean", nullable=true)
     *
     * @var bool
     * @Serializer\Groups({"training"})
     */
    protected $externalInitiative;

    /**
     * @ORM\Column(name="firstSessionPeriodSemester", type="integer")
     * @Assert\NotNull
     *
     * @var int
     * @Serializer\Groups({"training", "api"})
     */
    protected $firstSessionPeriodSemester = 1;

    /**
     * @ORM\Column(name="firstSessionPeriodYear", type="integer")
     * @Assert\NotNull
     *
     * @var int
     * @Serializer\Groups({"training", "api"})
     */
    protected $firstSessionPeriodYear;

    /**
     * @ORM\Column(name="comments", type="text", nullable=true)
     *
     * @var string
     * @Serializer\Groups({"training"})
     */
    protected $comments;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->firstSessionPeriodYear = (new \DateTime())->format('Y');
        $this->firstSessionPeriodSemester = ((new \DateTime())->format('m') > 6 ? 2 : 1);
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
        $this->setCreatedAt(new \DateTime());

        //sessions are not copied.
        $this->materials = new ArrayCollection();
        $this->sessions = new ArrayCollection();
        $this->tags     = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
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
    public function duplicateArrayCollection($addMethod, $arrayCollection)
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
    public function copyProperties($originalTraining)
    {
        foreach (array_keys(get_object_vars($this)) as $key) {
            if ($key !== 'id' && $key !== 'number' && $key !== 'sessions' && $key !== 'session') {
                if (isset($originalTraining->$key)) {
                    $this->$key = $originalTraining->$key;
                }
            }
        }
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
    public function setOrganization($organization)
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
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param ArrayCollection $sessions
     */
    public function setSessions($sessions)
    {
        $this->sessions = $sessions;
    }

    /**
     * @param AbstractSession $session
     */
    public function addSession($session)
    {
        $this->sessions->add($session);
    }

    /**
     * @param AbstractSession $session
     */
    public function removeSession($session)
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

    /**
     * @param mixed $number
     */
    public function setNumber($number)
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

    /**
     * @param mixed $theme
     */
    public function setTheme($theme)
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
    public function setProgram($program)
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
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getTeachingMethods()
    {
        return $this->teachingMethods;
    }

    /**
     * @param string $teachingMethods
     */
    public function setTeachingMethods($teachingMethods)
    {
        $this->teachingMethods = $teachingMethods;
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
    public function setInstitution($institution)
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
    public function setSupervisor($supervisor)
    {
        $this->supervisor = $supervisor;
    }

    /**
     * @return TrainingCategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param TrainingCategory $category
     */
    public function setCategory($category)
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
    public function setTags($tags)
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
    public function getInterventionType()
    {
        return $this->interventionType;
    }

    /**
     * @param string $interventionType
     */
    public function setInterventionType($interventionType)
    {
        $this->interventionType = $interventionType;
    }

    /**
     * @return boolean
     */
    public function isExternalInitiative()
    {
        return $this->externalInitiative;
    }

    /**
     * @param boolean $externalInitiative
     */
    public function setExternalInitiative($externalInitiative)
    {
        $this->externalInitiative = $externalInitiative;
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
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    /**
     * @return int
     */
    public function getFirstSessionPeriodSemester()
    {
        return $this->firstSessionPeriodSemester;
    }

    /**
     * @param int $firstSessionPeriodSemester
     */
    public function setFirstSessionPeriodSemester($firstSessionPeriodSemester)
    {
        $this->firstSessionPeriodSemester = $firstSessionPeriodSemester;
    }

    /**
     * @return int
     */
    public function getFirstSessionPeriodYear()
    {
        return $this->firstSessionPeriodYear;
    }

    /**
     * @param int $firstSessionPeriodYear
     */
    public function setFirstSessionPeriodYear($firstSessionPeriodYear)
    {
        $this->firstSessionPeriodYear = $firstSessionPeriodYear;
    }

    /**
     * Used for duplicate training choose type form.
     *
     * @return string
     */
    public function getDuplicatedType()
    {
        return $this->getType();
    }

    /**
     * Used for duplicate training choose type form.
     */
    public function setDuplicatedType($type)
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
}

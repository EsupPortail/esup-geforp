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
    use TimestampableTrait;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\Term\Theme")
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
    protected $teachingmethods;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\Term\Supervisor")
     * @Serializer\Groups({"training", "api.training", "session"})
     */
    protected $supervisor;

    /**
     * @var Trainingcategory
     * @ORM\ManyToOne(targetEntity="App\Entity\Term\Trainingcategory")
     * @ORM\JoinColumn(nullable=true)
     * @Serializer\Groups({"training", "api"})
     */
    protected $category;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="App\Entity\Term\Tag")
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
    protected $interventiontype;

    /**
     * @ORM\Column(name="externalInitiative", type="boolean", nullable=true)
     *
     * @var bool
     * @Serializer\Groups({"training"})
     */
    protected $externalinitiative;

    /**
     * @ORM\Column(name="firstSessionPeriodSemester", type="integer")
     * @Assert\NotNull
     *
     * @var int
     * @Serializer\Groups({"training", "api"})
     */
    protected $firstsessionperiodsemester = 1;

    /**
     * @ORM\Column(name="firstSessionPeriodYear", type="integer")
     * @Assert\NotNull
     *
     * @var int
     * @Serializer\Groups({"training", "api"})
     */
    protected $firstsessionperiodyear;

    /**
     * @ORM\Column(name="comments", type="text", nullable=true)
     *
     * @var string
     * @Serializer\Groups({"training"})
     */
    protected $comments;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Entity\Core\Material", mappedBy="training", cascade={"remove", "persist"})
     * @ORM\JoinColumn(nullable=true)
     * @Serializer\Groups({"training", "session", "api.attendance"})
     */
    protected $materials;

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

    public function setId($id)
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
    public function getTeachingmethods()
    {
        return $this->teachingmethods;
    }

    /**
     * @param string $teachingMethods
     */
    public function setTeachingmethods($teachingmethods)
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
     * @return Trainingcategory
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Trainingcategory $category
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
    public function getInterventiontype()
    {
        return $this->interventiontype;
    }

    /**
     * @param string $interventiontype
     */
    public function setInterventiontype($interventiontype)
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
    public function setExternalinitiative($externalinitiative)
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
    public function setComments($comments)
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

    /**
     * @param int $firstsessionperiodsemester
     */
    public function setFirstsessionperiodsemester($firstsessionperiodsemester)
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
    public function setFirstsessionperiodyear($firstSessionPeriodYear)
    {
        $this->firstsessionperiodyear = $firstSessionPeriodYear;
    }

    /**
     * @param ArrayCollection $materials
     */
    public function setMaterials($materials)
    {
        $this->materials = $materials;
    }

    /**
     * @param Material $material
     */
    public function addMaterial($material)
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

    /**
     * @return mixed
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"session", "training"})
     */
    public function getLastsession()
    {
        if (empty($this->sessions)) {
            return;
        }
        $now = new \DateTime();

        $result = null;
        $maxdif = 9999999999;
        foreach ($this->sessions as $session) {
            $dif = $now->getTimestamp() - $session->getDatebegin()->getTimeStamp();
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
    public function getNextsession()
    {
        if (empty($this->sessions)) {
            return;
        }
        $now = new \DateTime();

        $result = null;
        $maxdif = 9999999999;
        foreach ($this->sessions as $session) {
            $dif = $session->getDatebegin()->getTimestamp() - $now->getTimeStamp();
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
        if (empty($this->sessions)) {
            return 0;
        }

        return count($this->sessions);
    }

    /**
     * @return mixed
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"session", "training"})
     */
    public function getTrainers()
    {
        $trainers = array();
        if ($this->sessions) {
            foreach ($this->sessions as $session) {
                if ($session->getParticipations() && $session->getParticipations()->count() > 0) {
                    foreach ($session->getParticipations() as $participation) {
                        // do not add several times the same trainer
                        $trainers[$participation->getTrainer()->getId()] = $participation->getTrainer();
                    }
                }
            }
        }

        return $trainers;
    }
}

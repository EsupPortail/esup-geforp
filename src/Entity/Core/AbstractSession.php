<?php

namespace App\Entity\Core;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\Term\Sessiontype;
use App\Form\Type\AbstractSessionType;
use App\AccessRight\SerializedAccessRights;
use App\Entity\Term\Inscriptionstatus;
use App\Entity\Core\AbstractInscription;
use App\Entity\Term\Presencestatus;
use App\Entity\Core\ParticipantsSummary;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Session.
 *
 * @ORM\Table(name="session")
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\HasLifecycleCallbacks
 *
 * traduction: session
 */
abstract class AbstractSession implements SerializedAccessRights
{
    // Hook timestampable behavior : updates createdAt, updatedAt fields
    use TimestampableTrait;

    // registration states
    const REGISTRATION_DEACTIVATED = 0;
    const REGISTRATION_CLOSED = 1;
    const REGISTRATION_PRIVATE = 2;
    const REGISTRATION_PUBLIC = 3;

    // registration states
    const STATUS_OPEN = 0;
    const STATUS_REPORTED = 1;
    const STATUS_CANCELED = 2;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"Default", "api"})
     */
    protected $id;

    /**
     * @var AbstractTraining
     * @ORM\ManyToOne(targetEntity="AbstractTraining", inversedBy="sessions")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Serializer\Groups({"session", "inscription", "trainee", "trainer", "api"})
     */
    protected $training;

    /**
     * @ORM\OneToMany(targetEntity="AbstractParticipation", mappedBy="session", cascade={"remove"})
     * @Serializer\Groups({"session", "inscription", "trainee", "trainer", "api"})
     */
    protected $participations;

    /**
     * @ORM\OneToMany(targetEntity="AbstractInscription", mappedBy="session", fetch="EXTRA_LAZY", cascade={"remove"})
     * @ORM\OrderBy({"createdat" = "DESC"})
     * @Serializer\Groups({"session"})
     */
    protected $inscriptions;

    /**
     * @ORM\Column(name="promote", type="boolean")
     * @Serializer\Groups({"Default", "session", "api"})
     */
    protected $promote = false;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateBegin", type="datetime")
     * @Assert\NotBlank(message="Vous devez préciser une date de début.")
     * @Serializer\Groups({"Default", "session", "api"})
     */
    protected $datebegin;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="dateEnd", type="datetime", nullable=true)
     * @Serializer\Groups({"Default", "session", "api"})
     */
    protected $dateend;

    /**
     * @ORM\Column(name="registration", type="integer")
     */
    protected $registration = self::REGISTRATION_CLOSED;

    /**
     * @ORM\Column(name="status", type="integer")
     * @Serializer\Groups({"session", "training", "inscription", "api"})
     */
    protected $status = self::STATUS_OPEN;

    /**
     * @ORM\Column(name="displayOnline", type="boolean")
     *
     * @var bool
     * @Serializer\Groups({"Default", "session", "api"})
     */
    protected $displayonline = false;

    /**
     * @ORM\Column(name="numberOfRegistrations", type="integer", nullable=true)
     * @Serializer\Exclude
     */
    protected $numberofregistrations;

    /**
     * @ORM\Column(name="maximumNumberOfRegistrations", type="integer")
     * @Serializer\Groups({"session", "training", "inscription", "api"})
     * @Assert\NotBlank()
     */
    protected $maximumnumberofregistrations = 20;

    /**
     * @ORM\Column(name="limitRegistrationDate", type="datetime")
     * @Serializer\Groups({"session", "training", "api"})
     */
    protected $limitregistrationdate;

    /**
     * @ORM\Column(name="comments", type="text", nullable=true)
     *
     * @var string
     * @Serializer\Groups({"session"})
     */
    protected $comments;

    /**
     * @var Sessiontype
     * @ORM\ManyToOne(targetEntity="App\Entity\Term\Sessiontype")
     * @ORM\JoinColumn(name="sessionType_id", referencedColumnName="id", onDelete="SET NULL")
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    protected $sessiontype;

    /**
     * @ORM\Column(name="hourNumber", type="float")
     * @Serializer\Groups({"session", "inscription", "api"})
     * @Assert\GreaterThan(value = 0, message = "Vous devez renseigner un nombre d'heures")
     * @Assert\NotNull(message="Vous devez renseigner un nombre d'heures")
     */
    protected $hournumber;

    /**
     * @ORM\Column(name="dayNumber", type="float")
     * @Serializer\Groups({"session", "inscription", "api"})
     * @Assert\GreaterThan(value = 0, message = "Vous devez renseigner un nombre de jours")
     * @Assert\NotNull(message="Vous devez renseigner un nombre de jours")
     */
    protected $daynumber;

    /**
     * @ORM\Column(name="schedule", type="string", length=512, nullable=true)
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    protected $schedule;

    /**
     * @ORM\Column(name="place", type="text", nullable=true)
     * @var String
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    protected $place;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Core\ParticipantsSummary", mappedBy="session", fetch="EXTRA_LAZY", cascade={"persist", "remove"})
     * @Serializer\Groups({"session"})
     */
    protected $participantsSummaries;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Entity\Core\Material", mappedBy="session", cascade={"remove", "persist"})
     * @ORM\JoinColumn(nullable=true)
     * @Serializer\Groups({"training", "session", "api.attendance"})
     */
    protected $materials;

    /**
     * @var ArrayCollection
     * @Serializer\Groups({"api.attendance"})
     */
    protected $allMaterials;


    public function __construct()
    {
        $this->inscriptions = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->participantsSummaries = new ArrayCollection();
        $this->materials = new ArrayCollection();
    }

    public function __clone()
    {
        $this->setId(null);
        $this->inscriptions = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->participantsSummaries = new ArrayCollection();
        $this->materials = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return AbstractTraining
     */
    public function getTraining()
    {
        return $this->training;
    }

    /**
     * @param AbstractTraining $training
     */
    public function setTraining($training)
    {
        $this->training = $training;
    }

    /**
     * @return mixed
     */
    public function getParticipations()
    {
        return $this->participations;
    }

    /**
     * @param mixed $participations
     */
    public function setParticipations($participations)
    {
        $this->participations = $participations;
    }

    /**
     * @param AbstractParticipation $participation
     *
     * @return bool
     */
    public function addParticipation($participation)
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);

            return true;
        }

        return false;
    }

    /**
     * @param AbstractParticipation $participation
     *
     * @return bool
     */
    public function removeParticipation($participation)
    {
        if ($this->participations->contains($participation)) {
            $this->participations->removeElement($participation);

            return true;
        }

        return false;
    }

    /**
     * HumanReadablePropertyAccessor helper function : allows to get a single string containing all trainers.
     *
     * @return string
     */
    public function getTrainersListString()
    {
        if (!$this->getParticipations()) {
            return '';
        }

        $array = array();
        /** @var AbstractParticipation $participation */
        foreach ($this->getParticipations() as $participation) {
            $array[] = $participation->getTrainer()->getFullname();
        }

        return implode(', ', $array);
    }

    /**
     * Return trainers from participations
     * Used for publipost templates.
     *
     * @return ArrayCollection
     */
    public function getTrainers()
    {
        $trainers = new ArrayCollection();
        /** @var AbstractParticipation $participation */
        foreach ($this->getParticipations() as $participation) {
            $trainers->add($participation->getTrainer());
        }

        return $trainers;
    }

    /**
     * @return mixed
     */
    public function getInscriptions()
    {
        return $this->inscriptions;
    }

    /**
     * @param mixed $inscriptions
     */
    public function setInscriptions($inscriptions)
    {
        $this->inscriptions = $inscriptions;
    }

    /**
     * @param AbstractInscription $inscription
     *
     * @return bool
     */
    public function addInscription($inscription)
    {
        if (!$this->inscriptions->contains($inscription)) {
            $this->inscriptions->add($inscription);

            return true;
        }

        return false;
    }

    /**
     * @param AbstractInscription $inscription
     *
     * @return bool
     */
    public function removeInscription($inscription)
    {
        if ($this->inscriptions->contains($inscription)) {
            $this->inscriptions->removeElement($inscription);

            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getPromote()
    {
        return $this->promote;
    }

    /**
     * @param mixed $promote
     */
    public function setPromote($promote)
    {
        $this->promote = $promote;
    }

    /**
     * @return mixed
     */
    public function getRegistration()
    {
        return $this->registration;
    }

    /**
     * @param mixed $registration
     */
    public function setRegistration($registration)
    {
        $this->registration = $registration;
    }

    /**
     * @return bool
     */
    public function isDisplayonline()
    {
        return $this->displayonline;
    }

    /**
     * @return mixed
     */
    public function getDisplayonline()
    {
        return $this->displayonline;
    }

    /**
     * @param bool $displayOnline
     */
    public function setDisplayonline($displayOnline)
    {
        $this->displayonline = $displayOnline;
    }

    /**
     * @return mixed
     */
    public function getDatebegin()
    {
        return $this->datebegin;
    }

    /**
     * @return int
     * @Serializer\VirtualProperty
     */
    public function getYear()
    {
        return $this->getDatebegin() ? $this->getDatebegin()->format('Y') : null;
    }

    /**
     * @return int
     * @Serializer\VirtualProperty
     */
    public function getSemester()
    {
        return $this->getDatebegin() ? ceil($this->getDatebegin()->format('m') / 6) : null;
    }

    /**
     * @return int
     * @Serializer\VirtualProperty
     * @Serializer\Groups("api")
     */
    public function getSemesterLabel()
    {
        return $this->getYear().' - '.($this->getSemester() < 2 ? '1er' : '2nd').' semestre ';
    }

    /**
     * @param mixed $dateBegin
     */
    public function setDatebegin($dateBegin)
    {
        $this->datebegin = $dateBegin;
    }

    /**
     * @return mixed
     */
    public function getDateend()
    {
        return $this->dateend;
    }

    /**
     * @param mixed $dateEnd
     */
    public function setDateend($dateEnd)
    {
        $this->dateend = $dateEnd;
    }

    /**
     * @return mixed
     */
    public function getHournumber()
    {
        return $this->hournumber;
    }

    /**
     * @param mixed $hourNumber
     */
    public function setHournumber($hourNumber)
    {
        $this->hournumber = $hourNumber;
    }

    /**
     * @return mixed
     */
    public function getDaynumber()
    {
        return $this->daynumber;
    }

    /**
     * @param mixed $dayNumber
     */
    public function setDaynumber($dayNumber)
    {
        $this->daynumber = $dayNumber;
    }

    /**
     * @return string
     */
    public function getDuration()
    {
        return $this->getHournumber() . ' heure(s) sur ' . $this->getDaynumber() . ' jour(s)';
    }

    /**
     * @return Place
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * @param Place $place
     */
    public function setPlace($place)
    {
        $this->place = $place;
    }

    /**
     * @return mixed
     */
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * @param mixed $schedule
     */
    public function setSchedule($schedule)
    {
        $this->schedule = $schedule;
    }

    /**
     * @return mixed
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param mixed $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getMaximumnumberofregistrations()
    {
        return $this->maximumnumberofregistrations;
    }

    /**
     * @param mixed $maximumNumberOfRegistrations
     */
    public function setMaximumnumberofregistrations($maximumNumberOfRegistrations)
    {
        $this->maximumnumberofregistrations = $maximumNumberOfRegistrations;
    }

    /**
     * @return Sessiontype
     */
    public function getSessiontype()
    {
        return $this->sessiontype;
    }

    /**
     * @param Sessiontype $sessionType
     */
    public function setSessiontype($sessionType)
    {
        $this->sessiontype = $sessionType;
    }

    /**
     * Return true if the session is available on the website (private or public_old registration).
     *
     * @return mixed
     */
    public function isAvailable()
    {
        return $this->registration > self::REGISTRATION_CLOSED;
    }

    /**
     * Return true if the session registration is public_old.
     *
     * @return mixed
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"api"})
     */
    public function isPublic()
    {
        return $this->registration === self::REGISTRATION_PUBLIC;
    }

    /**
     * The session is registrable.
     */
    public function isRegistrable()
    {
        if ($this->getStatus() !== self::STATUS_OPEN) {
            return false;
        }

        $now = new \DateTime();

        // check date
        if ($this->getDatebegin() <= $now) {
            return false;
        }

        // check status
        if ($this->getRegistration() < self::REGISTRATION_PRIVATE) {
            return false;
        }

        // check limit registration date
        if ($this->getLimitregistrationdate()) {
            $limitRegistrationDate = clone $this->getLimitregistrationdate();
            $limitRegistrationDate->modify('+1 days');
            if ($limitRegistrationDate < $now) {
                return false;
            }
        }

        // ok
        return $this->getAvailablePlaces() > 0;
    }

    /**
     * hack : for serialization.
     *
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"session", "training", "api.training"})
     */
    public function registrable()
    {
        return $this->isRegistrable();
    }

    /**
     * Return available places.
     *
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"api.training"})
     */
    public function getAvailablePlaces()
    {
        return $this->getMaximumnumberofregistrations() - $this->getNumberofacceptedregistrations();
    }

    /**
     * @return mixed
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"session", "training"})
     */
    public function getNumberofregistrations()
    {
        if ($this->getRegistration() === self::REGISTRATION_DEACTIVATED) {
            return $this->numberofregistrations;
        }

        if (empty($this->inscriptions)) {
            return 0;
        }

        return $this->inscriptions->count();
    }

    /**
     * @param mixed $numberOfRegistrations
     */
    public function setNumberofregistrations($numberOfRegistrations)
    {
        $this->numberofregistrations = $numberOfRegistrations;
    }

    /**
     * @return mixed
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"session", "training"})
     */
    public function getNumberofacceptedregistrations()
    {
        if ($this->getRegistration() === self::REGISTRATION_DEACTIVATED) {
            return $this->numberofregistrations;
        }

        if (empty($this->inscriptions)) {
            return 0;
        }

        $nAccepted = 0;
        foreach ($this->inscriptions as $inscription) {
            if ($inscription->getInscriptionstatus()->getStatus() === Inscriptionstatus::STATUS_ACCEPTED) {
                ++$nAccepted;
            }
        }

        return $nAccepted;
    }

    /**
     * @return mixed
     */
    public function getLimitregistrationdate()
    {
        return $this->limitregistrationdate;
    }

    /**
     * @param mixed $limitRegistrationDate
     */
    public function setLimitregistrationdate($limitRegistrationDate)
    {
        $this->limitregistrationdate = $limitRegistrationDate;
    }

    /**
     * Update the limit registration date.
     *
     * @ORM\PrePersist
     */
    public function updateLimitregistrationdate()
    {
        // if the limit registration date is not set,
        // set it to the day before date begin
        if (!$this->getLimitregistrationdate()) {
            $date = clone $this->getDatebegin();
            $date->modify('-1 month');
            $this->setLimitregistrationdate($date);
        }
    }

    /**
     * @return ArrayCollection
     */
    public function getMaterials()
    {
        return $this->materials;
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
     *
     * @return bool
     */
    public function addMaterial($material)
    {
        if (!$this->materials->contains($material)) {
            $material->setSession($this);
            $this->materials->add($material);

            return true;
        }

        return false;
    }

    /**
     * @return ArrayCollection
     */
    public function getAllMaterials()
    {
        return $this->allMaterials;
    }

    /**
     * @param ArrayCollection $allMaterials
     */
    public function setAllMaterials($allMaterials)
    {
        $this->allMaterials = $allMaterials;
    }

    /**
     * @return mixed
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"session", "training"})
     */
    public function getNumberofparticipants()
    {
        $count = 0;
        if ($this->getRegistration() === self::REGISTRATION_DEACTIVATED) {
            foreach ($this->getParticipantsSummaries() as $summary) {
                $count += $summary->getCount();
            }
        }
        else {
            if ($this->getInscriptions() != null) {
                /** @var AbstractInscription $inscription */
                foreach ($this->getInscriptions() as $inscription) {
                    if ($inscription->getPresencestatus() && $inscription->getPresencestatus()->getStatus() === PresenceStatus::STATUS_PRESENT) {
                        ++$count;
                    }
                }
            }
        }

        return $count;
    }

    /**
     * @return ArrayCollection
     */
    public function getParticipantsSummaries()
    {
        return $this->participantsSummaries;
    }

    /**
     * @param ArrayCollection $participantsSummaries
     */
    public function setParticipantsSummaries($participantsSummaries)
    {
        /** @var ParticipantsSummary $summary */
        foreach ($participantsSummaries as $summary) {
            $summary->setSession($this);
        }
        $this->participantsSummaries = $participantsSummaries;
    }

    /**
     * @param ParticipantsSummary $participantsSummary
     *
     * @return bool
     */
    public function addParticipantsSummary($participantsSummary)
    {
        foreach ($this->participantsSummaries as $participantsSummaryOne) {
            if ($participantsSummaryOne->getPublictype() === $participantsSummary->getPublictype() &&
                $participantsSummaryOne->getSession() === $participantsSummary->getSession()) {
                $participantsSummaryOne->setCount($participantsSummaryOne->getCount() + $participantsSummary->getCount());

                return false;
            }
        }

        $participantsSummary->setSession($this);
        $this->participantsSummaries->add($participantsSummary);

        return true;
    }

    /**
     * @param ParticipantsSummary $participantsSummary
     *
     * @return bool
     */
    public function removeParticipantsSummary($participantsSummary)
    {
        if ($this->participantsSummaries->contains($participantsSummary)) {
            $this->participantsSummaries->removeElement($participantsSummary);

            return true;
        }

        return false;
    }

    /**
     * Get date range for OpenTBS.
     *
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"Default", "session", "api"})
     *
     * @return string
     */
    public function getDateRange()
    {
        if ( ! $this->datebegin) {
            return '';
        }
        if ( ! $this->dateend || $this->datebegin->format('d/m/y') === $this->dateend->format('d/m/y')) {
            return 'le ' . $this->datebegin->format('d/m/Y');
        }

        return 'du ' . $this->datebegin->format('d/m/Y') . ' au ' . $this->dateend->format('d/m/Y');
    }

    public function __toString()
    {
        return $this->getTraining()->getName().' - '.$this->getDateRange();
    }

    /**
     * @return mixed
     */
    public static function getFormType()
    {
        return AbstractSessionType::class;
    }

    /**
     * @return string
     */
    public static function getType()
    {
        return 'session';
    }

}

<?php

namespace App\Entity\Core;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
 *
 * traduction: session
 */
#[ORM\Table(name: 'session')]
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\HasLifecycleCallbacks]
abstract class AbstractSession implements SerializedAccessRights, \Stringable
{
    // Hook timestampable behavior : updates createdAt, updatedAt fields
    use TimestampableTrait;

    // registration states
    /**
     * @var int
     */
    final public const REGISTRATION_DEACTIVATED = 0;

    /**
     * @var int
     */
    final public const REGISTRATION_CLOSED = 1;

    /**
     * @var int
     */
    final public const REGISTRATION_PRIVATE = 2;

    /**
     * @var int
     */
    final public const REGISTRATION_PUBLIC = 3;

    // registration states
    /**
     * @var int
     */
    final public const STATUS_OPEN = 0;

    /**
     * @var int
     */
    final public const STATUS_REPORTED = 1;

    /**
     * @var int
     */
    final public const STATUS_CANCELED = 2;

    /**
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     * @var AbstractTraining
     * @Serializer\Groups({"session", "inscription", "trainee", "trainer", "api"})
     */
    #[ORM\ManyToOne(targetEntity: 'AbstractTraining', inversedBy: 'sessions')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    protected $training;

    /**
     * @Serializer\Groups({"session", "inscription", "trainee", "trainer", "api"})
     * @var Collection<\App\Entity\Core\AbstractParticipation>
     */
    #[ORM\OneToMany(mappedBy: 'session', targetEntity: 'AbstractParticipation', cascade: ['remove'])]
    protected Collection $participations;

    /**
     * @Serializer\Groups({"session"})
     * @var Collection<\App\Entity\Core\AbstractInscription>
     */
    #[ORM\OneToMany(targetEntity: 'AbstractInscription', mappedBy: 'session', fetch: 'EXTRA_LAZY', cascade: ['remove'])]
    #[ORM\OrderBy(['createdat' => 'DESC'])]
    protected Collection $inscriptions;

    /**
     * @Serializer\Groups({"Default", "session", "api"})
     */
    #[ORM\Column(name: 'promote', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected ?bool $promote = false;

    /**
     *
     * @Serializer\Groups({"Default", "session", "api"})
     */
    #[ORM\Column(name: 'dateBegin', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'Vous devez préciser une date de début.')]
    protected ?\DateTimeInterface $datebegin = null;

    /**
     *
     * @Serializer\Groups({"Default", "session", "api"})
     */
    #[ORM\Column(name: 'dateEnd', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $dateend = null;

    #[ORM\Column(name: 'registration', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected ?int $registration = self::REGISTRATION_CLOSED;

    /**
     * @Serializer\Groups({"session", "training", "inscription", "api"})
     */
    #[ORM\Column(name: 'status', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected ?int $status = self::STATUS_OPEN;

    /**
     *
     * @Serializer\Groups({"Default", "session", "api"})
     */
    #[ORM\Column(name: 'displayOnline', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected ?bool $displayonline = false;

    /**
     * @Serializer\Exclude
     */
    #[ORM\Column(name: 'numberOfRegistrations', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $numberofregistrations = null;

    /**
     * @Serializer\Groups({"session", "training", "inscription", "api"})
     */
    #[ORM\Column(name: 'maximumNumberOfRegistrations', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[Assert\NotBlank]
    protected ?int $maximumnumberofregistrations = 20;

    /**
     * @Serializer\Groups({"session", "training", "api"})
     */
    #[ORM\Column(name: 'limitRegistrationDate', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $limitregistrationdate = null;

    /**
     *
     * @Serializer\Groups({"session"})
     */
    #[ORM\Column(name: 'comments', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $comments = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[ORM\ManyToOne(targetEntity: \App\Entity\Term\Sessiontype::class)]
    #[ORM\JoinColumn(name: 'sessionType_id', onDelete: 'SET NULL')]
    protected ?\App\Entity\Term\Sessiontype $sessiontype = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[ORM\Column(name: 'hourNumber', type: \Doctrine\DBAL\Types\Types::FLOAT)]
    #[Assert\GreaterThan(value: 0, message: "Vous devez renseigner un nombre d'heures")]
    #[Assert\NotNull(message: "Vous devez renseigner un nombre d'heures")]
    protected ?float $hournumber = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[ORM\Column(name: 'dayNumber', type: \Doctrine\DBAL\Types\Types::FLOAT)]
    #[Assert\GreaterThan(value: 0, message: 'Vous devez renseigner un nombre de jours')]
    #[Assert\NotNull(message: 'Vous devez renseigner un nombre de jours')]
    protected ?float $daynumber = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[ORM\Column(name: 'schedule', type: \Doctrine\DBAL\Types\Types::STRING, length: 512, nullable: true)]
    protected ?string $schedule = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[ORM\Column(name: 'place', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $place = null;

    /**
     * @Serializer\Groups({"session"})
     * @var Collection<\App\Entity\Core\ParticipantsSummary>
     */
    #[ORM\OneToMany(mappedBy: 'session', targetEntity: \App\Entity\Core\ParticipantsSummary::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    protected Collection $participantsSummaries;

    /**
     * @var Collection<Material>
     * @Serializer\Groups({"training", "session", "api.attendance"})
     */
    #[ORM\OneToMany(mappedBy: 'session', targetEntity: Material::class, cascade: ['remove', 'persist'])]
    #[ORM\JoinColumn]
    protected Collection $materials;

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
        $this->setId((int)null);
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
    public function setId($id): void
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
    public function setTraining($training): void
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

    public function setParticipations(mixed $participations): void
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
        if (!$this->participations) {
            return '';
        }

        $array = [];
        /** @var AbstractParticipation $participation */
        foreach ($this->participations as $participation) {
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
        foreach ($this->participations as $participation) {
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

    public function setInscriptions(mixed $inscriptions): void
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

    public function setPromote(mixed $promote): void
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

    public function setRegistration(mixed $registration): void
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
    public function setDisplayonline($displayOnline): void
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
        return $this->datebegin ? $this->datebegin->format('Y') : null;
    }

    /**
     * @return int
     * @Serializer\VirtualProperty
     */
    public function getSemester()
    {
        return $this->datebegin ? ceil($this->datebegin->format('m') / 6) : null;
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

    public function setDatebegin(mixed $dateBegin): void
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

    public function setDateend(mixed $dateEnd): void
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

    public function setHournumber(mixed $hourNumber): void
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

    public function setDaynumber(mixed $dayNumber): void
    {
        $this->daynumber = $dayNumber;
    }

    /**
     * @return string
     */
    public function getDuration()
    {
        return $this->hournumber . ' heure(s) sur ' . $this->daynumber . ' jour(s)';
    }

    /**
     * @return string
     */
    public function getPlace(): ?string
    {
        return $this->place;
    }

    /**
     * @param Place $place
     */
    public function setPlace($place): void
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

    public function setSchedule(mixed $schedule): void
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

    public function setComments(mixed $comments): void
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

    public function setStatus(mixed $status): void
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

    public function setMaximumnumberofregistrations(mixed $maximumNumberOfRegistrations): void
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
    public function setSessiontype($sessionType): void
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
        if ($this->status !== self::STATUS_OPEN) {
            return false;
        }

        $dateTime = new \DateTime();

        // check date
        if ($this->datebegin <= $dateTime) {
            return false;
        }

        // check status
        if ($this->registration < self::REGISTRATION_PRIVATE) {
            return false;
        }

        if (!$this->limitregistrationdate) {
            // ok
            return true;
        }

        // ok
        return $this->limitregistrationdate >= $dateTime;
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
        return $this->maximumnumberofregistrations - $this->getNumberofacceptedregistrations();
    }

    /**
     * @return mixed
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"session", "training"})
     */
    public function getNumberofregistrations()
    {
        if ($this->registration === self::REGISTRATION_DEACTIVATED) {
            return $this->numberofregistrations;
        }

        if (empty($this->inscriptions)) {
            return 0;
        }

        return $this->inscriptions->count();
    }

    public function setNumberofregistrations(mixed $numberOfRegistrations): void
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
        if ($this->registration === self::REGISTRATION_DEACTIVATED) {
            return $this->numberofregistrations;
        }

        if (empty($this->inscriptions)) {
            return 0;
        }

        $nAccepted = 0;
        foreach ($this->inscriptions as $inscription) {
            if (($inscription->getInscriptionstatus()->getStatus() === Inscriptionstatus::STATUS_ACCEPTED) || ($inscription->getInscriptionstatus()->getStatus() === Inscriptionstatus::STATUS_CONVOKED)) {
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

    public function setLimitregistrationdate(mixed $limitRegistrationDate): void
    {
        $this->limitregistrationdate = $limitRegistrationDate;
    }

    /**
     * Update the limit registration date.
     *
     */
    #[ORM\PrePersist]
    public function updateLimitregistrationdate(): void
    {
        // if the limit registration date is not set,
        // set it to the day before date begin
        if (!$this->limitregistrationdate) {
            $date = clone $this->datebegin;
            $date->modify('-1 day');
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
    public function setMaterials($materials): void
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
    public function setAllMaterials($allMaterials): void
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
        if ($this->registration === self::REGISTRATION_DEACTIVATED) {
            if ($this->participantsSummaries != null) {
                foreach ($this->participantsSummaries as $participantSummary) {
                    $count += $participantSummary->getCount();
                }
            }
        } elseif ($this->inscriptions != null) {
            /** @var AbstractInscription $inscription */
            foreach ($this->inscriptions as $inscription) {
                if (!$inscription->getPresencestatus()) {
                    continue;
                }
                if ($inscription->getPresencestatus()->getStatus() !== PresenceStatus::STATUS_PRESENT) {
                    continue;
                }
                ++$count;
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
    public function setParticipantsSummaries($participantsSummaries): void
    {
        foreach ($participantsSummaries as $participantSummary) {
            $participantSummary->setSession($this);
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
        foreach ($this->participantsSummaries as $participantSummary) {
            if ($participantSummary->getPublictype() === $participantsSummary->getPublictype() &&
                $participantSummary->getSession() === $participantsSummary->getSession()) {
                $participantSummary->setCount($participantSummary->getCount() + $participantsSummary->getCount());

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

        if (! $this->dateend) {
            return 'le ' . $this->datebegin->format('d/m/Y');
        }
        if ($this->datebegin->format('d/m/y') === $this->dateend->format('d/m/y')) {
            return 'le ' . $this->datebegin->format('d/m/Y');
        }

        return 'du ' . $this->datebegin->format('d/m/Y') . ' au ' . $this->dateend->format('d/m/Y');
    }

    public function __toString(): string
    {
        return $this->training->getName().' - '.$this->getDateRange();
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

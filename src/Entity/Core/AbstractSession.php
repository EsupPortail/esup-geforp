<?php

namespace App\Entity\Core;

use App\Entity\Back\Inscription;
use App\Entity\Back\Participation;
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
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Serializer\Attribute\SerializedName;
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
abstract class AbstractSession implements SerializedAccessRights
{
    // Hook timestampable behavior : updates createdAt, updatedAt fields
    use TimestampableTrait;

    // registration states
    /**
     * @var int
     */
    final public const int REGISTRATION_DEACTIVATED = 0;

    /**
     * @var int
     */
    final public const int REGISTRATION_CLOSED = 1;

    /**
     * @var int
     */
    final public const int REGISTRATION_PRIVATE = 2;

    /**
     * @var int
     */
    final public const int REGISTRATION_PUBLIC = 3;

    // registration states
    /**
     * @var int
     */
    final public const int STATUS_OPEN = 0;

    /**
     * @var int
     */
    final public const int STATUS_REPORTED = 1;

    /**
     * @var int
     */
    final public const int STATUS_CANCELED = 2;

    /**
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['Default', 'api'])]
    protected ?int $id = null;

    /**
     * @var AbstractTraining
     * @Serializer\Groups({"session", "inscription", "trainee", "trainer", "api"})
     */
    #[Groups(['session', 'inscription', 'trainee', 'trainer', 'api'])]
    #[ORM\ManyToOne(targetEntity: 'AbstractTraining', inversedBy: 'sessions')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    protected AbstractTraining $training;

    /**
     * @Serializer\Groups({"session", "inscription", "trainee", "trainer", "api"})
     * @var Collection<AbstractParticipation>
     */
    #[Groups(['session', 'inscription', 'trainee', 'trainer', 'api'])]
    #[ORM\OneToMany(mappedBy: 'session', targetEntity: Participation::class, cascade: ['remove'])]
    protected Collection $participations ;

    /**
     * @Serializer\Groups({"session"})
     * @var Collection<\App\Entity\Core\AbstractInscription>
     */
    #[Groups('session')]
    #[ORM\OneToMany(mappedBy: 'session', targetEntity: Inscription::class, cascade: ['remove'], fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['createdat' => 'DESC'])]
    protected Collection $inscriptions;

    /**
     * @Serializer\Groups({"Default", "session", "api"})
     */
    #[Groups(['Default', 'session', 'api'])]
    #[ORM\Column(name: 'promote', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected ?bool $promote = false;

    /**
     *
     * @Serializer\Groups({"Default", "session", "api"})
     */
    #[Groups(['Default', 'session', 'api'])]
    #[ORM\Column(name: 'dateBegin', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'Vous devez préciser une date de début.')]
    protected \DateTimeInterface $datebegin;

    /**
     *
     * @Serializer\Groups({"Default", "session", "api"})
     */
    #[Groups(['Default', 'session', 'api'])]
    #[ORM\Column(name: 'dateEnd', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $dateend = null;

    #[ORM\Column(name: 'registration', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected int $registration = self::REGISTRATION_CLOSED;


     #[ORM\Column(name="registration", type="integer")]
    protected $registration = self::REGISTRATION_CLOSED;

    #[Groups(['session', 'training', 'inscription', 'api'])]
    #[ORM\Column(name: 'status', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected ?int $status = self::STATUS_OPEN;

    /**
     *
     * @Serializer\Groups({"Default", "session", "api"})
     */
    #[Groups(['Default', 'session', 'api'])]
    #[ORM\Column(name: 'displayOnline', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected ?bool $displayonline = false;

    /**
     * @Serializer\Exclude
     */
    #[Ignore]
    #[ORM\Column(name: 'numberOfRegistrations', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $numberofregistrations;

    /**
     * @Serializer\Groups({"session", "training", "inscription", "api"})
     */
    #[Groups(['session', 'training', 'inscription', 'api'])]
    #[ORM\Column(name: 'maximumNumberOfRegistrations', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $maximumnumberofregistrations  = null;

    /**
     * @Serializer\Groups({"session", "training", "api"})
     */
    #[Groups(['session', 'training', 'api'])]
    #[ORM\Column(name: 'limitRegistrationDate', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $limitregistrationdate = null;

    /**
     *
     * @Serializer\Groups({"session"})
     */
    #[Groups(['session'])]
    #[ORM\Column(name: 'comments', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $comments = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[Groups(['session', 'inscription', 'api'])]
    #[ORM\ManyToOne(targetEntity: \App\Entity\Term\Sessiontype::class)]
    #[ORM\JoinColumn(name: 'sessionType_id', onDelete: 'SET NULL')]
    protected ?\App\Entity\Term\Sessiontype $sessiontype = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[Groups(['session', 'inscription', 'api'])]
    #[ORM\Column(name: 'hourNumber', type: \Doctrine\DBAL\Types\Types::FLOAT)]
    #[Assert\GreaterThan(value: 0, message: "Vous devez renseigner un nombre d'heures")]
    #[Assert\NotNull(message: "Vous devez renseigner un nombre d'heures")]
    protected ?float $hournumber = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[Groups(['session', 'inscription', 'api'])]
    #[ORM\Column(name: 'dayNumber', type: \Doctrine\DBAL\Types\Types::FLOAT)]
    #[Assert\GreaterThan(value: 0, message: 'Vous devez renseigner un nombre de jours')]
    #[Assert\NotNull(message: 'Vous devez renseigner un nombre de jours')]
    protected ?float $daynumber = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[Groups(['session', 'inscription', 'api'])]
    #[ORM\Column(name: 'schedule', type: \Doctrine\DBAL\Types\Types::STRING, length: 512, nullable: true)]
    protected ?string $schedule = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[Groups(['session', 'inscription', 'api'])]
    #[ORM\Column(name: 'place', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $place = null;

    /**
     * @Serializer\Groups({"session"})
     * @var Collection<\App\Entity\Core\ParticipantsSummary>
     */
    #[ORM\OneToMany(mappedBy: 'session', targetEntity: \App\Entity\Core\ParticipantsSummary::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    #[Groups('session')]
    protected Collection $participantsSummaries;

    /**
     * @var Collection<Material>
     * @Serializer\Groups({"training", "session", "api.attendance"})
     */
    #[ORM\OneToMany(mappedBy: 'session', targetEntity: Material::class, cascade: ['remove', 'persist'])]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['training', 'session', 'api.attendence'])]
    protected Collection $materials;

    /**
     * @var ArrayCollection
     * @Serializer\Groups({"api.attendance"})
     */
    #[Groups(['api.attendance'])]
    protected ArrayCollection $allMaterials;


    public function __construct()
    {
        $this->maximumnumberofregistrations = intval($this->maximumnumberofregistrations);
        $this->inscriptions = new ArrayCollection();
        $this->participations = new ArrayCollection();
        $this->participantsSummaries = new ArrayCollection();
        $this->materials = new ArrayCollection();
        $this->datebegin = new \DateTime();
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
    #[VirtualProperty]
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return AbstractTraining
     */
    public function getTraining(): AbstractTraining
    {
        return $this->training;
    }

    /**
     * @param AbstractTraining $training
     */
    public function setTraining(AbstractTraining $training): void
    {
        $this->training = $training;
    }

    /**
     * @return ArrayCollection|Collection
     */
    #[Groups(["session", "inscription", "trainee", "trainer", "api"])]
    #[VirtualProperty]
    public function getParticipations(): Collection
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
    public function addParticipation(AbstractParticipation $participation): bool
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
    public function removeParticipation(AbstractParticipation $participation): bool
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
    public function getTrainersListString(): string
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
    public function getTrainers(): ArrayCollection
    {
        $trainers = new ArrayCollection();
        /** @var AbstractParticipation $participation */
        foreach ($this->participations as $participation) {
            $trainers->add($participation->getTrainer());
        }

        return $trainers;
    }

    /**
     * @return Collection
     */
    public function getInscriptions(): Collection
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
    public function addInscription(\App\Entity\Core\AbstractInscription $inscription): bool
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
    public function removeInscription(\App\Entity\Core\AbstractInscription $inscription): bool
    {
        if ($this->inscriptions->contains($inscription)) {
            $this->inscriptions->removeElement($inscription);

            return true;
        }

        return false;
    }

    /**
     * @return bool|null
     */
    public function getPromote(): ?bool
    {
        return $this->promote;
    }

    public function setPromote(mixed $promote): void
    {
        $this->promote = $promote;
    }

    /**
     * @return int|null
     */
    public function getRegistration(): int
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
    public function isDisplayonline(): ?bool
    {
        return $this->displayonline;
    }

    /**
     * @return bool|null
     */
    public function getDisplayonline(): ?bool
    {
        return $this->displayonline;
    }

    /**
     * @param bool $displayOnline
     */
    public function setDisplayonline(bool $displayOnline): void
    {
        $this->displayonline = $displayOnline;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDatebegin(): ?\DateTimeInterface
    {
        return $this->datebegin;
    }

    /**
     * @return int
     * @Serializer\VirtualProperty
     */
    #[VirtualProperty]
    public function getYear(): int
    {
        return $this->datebegin->format('Y');
    }

    /**
     * @return int
     * @Serializer\VirtualProperty
     */
    #[VirtualProperty]
    public function getSemester(): int
    {
        return ceil($this->datebegin->format('m') / 6);
    }

    /**
     * @return int
     * @Serializer\VirtualProperty
     * @Serializer\Groups("api")
     */
    #[VirtualProperty]
    #[Groups(["api"])]
    public function getSemesterLabel(): int|string
    {
        return ($this->getSemester() < 2 ? '1er' : '2nd').' semestre ';
    }

    public function setDatebegin(mixed $dateBegin): void
    {
        $this->datebegin = $dateBegin;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateend(): ?\DateTimeInterface
    {
        return $this->dateend;
    }

    public function setDateend(mixed $dateEnd): void
    {
        $this->dateend = $dateEnd;
    }

    /**
     * @return float|null
     */
    public function getHournumber(): ?float
    {
        return $this->hournumber;
    }

    public function setHournumber(mixed $hourNumber): void
    {
        $this->hournumber = $hourNumber;
    }

    /**
     * @return float|null
     */
    public function getDaynumber(): ?float
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
    public function getDuration(): string
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
     * @param String $place
     */
    public function setPlace(?String $place): void
    {
        $this->place = $place;
    }

    /**
     * @return string|null
     */
    public function getSchedule(): ?string
    {
        return $this->schedule;
    }

    public function setSchedule(mixed $schedule): void
    {
        $this->schedule = $schedule;
    }

    /**
     * @return string|null
     */
    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(mixed $comments): void
    {
        $this->comments = $comments;
    }

    /**
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(mixed $status): void
    {
        $this->status = $status;
    }

    /**
     * @return int|null
     */
    #[Groups(["session", "training", "Default"])]
    public function getMaximumNumberOfRegistrations(): ?int
    {
        return $this->maximumnumberofregistrations;
    }

    public function setMaximumnumberofregistrations(?int $maximumNumberOfRegistrations): void
    {
        $this->maximumnumberofregistrations = $maximumNumberOfRegistrations;
    }

    /**
     * @return Sessiontype
     */
    public function getSessiontype(): ?Sessiontype
    {
        return $this->sessiontype;
    }

    /**
     * @param Sessiontype $sessionType
     */
    public function setSessiontype(?Sessiontype $sessionType): void
    {
        $this->sessiontype = $sessionType;
    }

    /**
     * Return true if the session is available on the website (private or public_old registration).
     *
     * @return int|null
     */
    public function isAvailable(): ?int
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
    #[VirtualProperty]
    #[Groups(["api"])]
    public function isPublic(): ?int
    {
        return $this->registration === self::REGISTRATION_PUBLIC;
    }

    /**
     * The session is registrable.
     */
    public function isRegistrable(): ?int
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
    #[VirtualProperty]
    #[Groups(["session", "training", "api.training"])]
    public function registrable(): ?int
    {
        return $this->isRegistrable();
    }

    /**
     * Return available places.
     *
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"api.training"})
     */
    #[VirtualProperty]
    #[Groups(["api.training"])]
    public function getAvailablePlaces(): ?int
    {
        return $this->maximumnumberofregistrations - $this->getNumberofacceptedregistrations();
    }

    /**
     * @return int|null
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"session", "training"})
     */
    #[Groups(["session", "training"])]
    #[VirtualProperty]
    public function getNumberofregistrations(): ?int
    {
        if ($this->registration === self::REGISTRATION_DEACTIVATED) {
            return $this->numberofregistrations;
        }
        return $this->getInscriptions()?->count();
    }

    public function setNumberofregistrations(mixed $numberOfRegistrations): void
    {
        $this->numberofregistrations = $numberOfRegistrations;
    }

    /**
     * @return int|null
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"session", "training"})
     */
    #[VirtualProperty]
    #[Groups(["session", "training"])]
    public function getNumberofacceptedregistrations(): ?int
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

    #[VirtualProperty]
    #[Groups(["session", "training"])]
    public function getNumberofparticipants(): int
    {
        $count = 0;

        if ($this->getRegistration() === self::REGISTRATION_DEACTIVATED) {
            if ($this->getParticipantsSummaries() !== null) {
                foreach ($this->getParticipantsSummaries() as $summary) {
                    $count += $summary->getCount();
                }
            }
        } else {
            foreach ($this->getInscriptions() ?? [] as $inscription) {
                if (
                    $inscription->getPresencestatus() &&
                    $inscription->getPresencestatus()->getStatus() === PresenceStatus::STATUS_PRESENT
                ) {
                    ++$count;
                }
            }
        }

        return $count;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getLimitregistrationdate(): ?\DateTimeInterface
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
    public function getMaterials(): ArrayCollection|Collection
    {
        return $this->materials;
    }

    /**
     * @param ArrayCollection $materials
     */
    public function setMaterials(ArrayCollection $materials): void
    {
        $this->materials = $materials;
    }

    /**
     * @param Material $material
     *
     * @return bool
     */
    public function addMaterial(Material $material): Collection
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
    public function getAllMaterials(): ArrayCollection
    {
        return $this->allMaterials;
    }

    /**
     * @param ArrayCollection $allMaterials
     */
    public function setAllMaterials(ArrayCollection $allMaterials): void
    {
        $this->allMaterials = $allMaterials;
    }

    /**
     * @return ArrayCollection
     */
    public function getParticipantsSummaries(): ArrayCollection|Collection
    {
        return $this->participantsSummaries;
    }

    /**
     * @param ArrayCollection $participantsSummaries
     */
    public function setParticipantsSummaries(ArrayCollection $participantsSummaries): void
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
    public function addParticipantsSummary(\App\Entity\Core\ParticipantsSummary $participantsSummary): Collection
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
    public function removeParticipantsSummary(\App\Entity\Core\ParticipantsSummary $participantsSummary): Collection
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
    #[VirtualProperty]
    public function getDateRange(): string
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
    public static function getFormType(): mixed
    {
        return AbstractSessionType::class;
    }

    /**
     * @return string
     */
    public static function getType(): string
    {
        return 'session';
    }

}

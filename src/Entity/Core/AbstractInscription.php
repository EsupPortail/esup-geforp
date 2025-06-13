<?php

namespace App\Entity\Core;

use App\Entity\Back\Session;
use App\Form\Type\BaseInscriptionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\UniqueConstraint;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Term\Presencestatus;
use App\Entity\Term\Inscriptionstatus;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\AccessRight\SerializedAccessRights;
use App\Entity\Core\TimestampableTrait;

/**
 * Trainee.
 *
 */
#[ORM\Table(name: 'inscription')]
#[UniqueConstraint(name: 'traineesession_idx', columns: ['trainee_id', 'session_id'])]
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['trainee', 'session'], message: 'Cet utilisateur est déjà inscrit à cette session !')]
abstract class AbstractInscription implements SerializedAccessRights
{
    // Hook timestampable behavior : updates createdAt, updatedAt fields
    use TimestampableTrait;

    /**
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['Default', 'api'])]
    protected ?int $id = null;

    /**
     * @var AbstractTrainee
     * @Serializer\Groups({"inscription", "session"})
     */
    #[Groups(['inscription', 'session', 'Default'])]
    #[ORM\ManyToOne(targetEntity: 'AbstractTrainee', inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(name: 'trainee_id')]
    #[Assert\NotNull(message: 'Vous devez sélectionner un stagiaire.')]
    #[MaxDepth(1)]
    protected AbstractTrainee $trainee;

    /**
     * @var AbstractSession
     * @Serializer\Groups({"inscription", "trainee", "api"})
     */
    #[Groups(['inscription', 'trainee', 'api'])]
    #[ORM\ManyToOne(targetEntity: Session::class, inversedBy: 'inscriptions')]
    #[ORM\JoinColumn(name: 'session_id', referencedColumnName: 'id')]
    #[Assert\NotNull]
    protected AbstractSession $session;

    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[Groups(['Default', 'api'])]
    #[ORM\ManyToOne(targetEntity: \App\Entity\Term\Inscriptionstatus::class)]
    #[ORM\JoinColumn(name: 'inscription_status_id')]
    #[Assert\NotNull(message: "Vous devez spécifier un status d'inscription.")]
    protected ?\App\Entity\Term\Inscriptionstatus $inscriptionstatus;

    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[Groups(['Default', 'api'])]
    #[ORM\ManyToOne(targetEntity: \App\Entity\Term\Presencestatus::class)]
    #[ORM\JoinColumn(name: 'presence_status_id')]
    protected ?\App\Entity\Term\Presencestatus $presencestatus = null;

    /**
     * @var bool
     */
    protected bool $sendinscriptionstatusmail = false;


    public function __construct()
    {
        $this->presencestatus = new Presencestatus();
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param Inscriptionstatus
     */
    public function setInscriptionstatus(?Inscriptionstatus $inscriptionStatus): void
    {
        $this->inscriptionstatus = $inscriptionStatus;
    }

    /**
     * @return Inscriptionstatus
     */
    public function getInscriptionstatus(): ?Inscriptionstatus
    {
        return $this->inscriptionstatus;
    }

    /**
     * @param Presencestatus
     */
    public function setPresencestatus(?Presencestatus $presenceStatus): void
    {
        $this->presencestatus = $presenceStatus;
    }

    /**
     * @return Presencestatus
     */
    public function getPresencestatus(): ?Presencestatus
    {
        return $this->presencestatus;
    }

    /**
     * @param AbstractSession
     */
    public function setSession($session): void
    {
        $this->session = $session;
    }

    /**
     * @return AbstractSession
     */
    public function getSession(): AbstractSession
    {
        return $this->session;
    }

    /**
     * @param AbstractTrainee
     */
    public function setTrainee($trainee): void
    {
        $this->trainee = $trainee;
    }

    /**
     * @return AbstractTrainee
     */
    public function getTrainee(): AbstractTrainee
    {
        return $this->trainee;
    }

    /**
     * @return bool
     */
    public function isSendinscriptionstatusmail(): bool
    {
        return $this->sendinscriptionstatusmail;
    }

    /**
     * @param bool $sendinscriptionstatusmail
     */
    public function setSendinscriptionstatusmail($sendinscriptionstatusmail): void
    {
        $this->sendinscriptionstatusmail = $sendinscriptionstatusmail;
    }

    /**
     * Set the default inscription status (1).
     *
     */
    #[ORM\PreUpdate]
    #[ORM\PrePersist]
    public function setDefaultInscriptionstatus(\Doctrine\Persistence\Event\LifecycleEventArgs $lifecycleEventArgs): void
    {
        if (!$this->inscriptionstatus) {
            $entityRepository = $lifecycleEventArgs->getObjectManager()->getRepository(Inscriptionstatus::class);
            $inscriptionstatus = $entityRepository->findOneBy(['machineName' => 'waiting']);
            $this->setInscriptionstatus($inscriptionstatus);
        }
    }

    /**
     * @return AbstractOrganization
     */
    public function getOrganization(): AbstractOrganization
    {
        return $this->session->getTraining()->getOrganization();
    }

    /**
     * @return string
     */
    public static function getFormType(): string
    {
        return BaseInscriptionType::class;
    }

    /**
     * @return string
     */
    public static function getType(): string
    {
        return 'inscription';
    }
}

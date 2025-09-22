<?php

namespace App\Entity\Core;

use AllowDynamicProperties;
use App\Entity\Back\Institution;
use App\Entity\PersonTrait\PersonTrait;
use App\Repository\TraineeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\PersonTrait\AccountTrait;
use App\Entity\Core\AbstractOrganization;
use App\AccessRight\SerializedAccessRights;
use App\Entity\PersonTrait\ProfessionalSituationTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use App\Form\Type\AbstractTraineeType;
use Symfony\Component\Serializer\Attribute\Ignore;
/**
 * Trainee.
 *
 */
#[ORM\Table(name: 'trainee')]
#[ORM\UniqueConstraint(name: 'emailUnique', columns: ['email'])]
#[ORM\Entity(repositoryClass: TraineeRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\HasLifecycleCallbacks]abstract class AbstractTrainee
{
    // Hook timestampable behavior : updates createdAt, updatedAt fields
    use TimestampableTrait;
    use AccountTrait;
    use ProfessionalSituationTrait;
    use PersonTrait;
    /**
     * @var int id
     *
     */

    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $id;

    /**
     * @Serializer\Groups({"trainee", "session", "api.profile", "api.token"})})
     * @Assert\NotNull(message="Vous devez renseigner un établissement ou une entreprise.", groups="api.profile")
     */
    #[ORM\ManyToOne(targetEntity: AbstractInstitution::class)]
    #[Assert\NotNull(message: 'Vous devez renseigner un établissement.')]
    #[Groups(['trainee', 'session', 'api.profile', 'api.token', 'inscription'])]
    protected ?AbstractInstitution $institution = null;

    /**
     * @Serializer\Groups({"trainee"})
     * @var Collection<int, AbstractInscription>|AbstractInscription[]
     */
    #[Groups(['trainee'])]
    #[ORM\OneToMany(mappedBy: 'trainee', targetEntity: AbstractInscription::class, cascade: ['remove'])]
    protected Collection $inscriptions;

    /**
     * Construct.
     */
    function __construct()
    {
        $this->inscriptions = new ArrayCollection();
        $this->isactive = true;
        $this->salt = md5(uniqid('', true));
        $this->password = md5(uniqid('', true));
        $this->addresstype = 0;
        $this->lastname = '';
        $this->phonenumber = '';
        $this->firstname = '';
        $this->institution = new Institution();
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setInscriptions(mixed $inscriptions): void
    {
        $this->inscriptions = $inscriptions;
    }

    public function getInscriptions(): Collection
    {
        return $this->inscriptions;
    }

    /**
     * @param void $institution
     */
    public function setInstitution(AbstractInstitution $institution): void
    {
        $this->institution = $institution;
    }

    public function getInstitution(): ?AbstractInstitution
    {
        return $this->institution;
    }

    public function getFormationName(): ?string
    {
        $sessions = $this->getSessions()->toArray();

        if (empty($sessions)) {
            return null;
        }

        usort($sessions, fn($a, $b) => $a->getDateBegin() <=> $b->getDateBegin());

        $last = end($sessions);
        return $last ? $last->getName() : null;
    }

    public function getCivilite(): ?string
    {
        $sessions = $this->getSessions()->toArray();

        if (empty($sessions)) {
            return null;
        }

        usort($sessions, fn($a, $b) => $a->getDateBegin() <=> $b->getDateBegin());

        $last = end($sessions);
        if (!$last || $last->getTrainers()->isEmpty()) {
            return null;
        }

        $trainer = $last->getTrainers()->first();
        return $trainer ? $trainer->getTitle() : null;
    }

    public function getCommentsSession(): ?string
    {
        $sessions = $this->getSessions()->toArray();

        if (empty($sessions)) {
            return null;
        }
        usort($sessions, fn($a, $b) => $a->getDateBegin() <=> $b->getDateBegin());

        $last = end($sessions);
        return $last ? $last->getComments() : null;
    }

    public function getDateSession(): ?string
    {
        $sessions = $this->getSessions()->toArray();

        if (empty($sessions)) {
            return null;
        }
        usort($sessions, fn($a, $b) => $a->getDateBegin() <=> $b->getDateBegin());

        $last = end($sessions);
        return $last ? $last->getDatesString() : null;
    }

    public function getSessions(): Collection
    {
        return $this->getInscriptions()
            ->map(fn($inscription) => $inscription->getSession())
            ->filter(fn($session) => $session !== null);
    }

    /**
     * {}
     */
    public function getRoles(): array
    {
        return ['ROLE_TRAINEE'];
    }

    /**
     * @see \Serializable::serialize()
     */
    public function serialize(): ?string
    {
        return serialize(
            [$this->id]
        );
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize(string $data): void
    {
        [$this->id] = unserialize($data);
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }

    /**
     * loadValidatorMetadata.
     *
     */
    public static function loadValidatorMetadata(ClassMetadata $classMetadata): void
    {
        // PersonTrait
        $classMetadata->addPropertyConstraint('title', new Assert\NotBlank(['message' => 'Vous devez renseigner une civilité.']));
        $classMetadata->addPropertyConstraint('lastname', new Assert\NotBlank(['message' => 'Vous devez renseigner un nom de famille.', 'groups' => 'api']));
        $classMetadata->addPropertyConstraint('firstname', new Assert\NotBlank(['message' => 'Vous devez renseigner un prénom.']));

        // CoordinateTrait
        $classMetadata->addPropertyConstraint('address', new Assert\NotBlank(['message' => 'Vous devez renseigner une adresse.', 'groups' => 'api.profile']));
        $classMetadata->addPropertyConstraint('zip', new Assert\NotBlank(['message' => 'Vous devez renseigner un code postal.', 'groups' => 'api.profile']));
        $classMetadata->addPropertyConstraint('city', new Assert\NotBlank(['message' => 'Vous devez renseigner une ville.', 'groups' => 'api.profile']));
        $classMetadata->addPropertyConstraint('email', new Assert\NotBlank(['message' => 'Vous devez renseigner un email.']));
        $classMetadata->addPropertyConstraint('phonenumber', new Assert\NotBlank(['message' => 'Vous devez renseigner un numéro de téléphone.', 'groups' => 'api.profile']));

        // PublicCategoryTrait
        $classMetadata->addPropertyConstraint('publictype', new Assert\NotNull(['message' => 'Vous devez renseigner un type de personnel.', 'groups' => 'api.profile']));
    }

    /**
     * @return mixed
     */
    static public function getFormType(): string
    {
        return AbstractTraineeType::class;
    }

    static public function getType(): string
    {
        return 'trainee';
    }
}

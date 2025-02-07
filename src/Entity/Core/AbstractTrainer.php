<?php

namespace App\Entity\Core;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\AccessRight\SerializedAccessRights;
use App\Form\Type\AbstractTrainerType;
use App\Entity\PersonTrait\CoordinatesTrait;
use App\Entity\PersonTrait\PersonTrait;
use App\Entity\PersonTrait\ProfessionalSituationTrait;
use App\Entity\Term\Trainertype;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Trainer.
 *
 */
#[ORM\Table(name: 'trainer')]
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[UniqueEntity(fields: ['email', 'organization'], message: 'Cette adresse email est déjà utilisée.', ignoreNull: true, groups: ['Default', 'trainer'])]
abstract class AbstractTrainer implements SerializedAccessRights
{
    // Hook timestampable behavior : updates createdAt, updatedAt fields
    use TimestampableTrait;

    use PersonTrait;
    use CoordinatesTrait;
    use ProfessionalSituationTrait;

    /**
     *
     * @Serializer\Groups({"Default", "trainer", "session", "api.training"})
     */
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     * @var AbstractOrganization
     * @Serializer\Groups({"trainer"})
     */
    #[ORM\ManyToOne(targetEntity: 'AbstractOrganization')]
    #[ORM\JoinColumn]
    protected $organization;

    /**
     * @var \Doctrine\Common\Collections\Collection<\App\Entity\Core\AbstractParticipation>
     * @Serializer\Exclude
     */
    #[ORM\OneToMany(targetEntity: 'AbstractParticipation', mappedBy: 'trainer', cascade: ['remove'])]
    protected \Doctrine\Common\Collections\Collection $participations;

    /**
     * @Serializer\Groups({"trainer"})
     */
    #[ORM\ManyToOne(targetEntity: \App\Entity\Term\Trainertype::class)]
    #[ORM\JoinColumn(name: 'trainer_type_id')]
    protected ?\App\Entity\Term\Trainertype $trainertype = null;

    /**
     * @Serializer\Groups({"trainer"})
     */
    #[ORM\Column(name: 'is_archived', type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected ?bool $isarchived = null;

    /**
     * @Serializer\Groups({"trainer", "api.training", "api.trainer"})
     */
    #[ORM\Column(name: 'is_allow_send_mail', type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected ?bool $isallowsendmail = false;

    /**
     * @Serializer\Groups({"trainer"})
     */
    #[ORM\Column(name: 'is_organization', type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected ?bool $isorganization = null;

    /**
     * @Serializer\Groups({"trainer"})
     */
    #[ORM\Column(name: 'is_public', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected ?bool $ispublic = null;

    /**
     * @Serializer\Groups({"trainer"})
     */
    #[ORM\Column(name: 'comments', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $comments = null;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
    }

    /**
     * Remove properties related to another organization, except excluded ones.
     */
    public function changePropertiesOrganization(): void
    {
    }

    /**
     * @return int
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
     * @param AbstractOrganization
     */
    public function setOrganization($organization): void
    {
        $this->organization = $organization;
    }

    /**
     * @return ArrayCollection
     */
    public function getParticipations()
    {
        return $this->participations;
    }

    /**
     * @param ArrayCollection $participations
     */
    public function setParticipations($participations): void
    {
        $this->participations = $participations;
    }

    /**
     * Return sessions from participations
     * Used to not to have update all publipost templates.
     *
     * @return ArrayCollection
     */
    public function getSessions()
    {
        $sessions = new ArrayCollection();
        foreach ($this->participations as $participation) {
            $sessions->add($participation->getSession());
        }

        return $sessions;
    }

    /**
     * @return Trainertype
     */
    public function getTrainertype()
    {
        return $this->trainertype;
    }

    /**
     * @param Trainertype $trainerType
     */
    public function setTrainertype($trainerType): void
    {
        $this->trainertype = $trainerType;
    }

    /**
     * @return bool
     */
    public function isIsarchived()
    {
        return $this->isarchived;
    }

    /**
     * @param bool $isArchived
     */
    public function setIsarchived($isArchived): void
    {
        $this->isarchived = $isArchived;
    }

    /**
     * @return bool
     */
    public function isIsallowsendmail()
    {
        return $this->isallowsendmail;
    }

    /**
     * @param bool $isAllowSendMail
     */
    public function setIsallowsendmail($isAllowSendMail): void
    {
        $this->isallowsendmail = $isAllowSendMail;
    }

    /**
     * @return bool
     */
    public function getIsorganization()
    {
        return $this->isorganization;
    }

    /**
     * @param bool $isOrganization
     */
    public function setIsorganization($isOrganization): void
    {
        $this->isorganization = $isOrganization;
    }

    /**
     * @return bool
     */
    public function isIspublic()
    {
        return $this->ispublic;
    }

    /**
     * @param bool $isPublic
     */
    public function setIspublic($isPublic): void
    {
        $this->ispublic = $isPublic;
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
     * @return mixed
     */
    public static function getFormType()
    {
        return AbstractTrainerType::class;
    }

    /**
     * loadValidatorMetadata.
     *
     */
    public static function loadValidatorMetadata(ClassMetadata $classMetadata): void
    {
        // PersonTrait
        $classMetadata->addPropertyConstraint('title', new Assert\NotBlank(['message' => 'Vous devez renseigner une civilité.']));
        $classMetadata->addPropertyConstraint('firstname', new Assert\NotBlank(['message' => 'Vous devez renseigner un prénom.']));
        $classMetadata->addPropertyConstraint('lastname', new Assert\NotBlank(['message' => 'Vous devez renseigner un nom de famille.']));
        $classMetadata->addPropertyConstraint('email', new Assert\NotBlank(['message' => 'Vous devez renseigner un email.']));

    }

    /**
     * @return string
     */
    public static function getType()
    {
        return 'trainer';
    }
}

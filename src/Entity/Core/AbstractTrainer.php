<?php

namespace App\Entity\Core;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use App\Security\Authorization\AccessRight\SerializedAccessRights;
use App\Form\Type\AbstractTrainerType;
use App\Entity\Core\PersonTrait\CoordinatesTrait;
use App\Entity\Core\PersonTrait\PersonTrait;
use App\Entity\Core\PersonTrait\ProfessionalSituationTrait;
use App\Entity\Core\Term\TrainerType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Trainer.
 *
 * @ORM\Table(name="trainer")
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @UniqueEntity(fields={"email", "organization"}, message="Cette adresse email est déjà utilisée.", ignoreNull=true, groups={"Default", "trainer"})
 */
abstract class AbstractTrainer implements SerializedAccessRights
{
//    use ORMBehaviors\Timestampable\Timestampable;
    use PersonTrait;
    use CoordinatesTrait;
    use ProfessionalSituationTrait;

    /**
     * @var int id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"Default", "trainer", "session", "api.training"})
     */
    protected $id;

    /**
     * @var AbstractOrganization
     * @ORM\ManyToOne(targetEntity="AbstractOrganization")
     * @ORM\JoinColumn(nullable=true)
     * @Serializer\Groups({"trainer"})
     */
    protected $organization;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="AbstractParticipation", mappedBy="trainer", cascade={"remove"})
     * @Serializer\Exclude
     */
    protected $participations;

    /**
     * @var TrainerType
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\Term\TrainerType")
     * @ORM\JoinColumn(nullable=true)
     * @Serializer\Groups({"trainer"})
     */
    protected $trainerType;

    /**
     * @var bool
     * @ORM\Column(name="is_archived", type="boolean", nullable=true)
     * @Serializer\Groups({"trainer"})
     */
    protected $isArchived;

    /**
     * @var bool
     * @ORM\Column(name="is_allow_send_mail", type="boolean", nullable=true)
     * @Serializer\Groups({"trainer", "api.training", "api.trainer"})
     */
    protected $isAllowSendMail = false;

    /**
     * @var bool
     * @ORM\Column(name="is_organization", type="boolean", nullable=true)
     * @Serializer\Groups({"trainer"})
     */
    protected $isOrganization;

    /**
     * @var bool
     * @ORM\Column(name="is_public", type="boolean")
     * @Serializer\Groups({"trainer"})
     */
    protected $isPublic;

    /**
     * @var string
     * @ORM\Column(name="comments", type="text", nullable=true)
     * @Serializer\Groups({"trainer"})
     */
    protected $comments;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
    }

    /**
     * Remove properties related to another organization, except excluded ones.
     */
    public function changePropertiesOrganization()
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
    public function setOrganization($organization)
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
    public function setParticipations($participations)
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
        foreach ($this->getParticipations() as $participation) {
            $sessions->add($participation->getSession());
        }

        return $sessions;
    }

    /**
     * @return TrainerType
     */
    public function getTrainerType()
    {
        return $this->trainerType;
    }

    /**
     * @param TrainerType $trainerType
     */
    public function setTrainerType($trainerType)
    {
        $this->trainerType = $trainerType;
    }

    /**
     * @return bool
     */
    public function isIsArchived()
    {
        return $this->isArchived;
    }

    /**
     * @param bool $isArchived
     */
    public function setIsArchived($isArchived)
    {
        $this->isArchived = $isArchived;
    }

    /**
     * @return bool
     */
    public function isIsAllowSendMail()
    {
        return $this->isAllowSendMail;
    }

    /**
     * @param bool $isAllowSendMail
     */
    public function setIsAllowSendMail($isAllowSendMail)
    {
        $this->isAllowSendMail = $isAllowSendMail;
    }

    /**
     * @return bool
     */
    public function getIsOrganization()
    {
        return $this->isOrganization;
    }

    /**
     * @param bool $isOrganization
     */
    public function setIsOrganization($isOrganization)
    {
        $this->isOrganization = $isOrganization;
    }

    /**
     * @return bool
     */
    public function isIsPublic()
    {
        return $this->isPublic;
    }

    /**
     * @param bool $isPublic
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;
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
     * @return mixed
     */
    public static function getFormType()
    {
        return AbstractTrainerType::class;
    }

    /**
     * loadValidatorMetadata.
     *
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        // PersonTrait
        $metadata->addPropertyConstraint('title', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner une civilité.',
        )));
        $metadata->addPropertyConstraint('firstName', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner un prénom.',
        )));
        $metadata->addPropertyConstraint('lastName', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner un nom de famille.',
        )));
    }

    /**
     * @return string
     */
    public static function getType()
    {
        return 'trainer';
    }
}

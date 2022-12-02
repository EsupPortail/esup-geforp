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
 * @ORM\Table(name="trainer")
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @UniqueEntity(fields={"email", "organization"}, message="Cette adresse email est déjà utilisée.", ignoreNull=true, groups={"Default", "trainer"})
 */
abstract class AbstractTrainer implements SerializedAccessRights
{
    // Hook timestampable behavior : updates createdAt, updatedAt fields
    use TimestampableTrait;

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
     * @var Trainertype
     * @ORM\ManyToOne(targetEntity="App\Entity\Term\Trainertype")
     * @ORM\JoinColumn(name="trainer_type_id", nullable=true)
     * @Serializer\Groups({"trainer"})
     */
    protected $trainertype;

    /**
     * @var bool
     * @ORM\Column(name="is_archived", type="boolean", nullable=true)
     * @Serializer\Groups({"trainer"})
     */
    protected $isarchived;

    /**
     * @var bool
     * @ORM\Column(name="is_allow_send_mail", type="boolean", nullable=true)
     * @Serializer\Groups({"trainer", "api.training", "api.trainer"})
     */
    protected $isallowsendmail = false;

    /**
     * @var bool
     * @ORM\Column(name="is_organization", type="boolean", nullable=true)
     * @Serializer\Groups({"trainer"})
     */
    protected $isorganization;

    /**
     * @var bool
     * @ORM\Column(name="is_public", type="boolean")
     * @Serializer\Groups({"trainer"})
     */
    protected $ispublic;

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
     * @return Trainertype
     */
    public function getTrainertype()
    {
        return $this->trainertype;
    }

    /**
     * @param Trainertype $trainerType
     */
    public function setTrainertype($trainerType)
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
    public function setIsarchived($isArchived)
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
    public function setIsallowsendmail($isAllowSendMail)
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
    public function setIsorganization($isOrganization)
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
    public function setIspublic($isPublic)
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
        $metadata->addPropertyConstraint('firstname', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner un prénom.',
        )));
        $metadata->addPropertyConstraint('lastname', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner un nom de famille.',
        )));
        $metadata->addPropertyConstraint('email', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner un email.',
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

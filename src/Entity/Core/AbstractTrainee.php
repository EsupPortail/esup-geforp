<?php

namespace App\Entity\Core;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use App\Entity\Core\PersonTrait\AccountTrait;
use App\Entity\Core\AbstractOrganization;
use App\Security\Authorization\AccessRight\SerializedAccessRights;
use App\Entity\Core\PersonTrait\ProfessionalSituationTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use App\Form\BaseTraineeType;

/**
 * Trainee.
 *
 * @ORM\Table(name="trainee", uniqueConstraints={@ORM\UniqueConstraint(name="emailUnique", columns={"email"})}))
 * @ORM\Entity(repositoryClass="App\Repository\TraineeRepository")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields={"email"}, message="Cette adresse email est déjà utilisée.")
 */
abstract class AbstractTrainee implements UserInterface, \Serializable, SerializedAccessRights
{
//    use ORMBehaviors\Timestampable\Timestampable;
    use AccountTrait;
    use ProfessionalSituationTrait;

    /**
     * @var int id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var AbstractOrganization Organization
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\AbstractOrganization")
     * @Assert\NotNull(message="Vous devez renseigner un centre de rattachement.")
     * @Serializer\Groups({"trainee", "session", "api.profile", "api.token"})})
     */
    protected $organization;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Core\AbstractInscription", mappedBy="trainee", cascade={"remove"})
     * @Serializer\Groups({"trainee"})
     */
    protected $inscriptions;

    /**
     * Construct.
     */
    function __construct()
    {
        $this->inscriptions = new ArrayCollection();
        $this->isActive = true;
        $this->salt     = md5(uniqid(null, true));
        $this->password = md5(uniqid(null, true));
        $this->addressType = 0;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $inscriptions
     */
    public function setInscriptions($inscriptions)
    {
        $this->inscriptions = $inscriptions;
    }

    /**
     * @return ArrayCollection
     */
    public function getInscriptions()
    {
        return $this->inscriptions;
    }

    /**
     * @param Organization $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return array('ROLE_TRAINEE');
    }

    /**
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return serialize(
            array(
                $this->id,
            )
        );
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list($this->id) = unserialize($serialized);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getFullName();
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
        $metadata->addPropertyConstraint('lastName', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner un nom de famille.',
        )));
        $metadata->addPropertyConstraint('firstName', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner un prénom.',
        )));

        // CoordinateTrait
        $metadata->addPropertyConstraint('address', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner une adresse.',
            'groups'  => 'api.profile',
        )));
        $metadata->addPropertyConstraint('zip', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner un code postal.',
            'groups'  => 'api.profile',
        )));
        $metadata->addPropertyConstraint('city', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner une ville.',
            'groups'  => 'api.profile',
        )));
        $metadata->addPropertyConstraint('email', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner un email.',
        )));
        $metadata->addPropertyConstraint('phoneNumber', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner un numéro de téléphone.',
            'groups'  => 'api.profile',
        )));

        // ProfessionalSituationTrait
        $metadata->addPropertyConstraint('institution', new Assert\NotNull(array(
            'message' => 'Vous devez renseigner un établissement ou une entreprise.',
            'groups'  => 'api.profile',
        )));

        // PublicCategoryTrait
        $metadata->addPropertyConstraint('publicType', new Assert\NotNull(array(
            'message' => 'Vous devez renseigner un type de personnel.',
            'groups'  => 'api.profile',
        )));
    }

    /**
     * @return mixed
     */
    static public function getFormType()
    {
        return BaseTraineeType::class;
    }

    /**
     * @return string
     */
    static public function getType()
    {
        return 'trainee';
    }
}

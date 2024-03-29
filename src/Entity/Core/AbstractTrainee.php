<?php

namespace App\Entity\Core;

use App\Entity\Back\Institution;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\PersonTrait\AccountTrait;
use App\Entity\Core\AbstractOrganization;
use App\AccessRight\SerializedAccessRights;
use App\Entity\PersonTrait\ProfessionalSituationTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use App\Form\Type\AbstractTraineeType;

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
    // Hook timestampable behavior : updates createdAt, updatedAt fields
    use TimestampableTrait;

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
     * @var AbstractInstitution Institution
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\AbstractInstitution")
     * @Assert\NotNull(message="Vous devez renseigner un établissement.")
     * @Serializer\Groups({"trainee", "session", "api.profile", "api.token"})})
     */
    protected $institution;

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
        $this->isactive = true;
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
     * @param Institution $institution
     */
    public function setInstitution($institution)
    {
        $this->institution = $institution;
    }

    /**
     * @return Institution
     */
    public function getInstitution()
    {
        return $this->institution;
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
        $metadata->addPropertyConstraint('lastname', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner un nom de famille.',
        )));
        $metadata->addPropertyConstraint('firstname', new Assert\NotBlank(array(
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
        $metadata->addPropertyConstraint('phonenumber', new Assert\NotBlank(array(
            'message' => 'Vous devez renseigner un numéro de téléphone.',
            'groups'  => 'api.profile',
        )));

        // ProfessionalSituationTrait
        $metadata->addPropertyConstraint('institution', new Assert\NotNull(array(
            'message' => 'Vous devez renseigner un établissement ou une entreprise.',
            'groups'  => 'api.profile',
        )));

        // PublicCategoryTrait
        $metadata->addPropertyConstraint('publictype', new Assert\NotNull(array(
            'message' => 'Vous devez renseigner un type de personnel.',
            'groups'  => 'api.profile',
        )));
    }

    /**
     * @return mixed
     */
    static public function getFormType()
    {
        return AbstractTraineeType::class;
    }

    /**
     * @return string
     */
    static public function getType()
    {
        return 'trainee';
    }
}

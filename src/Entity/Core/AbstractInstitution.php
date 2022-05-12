<?php

namespace App\Entity\Core;

use App\Form\Type\BaseInstitutionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\Core\PersonTrait\CoordinatesTrait;
use App\Entity\Core\AbstractOrganization;
use App\AccessRight\SerializedAccessRights;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use App\Form\Type\BaseInstitutionType as FormType;

/**
 * Institution.
 *
 * @ORM\Table(name="institution")
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 */
abstract class AbstractInstitution implements SerializedAccessRights
{
    // Hook timestampable behavior : updates createdAt, updatedAt fields
    use TimestampableTrait;

    use CoordinatesTrait;

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
     * @var AbstractOrganization Organization
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\AbstractOrganization")
     * @Assert\NotNull(message="Vous devez renseigner un centre de rattachement.")
     * @Serializer\Groups({"Default", "api", "api.institution"})})
     */
    protected $organization;

    /**
     * @var string name
     * @ORM\Column(name="name", type="string", length=512)
     * @Assert\NotBlank(message="Vous devez renseigner un nom d'établissement.")
     * @Serializer\Groups({"Default", "api"})
     */
    protected $name;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }

    /**
     * @return Organization Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    function __toString()
    {
        return $this->getName();
    }

    public static function getFormType()
    {
        return BaseInstitutionType::class;
    }

    public static function getType()
    {
        return 'institution';
    }
}

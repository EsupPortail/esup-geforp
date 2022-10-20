<?php

namespace App\Entity\Core;

use App\Entity\Term\Domain;
use App\Form\Type\BaseInstitutionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\PersonTrait\CoordinatesTrait;
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
     * @var string name
     * @ORM\Column(name="name", type="string", length=512)
     * @Assert\NotBlank(message="Vous devez renseigner un nom d'établissement.")
     * @Serializer\Groups({"Default", "api"})
     */
    protected $name;

    /**
     * @var string idp
     * @ORM\Column(name="idp", type="string", length=512, nullable=true)
     * @Serializer\Groups({"Default", "api"})
     */
    protected $idp;

    /**
     * @var ArrayCollection
     * @ORM\ManyToMany(targetEntity="App\Entity\Term\Domain")
     * @ORM\JoinTable(name="institution__institution_domain",
     *      joinColumns={@ORM\JoinColumn(name="institution_id", referencedColumnName="id", onDelete="cascade")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="domain_id", referencedColumnName="id", onDelete="cascade")}
     * )
     * @Serializer\Groups({"Default", "api"})
     */
    protected $domains;

    public function __construct()
    {
        $this->domains = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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

    /**
     * @return string
     */
    public function getIdp()
    {
        return $this->idp;
    }

    /**
     * @param string $idp
     */
    public function setIdp($idp)
    {
        $this->idp = $idp;
    }

    /**
     * @return mixed
     */
    public function getDomains()
    {
        return $this->domains;
    }

    /**
     * @param mixed $domains
     */
    public function setDomains($domains)
    {
        $this->domains = $domains;
    }

    /**
     * @param Domain $domain
     *
     * @return bool
     */
    public function addDomain($domain)
    {
        if (!$this->domains->contains($domain)) {
            $this->domains->add($domain);

            return true;
        }

        return false;
    }

    /**
     * @param Domain $domain
     *
     * @return bool
     */
    public function removeDomain($domain)
    {
        if ($this->domains->contains($domain)) {
            $this->domains->removeElement($domain);

            return true;
        }

        return false;
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

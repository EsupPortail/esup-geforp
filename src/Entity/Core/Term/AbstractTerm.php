<?php

namespace App\Entity\Core\Term;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Core\AbstractOrganization;

/**
 * Class AbstractTerm.
 *
 * @ORM\MappedSuperclass()
 */
abstract class AbstractTerm implements VocabularyInterface
{
    use SortableTrait;

    /**
     * @var string
     */
    protected $vocabularyId;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"Default", "api"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Assert\NotBlank()
     * @Serializer\Groups({"Default", "api"})
     */
    private $name;

    /**
     * @var bool
     * @ORM\Column(name="private", type="boolean")
     * @Serializer\Exclude
     */
    private $private = false;

    private $label = null;

    /**
     * @var AbstractOrganization
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\AbstractOrganization")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    protected $organization;

    /**
     * @var string
     * @ORM\Column(name="machine_name", type="string", length=255, nullable=true)
     */
    protected $machinename;

    /**
     * @return mixed
     */
    abstract public function getVocabularyName();

    /**
     * @param $label
     */
    public function setVocabularyLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getVocabularyLabel()
    {
        return $this->label ? $this->label : $this->getVocabularyName();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getName();
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
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getPrivate()
    {
        return $this->private;
    }

    /**
     * @param mixed $private
     */
    public function setPrivate($private)
    {
        $this->private = $private;
    }

    /**
     * @param AbstractOrganization $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }

    /**
     * @return AbstractOrganization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * api helper.
     *
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"Default", "api"})
     *
     * @return int
     */
    public function getOrganizationId()
    {
        return $this->getOrganization() ? $this->getOrganization()->getId() : null;
    }

    /**
     * @return string|null
     */
    public function getMachinename()
    {
        return $this->machinename;
    }

    /**
     * @param string
     */
    public function setMachinename($machineName)
    {
        $this->machinename = $machineName;
    }

    /**
     * If term is used for internal system processes.
     *
     * @return bool
     */
    public function isLocked($machineName = null)
    {
        return !empty($this->machinename);
    }

    /**
     * Check machine name match.
     *
     * @param $machineName
     *
     * @return bool
     */
    public function isMachinename($machineName)
    {
        return $this->machinename === $machineName;
    }

    /**
     * @return mixed
     */
    public function getVocabularyId()
    {
        return $this->vocabularyId;
    }

    /**
     * @param string $id
     */
    public function setVocabularyId($id)
    {
        $this->vocabularyId = $id;
    }

    /**
     * @return mixed
     *               This static method is used to set a specific order field
     *               when fetch terms
     */
    public static function orderBy()
    {
        return 'name';
    }
}

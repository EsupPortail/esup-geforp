<?php

namespace App\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Core\AbstractOrganization;

/**
 * Class AbstractTerm.
 *
 */
#[ORM\MappedSuperclass]
abstract class AbstractTerm implements VocabularyInterface, \Stringable
{
    use SortableTrait;

    /**
     * @var string
     */
    protected string $vocabularyId;

    /**
     * @var int
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    /**
     * @var string
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    #[Assert\NotBlank]
    private string $name;

    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'private', type: 'boolean')]
    private bool $private = false;

    private $label = null;

    /**
     * @var AbstractOrganization
     */
    #[ORM\ManyToOne(targetEntity: \App\Entity\Core\AbstractOrganization::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    protected AbstractOrganization $organization;

    /**
     * @var string
     */

    protected string $machinename;

    /**
     * @return mixed
     */
    abstract public function getVocabularyName(): mixed;

    /**
     * @param $label
     */
    public function setVocabularyLabel($label): void
    {
        $this->label = $label;
    }

    /**
     * @return string
     */
    public function getVocabularyLabel()
    {
        return $this->label ?: $this->getVocabularyName();
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getName();
    }

    /**
     * @param int $id
     */
    public function setId($id): void
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
    public function setName($name): void
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

    public function setPrivate(mixed $private): void
    {
        $this->private = $private;
    }

    /**
     * @param AbstractOrganization $organization
     */
    public function setOrganization($organization): void
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
    public function setMachinename($machineName): void
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
    public function setVocabularyId($id): void
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

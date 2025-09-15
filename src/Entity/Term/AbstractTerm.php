<?php

namespace App\Entity\Term;

use AllowDynamicProperties;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Proxy;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Core\AbstractOrganization;

/**
 * Class AbstractTerm.
 *
 */
#[AllowDynamicProperties] #[ORM\MappedSuperclass]
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
    private ?int $id = null;

    /**
     * @var string
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'private', type: 'boolean')]
    private bool $private = false;

    public ?string $labelVocabulary = null;
    /**
     * @var AbstractOrganization
     */
    #[ORM\ManyToOne(targetEntity: \App\Entity\Core\AbstractOrganization::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    protected ?AbstractOrganization $organization = null;

    /**
     * @var string
     */

    #[ORM\Column(name: 'machine_name' ,type: 'string', length: 255, nullable: true)]
    #[Groups(['Default', 'api'])]
    protected string $machinename;


    public function getVocabularyLabel(): string
    {
        return $this->labelVocabulary ?: $this->getVocabularyName();
    }

    public function setVocabularyLabel($labelVocabulary): void
    {
        $this->labelVocabulary = $labelVocabulary;
    }

    /**
     * @return mixed
     */


    abstract public function getVocabularyName(): mixed;

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
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function getPrivate(): bool
    {
        return $this->private;
    }

    public function setPrivate(mixed $private): void
    {
        $this->private = $private;
    }

    /**
     * @param AbstractOrganization|null $organization
     * @return AbstractOrganization|null
     */
    public function setOrganization(?AbstractOrganization $organization): ?AbstractOrganization
    {
       return $this->organization = $organization;
    }

    /**
     * @return AbstractOrganization
     */
    public function getOrganization(): ?AbstractOrganization
    {
        return $this->organization ?? null;
    }

    /**
     * api helper.
     *
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"Default", "api"})
     *
     * @return int
     */
    public function getOrganizationId(): ?int
    {
        return $this->getOrganization()?->getId();
    }

    /**
     * @return string|null
     */
    public function getMachinename(): ?string
    {
        return $this->machinename;
    }

    /**
     * @param string $machineName
     */
    public function setMachinename(string $machineName): void
    {
       $this->machinename = $machineName;
    }

    /**
     * If term is used for internal system processes.
     *
     * @return bool
     */
    public function isLocked($machineName = null): bool
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
    public function isMachinename($machineName): bool
    {
        return $this->machinename === $machineName;
    }

    /**
     * @return string
     */
    public function getVocabularyId(): string
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
    public static function orderBy(): mixed
    {
        return 'name';
    }
}

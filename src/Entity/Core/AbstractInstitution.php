<?php

namespace App\Entity\Core;


use AllowDynamicProperties;
use App\AccessRight\SerializedAccessRights;
use App\Entity\Back\Organization;
use App\Entity\Core\AbstractOrganization;
use App\Entity\PersonTrait\CoordinatesTrait;
use App\Entity\Term\Domain;
use App\Form\Type\BaseInstitutionType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Serializer\Attribute\Groups;
use App\Entity\Term\AbstractTerm;

/**
 * Institution.
 *
 */
#[AllowDynamicProperties] #[ORM\Table(name: 'institution')]
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\MappedSuperclass]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
abstract class AbstractInstitution implements SerializedAccessRights, \Stringable
{
    // Hook timestampable behavior : updates createdAt, updatedAt fields
    use TimestampableTrait;

    use CoordinatesTrait;

    #[Groups(['Default', 'institution'])]
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected int $id;

    #[Groups(['Default', 'api', 'institution', 'trainer'])]
    #[ORM\Column(name: 'name', type: \Doctrine\DBAL\Types\Types::STRING, length: 512)]
    #[Assert\NotBlank(message: "Vous devez renseigner un nom d'établissement.")]
    protected string $name;

    #[Groups(['Default', 'api', 'institution'])]
    #[ORM\Column(name: 'idp', type: \Doctrine\DBAL\Types\Types::STRING, length: 512, nullable: true)]
    protected ?string $idp = null;

    #[Groups(['Default', 'api'])]
    #[ORM\JoinTable(name: 'institution__institution_domain')]
    #[ORM\JoinColumn(name: 'institution_id', onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'domain_id', referencedColumnName: 'id', onDelete: 'cascade')]
    #[ORM\ManyToMany(targetEntity: \App\Entity\Term\Domain::class)]
    protected Collection $domains;


    #[Groups(['Default', 'api'])]
    #[ORM\JoinTable(name: 'institution__visuinstitutions')]
    #[ORM\JoinColumn(name: 'institution_id', onDelete: 'cascade')]
    #[ORM\InverseJoinColumn(name: 'visu_institution_id', referencedColumnName: 'id', onDelete: 'cascade')]
    #[ORM\ManyToMany(targetEntity: AbstractInstitution::class, cascade: ["persist", "remove"])]
    protected Collection $visuinstitutions;

    public function __construct()
    {
        $this->domains = new ArrayCollection();
        $this->visuinstitutions = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
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
    public function getIdp(): string
    {
        return $this->idp;
    }

    /**
     * @param string $idp
     */
    public function setIdp(string $idp): void
    {
        $this->idp = $idp;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getDomains(): ArrayCollection|Collection
    {
        return $this->domains;
    }

    public function setDomains(mixed $domains): void
    {
        $this->domains = $domains;
    }

    /**
     * @param Domain $domain
     *
     * @return bool
     */
    public function addDomain(Domain $domain): bool
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
    public function removeDomain(Domain $domain): bool
    {
        if ($this->domains->contains($domain)) {
            $this->domains->removeElement($domain);

            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getVisuinstitutions(): mixed
    {
        return $this->visuinstitutions;
    }

    public function setVisuinstitutions(mixed $visuinstitutions): void
    {
        $this->visuinstitutions = $visuinstitutions;
    }

    /**
     * @param AbstractInstitution $institution
     *
     * @return bool
     */
    public function addVisuinstitution(AbstractInstitution $institution): bool
    {
        if (!$this->visuinstitutions->contains($institution)) {
            $this->visuinstitutions->add($institution);

            return true;
        }

        return false;
    }

    /**
     * @param AbstractInstitution $institution
     *
     * @return bool
     */
    public function removeVisuinstitution(AbstractInstitution $institution): bool
    {
        if ($this->visuinstitutions->contains($institution)) {
            $this->visuinstitutions->removeElement($institution);

            return true;
        }

        return false;
    }

    function __toString(): string
    {
        return $this->name;
    }

    public static function getFormType(): string
    {
        return BaseInstitutionType::class;
    }

    public static function getType(): string
    {
        return 'institution';
    }
}

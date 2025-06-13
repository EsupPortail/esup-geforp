<?php

namespace App\Entity\PersonTrait;

use App\Entity\Term\Publictype;
use App\Entity\Core\AbstractInstitution;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Trait ProfessionalSituationTrait
 * @package App\Entity\PersonTrait
 */
trait ProfessionalSituationTrait
{
    #[Assert\NotNull(message: 'Vous devez renseigner un établissement ou une entreprise.', groups: ['api.profile'])]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Core\AbstractInstitution')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['trainee', 'trainer', 'inscription', 'session', 'api.profile'])]
    protected ?AbstractInstitution $institution = null;

    #[ORM\ManyToOne(targetEntity: Publictype::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(["trainee", "trainer", "inscription", "api.profile", "session"])]
    protected ?Publictype $publictype;

    #[ORM\Column(name: "service", type: "string", length: 255, nullable: true)]
    #[Groups(["trainee", "trainer", "inscription", "api.profile"])]
    protected ?string $service;

    #[ORM\Column(name: "is_paying", type: "boolean")]
    #[Groups(["trainee", "inscription", "api.profile", "api.token"])]
    protected bool $isPaying = false;

    #[ORM\Column(name: "status", type: "string", length: 512, nullable: true)]
    #[Groups(["trainee", "trainer", "inscription", "api.profile"])]
    protected ?string $status;


    /**
     * Copy professional situation information from another entity
     *
     * @param ProfessionalSituationTrait $entity
     * @param boolean $force
     */

    public function copyProfessionalSituation($entity, bool $force = true): void
    {
        $propertyAccessor = new PropertyAccessor();
        foreach (['institution', 'publictype', 'service', 'isPaying', 'status'] as $property) {
            $thisValue = $propertyAccessor->getValue($this, $property);
            if ($force || !$thisValue) {
                $propertyAccessor->setValue($this, $property, $propertyAccessor->getValue($entity, $property));
            }
        }
    }

    public function setInstitution(?AbstractInstitution $institution): void
    {
        $this->institution = $institution;
    }

    public function getInstitution(): ?AbstractInstitution
    {
        return $this->institution;
    }

    public function setPublictype(?Publictype $publictype): void
    {
        $this->publictype = $publictype;
    }

    public function getPublictype(): ?Publictype
    {
        return $this->publictype;
    }

    public function setService(?string $service): void
    {
        $this->service = $service;
    }

    public function getService(): ?string
    {
        return $this->service;
    }

    public function getIsPaying(): bool
    {
        return $this->isPaying;
    }

    public function setIsPaying(bool $isPaying): void
    {
        $this->isPaying = $isPaying;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }
}

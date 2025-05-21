<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Form\Type\AbstractParticipationType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Attribute\MaxDepth;
/**
 * Participation.
 *
 */
#[ORM\Table(name: 'participation')]
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[UniqueEntity(fields: ['session', 'trainer'], message: 'Cet intervenant est déjà associé à cet évènement.')]
abstract class AbstractParticipation
{
    /**
     * @Serializer\Groups({"Default", "api", "session", "participation"})
     */
    #[Groups(["Default", "api", "session", "participation"])]
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     * @var AbstractTrainer
     * @Serializer\Groups({"participation", "session", "api.training", "api"})
     */
    #[Groups(["api.training", "api", "session", "participation"])]
    #[ORM\ManyToOne(targetEntity: 'AbstractTrainer', inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'trainer_id')]
    #[Assert\NotNull(message: 'Vous devez sélectionner un intervenant')]
    #[MaxDepth(1)]
    protected AbstractTrainer $trainer;

    /**
     * @var AbstractSession
     * @Serializer\Groups({"participation", "session", "trainer", "api"})
     */
    #[Groups([ "api", "session", "participation", "trainer"])]
    #[ORM\ManyToOne(targetEntity: 'AbstractSession', inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'session_id')]
    #[Assert\NotNull]
    #[MaxDepth(1)]
    protected AbstractSession $session;

    /**
     * @Serializer\Groups({"participation"})
     */
    #[Groups(["participation"])]
    #[ORM\Column(name: 'is_organization', type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected ?bool $isOrganization = null;

    /**
     * @var AbstractOrganization
     * @Serializer\Groups({"Default", "api"})
     * @Serializer\Groups({"participation"})
     */
    #[Groups(["participation", "Default", "api"])]
    #[ORM\ManyToOne(targetEntity: 'AbstractOrganization')]
    #[ORM\JoinColumn]
    #[MaxDepth(1)]
    protected AbstractOrganization $organization;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return AbstractTrainer
     */
    public function getTrainer(): AbstractTrainer
    {
        return $this->trainer;
    }

    /**
     * @param AbstractTrainer
     */
    public function setTrainer($trainer): void
    {
        $this->trainer = $trainer;
    }

    /**
     * @return AbstractSession
     */
    public function getSession(): AbstractSession
    {
        return $this->session;
    }

    /**
     * @param AbstractSession $session
     */
    public function setSession(AbstractSession $session): void
    {
        $this->session = $session;
    }

    /**
     * @return bool|null
     */
    public function getIsOrganization(): ?bool
    {
        return $this->isOrganization;
    }

    public function setIsOrganization(mixed $isOrganization): void
    {
        $this->isOrganization = $isOrganization;
    }

    /**
     * @return AbstractOrganization
     */
    public function getOrganization(): AbstractOrganization
    {
        return $this->organization;
    }

    public function setOrganization(mixed $organization): void
    {
        $this->organization = $organization;
    }

    /**
     * @return string
     */
    public static function getFormType(): string
    {
        return AbstractParticipationType::class;
    }

    /**
     * @return string
     */
    public static function getType(): string
    {
        return 'participation';
    }
}

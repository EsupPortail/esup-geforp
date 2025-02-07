<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Form\Type\AbstractParticipationType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

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
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     * @var AbstractTrainer
     * @Serializer\Groups({"participation", "session", "api.training", "api"})
     */
    #[ORM\ManyToOne(targetEntity: 'AbstractTrainer', inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'trainer_id')]
    #[Assert\NotNull(message: 'Vous devez sélectionner un intervenant')]
    protected $trainer;

    /**
     * @var AbstractSession
     * @Serializer\Groups({"participation", "session", "trainer", "api"})
     */
    #[ORM\ManyToOne(targetEntity: 'AbstractSession', inversedBy: 'participations')]
    #[ORM\JoinColumn(name: 'session_id')]
    #[Assert\NotNull]
    protected $session;

    /**
     * @Serializer\Groups({"participation"})
     */
    #[ORM\Column(name: 'is_organization', type: \Doctrine\DBAL\Types\Types::BOOLEAN, nullable: true)]
    protected ?bool $isOrganization = null;

    /**
     * @var AbstractOrganization
     * @Serializer\Groups({"Default", "api"})
     * @Serializer\Groups({"participation"})
     */
    #[ORM\ManyToOne(targetEntity: 'AbstractOrganization')]
    #[ORM\JoinColumn]
    protected $organization;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return AbstractTrainer
     */
    public function getTrainer()
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
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param AbstractSession
     */
    public function setSession($session): void
    {
        $this->session = $session;
    }

    /**
     * @return mixed
     */
    public function getIsOrganization()
    {
        return $this->isOrganization;
    }

    public function setIsOrganization(mixed $isOrganization): void
    {
        $this->isOrganization = $isOrganization;
    }

    /**
     * @return mixed
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    public function setOrganization(mixed $organization): void
    {
        $this->organization = $organization;
    }

    /**
     * @return mixed
     */
    public static function getFormType()
    {
        return AbstractParticipationType::class;
    }

    /**
     * @return string
     */
    public static function getType()
    {
        return 'participation';
    }
}

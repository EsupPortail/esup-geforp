<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Material.
 *
 */
#[ORM\Table(name: 'material')]
#[ORM\Entity]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([])]
#[ORM\InheritanceType('JOINED')]
abstract class Material
{
    /**
     *
     * @Serializer\Groups({"Default", "api.attendance"})
     */
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     * @Serializer\Groups({"Default", "api.attendance"})
     */
    #[ORM\Column(name: 'name', type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    protected ?string $name = null;

    /**
     * @var AbstractTraining
     * @Serializer\Exclude
     */
    #[ORM\ManyToOne(targetEntity: 'AbstractTraining', inversedBy: 'materials')]
    #[ORM\JoinColumn]
    protected AbstractTraining $training;

    /**
     * @var AbstractSession
     * @Serializer\Exclude
     */
    #[ORM\ManyToOne(targetEntity: 'AbstractSession', inversedBy: 'materials')]
    #[ORM\JoinColumn(nullable: true)]
    protected AbstractSession $session;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return self
     */
    public function setName($name): \App\Entity\Core\Material
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param AbstractTraining $training
     */
    public function setTraining($training = null): void
    {
        $this->training = $training;
    }

    /**
     * @return AbstractTraining
     */
    public function getTraining(): AbstractTraining
    {
        return $this->training;
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
    public function setSession($session = null): void
    {
        $this->session = $session;
    }
    
}

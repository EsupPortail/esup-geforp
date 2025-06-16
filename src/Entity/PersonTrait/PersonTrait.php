<?php

namespace App\Entity\PersonTrait;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use App\EventListener\Serializer;
use Symfony\Component\Serializer\Attribute\Ignore;

/**
 * Trait PersonTrait.
 */
trait PersonTrait
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Term\Title")
     * @Groups({"Default", "api"})
     */
    #[Ignore]
    #[ORM\ManyToOne(targetEntity : 'App\Entity\Term\Title')]
    #[Groups(['Default', 'api'])]
    protected mixed $title;

    /**
     * @var string
     * @ORM\Column(name="first_name", type="string", length=50, nullable=true)
     * @Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'first_name', type: 'string', length: 50, nullable: true)]
    #[Groups(['Default', 'api'])]
    protected ?string $firstname = null;

    /**
     * @var string
     * @ORM\Column(name="last_name", type="string", length=50)
     * @Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'last_name', type: 'string', length: 50)]
    #[Groups(['Default', 'api'])]
    protected string $lastname;

    public function setTitle(mixed $title): void
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getTitle(): mixed
    {
        return $this->title;
    }

    /**
     * @param string $firstName
     */
    public function setFirstname(string $firstName): void
    {
        $this->firstname = $firstName;
    }

    /**
     * @return string
     */
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    /**
     * @param string $lastName
     */
    public function setLastname(string $lastName): void
    {
        $this->lastname = $lastName;
    }

    /**
     * @return string
     */
    public function getLastname(): string
    {
        return $this->lastname;
    }

    #[VirtualProperty]
    #[Groups(['Default', 'trainer', 'session', 'api.training', 'inscription'])]
    public function getFullname(): string
    {
        return $this->getFirstname() . ' ' . $this->getLastname();
    }


    public function getReverseFullName(): string
    {
        return $this->getLastname() . ' ' . $this->getFirstname();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getFullname();
    }
}

<?php

namespace App\Entity\PersonTrait;

use JMS\Serializer\Annotation as Serializer;

/**
 * Trait PersonTrait.
 */
trait PersonTrait
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Term\Title")
     * @Serializer\Groups({"Default", "api"})
     */
    protected $title;

    /**
     * @var string
     * @ORM\Column(name="first_name", type="string", length=50, nullable=true)
     * @Serializer\Groups({"Default", "api"})
     */
    protected $firstname;

    /**
     * @var string
     * @ORM\Column(name="last_name", type="string", length=50)
     * @Serializer\Groups({"Default", "api"})
     */
    protected $lastname;

    public function setTitle(mixed $title): void
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $firstName
     */
    public function setFirstname($firstName): void
    {
        $this->firstname = $firstName;
    }

    /**
     * @return string
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @param string $lastName
     */
    public function setLastname($lastName): void
    {
        $this->lastname = $lastName;
    }

    /**
     * @return string
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"Default", "api"})
     */
    public function getFullname(): string
    {
        return $this->getFirstname().' '.$this->getLastname();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"Default", "api"})
     */
    public function getReverseFullName(): string
    {
        return $this->getLastName().' '.$this->getFirstName();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getFullname();
    }
}

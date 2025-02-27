<?php

namespace App\Entity\PersonTrait;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\EventListener\Serializer;
use Doctrine\ORM\Mapping as ORM;
/**
 * Trait CoordinatesTrait.
 */
trait CoordinatesTrait
{
    /**
     * @var boolean addressType
     *
     * @ORM\Column(name="address_type", type="integer", nullable=true)
     * @Serializer\Groups({"Default", "trainee", "api.profile"})
     */
    #[ORM\Column(name: 'address_type', type: 'integer', nullable: true)]
    #[Groups(['Default', 'trainee', 'api.profile'])]
    protected bool $addresstype;

    /**
     * @var string address
     *
     * @ORM\Column(name="address", type="string", length=512, nullable=true)
     * @Serializer\Groups({"trainee", "institution", "inscription", "trainer", "api.profile"})
     */
    #[ORM\Column(name: 'address', type: 'string', length: 512, nullable: true)]
    #[Groups(['trainee', 'institution', 'inscription', 'trainer', 'api.profile'])]
    protected string $address;

    /**
     * @var string zip
     *
     * @ORM\Column(name="zip", type="string", length=32, nullable=true)
     * @Serializer\Groups({"trainee", "institution", "inscription", "trainer", "api.profile"})
     */
    #[ORM\Column(name: 'zip', type: 'string', length: 512, nullable: true)]
    #[Groups(['trainee', 'institution', 'inscription', 'trainer', 'api.profile'])]
    protected string $zip;

    /**
     * @var string city
     *
     * @ORM\Column(name="city", type="string", length=128, nullable=true)
     * @Serializer\Groups({"trainee", "institution", "inscription", "trainer", "api.profile"})
     */
    #[ORM\Column(name: 'city', type: 'string', length: 128, nullable: true)]
    #[Groups(['trainee', 'institution', 'inscription', 'trainer', 'api.profile'])]
    protected string $city;

    /**
     * @var string
     * @ORM\Column(name="email", type="string", length=128, nullable=true)
     * @Serializer\Groups({"trainee", "institution", "inscription", "trainer", "session", "api.profile", "api.inscription", "api.token"})
     */
    #[ORM\Column(name: 'email', type: 'string', length: 128, nullable: true)]
    #[Groups(['trainee', 'institution', 'inscription', 'trainer', 'session', 'api.profile', 'api.inscription' , 'api.token'])]
    #[Assert\Email(message: 'Vous devez renseigner un email valide.')]
    protected ?string $email = null;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_number", type="string", length=255, nullable=true)
     * @Serializer\Groups({"trainee", "inscription", "trainer", "api.profile"})
     */
    #[ORM\Column(name: 'phone_number', type: 'string', length: 255, nullable: true)]
    #[Groups(['trainee', 'inscription', 'trainer', 'api.profile'])]
    protected string $phonenumber;

    /**
     * @var string
     *
     * @ORM\Column(name="fax_number", type="string", length=255, nullable=true)
     * @Serializer\Groups({"organization", "trainee", "trainer", "api.profile"})
     */
    #[ORM\Column(name: 'fax_number', type: 'string', length: 255, nullable: true)]
    #[Groups(['organization', 'trainee', 'trainer', 'api.profile'])]
    protected string $faxnumber;

    /**
     * @var string
     * @ORM\Column(name="website", type="string", length=512, nullable=true)
     * @Serializer\Groups({"organization", "trainee", "trainer", "institution", "api.profile"})
     */
    #[ORM\Column(name: 'website', type: 'string', length: 512, nullable: true)]
    #[Groups(['organization', 'trainee', 'trainer', 'institution' , 'api.profile'])]
    protected string $website;

    /**
     * Copy coordinates from another entity.
     *
     * @param CoordinatesTrait $entity
     * @param bool $force  override existing data
     */
    public function copyCoordinates(CoordinatesTrait $entity, bool $force = true): void
    {
        $propertyAccessor = new PropertyAccessor();
        foreach (['addresstype', 'address', 'zip', 'city', 'email', 'phonenumber', 'faxnumber', 'website'] as $property) {
            $thisValue = $propertyAccessor->getValue($this, $property);
            if ($force || ! $thisValue) {
                $propertyAccessor->setValue($this, $property, $propertyAccessor->getValue($entity, $property));
            }
        }
    }


    /*
     * @param boolean $addressType
     */
    public function setAddresstype($addressType): void
    {
        $this->addresstype = $addressType;
    }

    /**
     * @return boolean
     */
    public function getAddresstype(): bool
    {
        return $this->addresstype;
    }

    /**
     * @param string $address
     */
    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $zip
     */
    public function setZip(string $zip): void
    {
        $this->zip = $zip;
    }

    /**
     * @return string
     */
    public function getZip(): string
    {
        return $this->zip;
    }

    /**
     * @param string $city
     */
    public function setCity(string $city): void
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    /**
     * @param string $phoneNumber
     */
    public function setPhonenumber(string $phoneNumber): void
    {
        $this->phonenumber = $phoneNumber;
    }

    /**
     * @return string
     */
    public function getPhonenumber(): string
    {
        return $this->phonenumber;
    }

    /**
     * @return string
     */
    public function getFaxnumber(): string
    {
        return $this->faxnumber;
    }

    /**
     * @param string $faxNumber
     */
    public function setFaxnumber(string $faxNumber): void
    {
        $this->faxnumber = $faxNumber;
    }

    /**
     * @param string $website
     */
    public function setWebsite(string $website): void
    {
        $this->website = $website;
    }

    /**
     * @return string
     */
    public function getWebsite(): string
    {
        return $this->website;
    }

    /**
     * Return the full address.
     *
     */
    public function getFullAddress(): string
    {
        $lines = [];
        if ($this->getAddress()) {
            $lines[] = $this->getAddress();
        }
        if ($this->getCity()) {
            $lines[] = ($this->getZip() ? $this->getZip() . ' ' : '') . $this->getCity();
        }

        return implode("\n", $lines);
    }
}

<?php

namespace App\Entity\Core;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\PersonTrait\CoordinatesTrait;
use App\Form\Type\AbstractOrganizationType;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Annotation\MaxDepth;


/**
 * Organization.
 *
 * IMPORTANT : serialization is handle by YML
 * to prevent rules from CoordinatesTrait being applied to private infos (trainee, trainer)
 *
 * @see Resources/config/serializer/Entity.Organization.yml
 * NO SERIALIZATION INFO IN ANNOTATIONS !!!
 *
 */
#[ORM\Table(name: 'organization')]
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
abstract class AbstractOrganization implements \Stringable
{
    /**
     *
     * @Serializer\Exclude
     */
    #[ORM\Column(name: 'address_type', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $addresstype = null;

    /**
     *
     * @Serializer\Groups({"api"})
     */
    #[ORM\Column(name: 'address', type: \Doctrine\DBAL\Types\Types::STRING, length: 512, nullable: true)]
    protected ?string $address = null;

    /**
     *
     * @Serializer\Groups({"api"})
     */
    #[ORM\Column(name: 'zip', type: \Doctrine\DBAL\Types\Types::STRING, length: 32, nullable: true)]
    protected ?string $zip = null;

    /**
     *
     * @Serializer\Groups({"api"})
     */
    #[ORM\Column(name: 'city', type: \Doctrine\DBAL\Types\Types::STRING, length: 128, nullable: true)]
    protected ?string $city = null;

    /**
     * @Serializer\Groups({"api"})
     */
    #[Assert\Email(message: 'Vous devez renseigner un email valide.')]
    #[ORM\Column(name: 'email', type: \Doctrine\DBAL\Types\Types::STRING, length: 128, nullable: true)]
    protected ?string $email = null;

    /**
     *
     * @Serializer\Groups({"api"})
     */
    #[ORM\Column(name: 'phone_number', type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    protected ?string $phonenumber = null;

    /**
     *
     * @Serializer\Groups({"api"})
     */
    #[ORM\Column(name: 'fax_number', type: \Doctrine\DBAL\Types\Types::STRING, length: 255, nullable: true)]
    protected ?string $faxnumber = null;

    /**
     * @Serializer\Groups({"api"})
     */
    #[ORM\Column(name: 'website', type: \Doctrine\DBAL\Types\Types::STRING, length: 512, nullable: true)]
    protected ?string $website = null;

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
    public function getAddresstype(): bool|int|null
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
    public function getAddress(): ?string
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
    public function getZip(): ?string
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
    public function getCity(): ?string
    {
        return $this->city;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(mixed $email): void
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
    public function getPhonenumber(): ?string
    {
        return $this->phonenumber;
    }

    /**
     * @return string
     */
    public function getFaxnumber(): ?string
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
    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * Return the full address.
     *
     * @return string
     */
    public function getFullAddress(): string
    {
        $lines = [];
        if ($this->address !== '' && $this->getAddress() !== '0') {
            $lines[] = $this->address;
        }

        if ($this->city !== '' && $this->getCity() !== '0') {
            $lines[] = ($this->zip !== '' && $this->getZip() !== '0' ? $this->zip . ' ' : '') . $this->city;
        }

        return implode("\n", $lines);
    }

    /**
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'name', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected string $name;

    /**
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'code', type: \Doctrine\DBAL\Types\Types::STRING, length: 32)]
    protected ?string $code = null;

    /**
     * @Serializer\Exclude
     */
    #[ORM\OneToMany(mappedBy: 'organization', targetEntity: 'User', cascade: ['persist', 'merge'])]
    private Collection $users;

    /**
     * @Serializer\Groups({"api"})
     */
    #[ORM\Column(name: 'trainee_registrable', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected ?bool $traineeRegistrable = true;

    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\ManyToOne(targetEntity: \App\Entity\Core\AbstractInstitution::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?\App\Entity\Core\AbstractInstitution $institution = null;


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
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
     * @param string $code
     */
    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function setUsers(\Doctrine\Common\Collections\ArrayCollection $users): void
    {
        $this->users = $users;
    }

    /**
     * @return ArrayCollection
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    /**
     * @return bool
     */
    public function getTraineeRegistrable(): ?bool
    {
        return $this->traineeRegistrable;
    }

    /**
     * @param bool $traineeRegistrable
     */
    public function setTraineeRegistrable(bool $traineeRegistrable): void
    {
        $this->traineeRegistrable = $traineeRegistrable;
    }

    public static function getFormType(): string
    {
        return AbstractOrganizationType::class;
    }

    /**
     * @return string
     */
    public static function getType(): string
    {
        return 'trainer';
    }

    /**
     * @return AbstractInstitution
     */
    public function getInstitution(): ?AbstractInstitution
    {
        return $this->institution;
    }

    /**
     * @param AbstractInstitution $institution
     */
    public function setInstitution(AbstractInstitution $institution): void
    {
        $this->institution = $institution;
    }
}

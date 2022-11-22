<?php

namespace App\Entity\Core;

use Doctrine\Common\Collections\ArrayCollection;
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
 * @ORM\Table(name="organization")
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 */
abstract class AbstractOrganization
{
    /**
     * @var boolean addressType
     *
     * @ORM\Column(name="address_type", type="integer", nullable=true)
     * @Serializer\Exclude
     */
    protected $addresstype;

    /**
     * @var string address
     *
     * @ORM\Column(name="address", type="string", length=512, nullable=true)
     * @Serializer\Groups({"api"})
     */
    protected $address;

    /**
     * @var string zip
     *
     * @ORM\Column(name="zip", type="string", length=32, nullable=true)
     * @Serializer\Groups({"api"})
     */
    protected $zip;

    /**
     * @var string city
     *
     * @ORM\Column(name="city", type="string", length=128, nullable=true)
     * @Serializer\Groups({"api"})
     */
    protected $city;

    /**
     * @var string
     * @Assert\Email(message="Vous devez renseigner un email valide.")
     * @ORM\Column(name="email", type="string", length=128, nullable=true)
     * @Serializer\Groups({"api"})
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_number", type="string", length=255, nullable=true)
     * @Serializer\Groups({"api"})
     */
    protected $phonenumber;

    /**
     * @var string
     *
     * @ORM\Column(name="fax_number", type="string", length=255, nullable=true)
     * @Serializer\Groups({"api"})
     */
    protected $faxnumber;

    /**
     * @var string
     * @ORM\Column(name="website", type="string", length=512, nullable=true)
     * @Serializer\Groups({"api"})
     */
    protected $website;

    /**
     * Copy coordinates from another entity.
     *
     * @param CoordinatesTrait $entity
     * @param bool             $force  override existing data
     */
    public function copyCoordinates($entity, $force = true)
    {
        $propertyAccessor = new PropertyAccessor();
        foreach (array('addresstype', 'address', 'zip', 'city', 'email', 'phonenumber', 'faxnumber', 'website') as $property) {
            $thisValue = $propertyAccessor->getValue($this, $property);
            if ($force || ! $thisValue) {
                $propertyAccessor->setValue($this, $property, $propertyAccessor->getValue($entity, $property));
            }
        }
    }


    /*
     * @param boolean $addressType
     */
    public function setAddresstype($addressType)
    {
        $this->addresstype = $addressType;
    }

    /**
     * @return boolean
     */
    public function getAddresstype()
    {
        return $this->addresstype;
    }

    /**
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $zip
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

    /**
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @param string $phoneNumber
     */
    public function setPhonenumber($phoneNumber)
    {
        $this->phonenumber = $phoneNumber;
    }

    /**
     * @return string
     */
    public function getPhonenumber()
    {
        return $this->phonenumber;
    }

    /**
     * @return string
     */
    public function getFaxnumber()
    {
        return $this->faxnumber;
    }

    /**
     * @param string $faxNumber
     */
    public function setFaxnumber($faxNumber)
    {
        $this->faxnumber = $faxNumber;
    }

    /**
     * @param string $website
     */
    public function setWebsite($website)
    {
        $this->website = $website;
    }

    /**
     * @return string
     */
    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * Return the full address.
     *
     * @return string
     */
    public function getFullAddress()
    {
        $lines = array();
        if ($this->getAddress()) {
            $lines[] = $this->getAddress();
        }
        if ($this->getCity()) {
            $lines[] = ($this->getZip() ? $this->getZip() . ' ' : '') . $this->getCity();
        }

        return implode("\n", $lines);
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"Default", "api"})
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Serializer\Groups({"Default", "api"})
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=32)
     * @Serializer\Groups({"Default", "api"})
     */
    protected $code;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="User", mappedBy="organization", cascade={"persist", "merge"})
     * @Serializer\Exclude
     */
    private $users;

    /**
     * @var bool
     * @ORM\Column(name="trainee_registrable", type="boolean")
     * @Serializer\Groups({"api"})
     */
    protected $traineeRegistrable = true;

    /**
     * @var AbstractInstitution
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\AbstractInstitution")
     * @ORM\JoinColumn(nullable=false)
     * @Serializer\Groups({"Default", "api"})
     */
    private $institution;


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param ArrayCollection $users
     */
    public function setUsers($users)
    {
        $this->users = $users;
    }

    /**
     * @return ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return bool
     */
    public function getTraineeRegistrable()
    {
        return $this->traineeRegistrable;
    }

    /**
     * @param bool $traineeRegistrable
     */
    public function setTraineeRegistrable($traineeRegistrable)
    {
        $this->traineeRegistrable = $traineeRegistrable;
    }

    public static function getFormType()
    {
        return AbstractOrganizationType::class;
    }

    /**
     * @return string
     */
    public static function getType()
    {
        return 'trainer';
    }

    /**
     * @return AbstractInstitution
     */
    public function getInstitution()
    {
        return $this->institution;
    }

    /**
     * @param AbstractInstitution $institution
     */
    public function setInstitution($institution)
    {
        $this->institution = $institution;
    }
}

<?php

namespace App\Entity\PersonTrait;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\Constraints as Assert;

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
    protected $addresstype;

    /**
     * @var string address
     *
     * @ORM\Column(name="address", type="string", length=512, nullable=true)
     * @Serializer\Groups({"trainee", "institution", "inscription", "trainer", "api.profile"})
     */
    protected $address;

    /**
     * @var string zip
     *
     * @ORM\Column(name="zip", type="string", length=32, nullable=true)
     * @Serializer\Groups({"trainee", "institution", "inscription", "trainer", "api.profile"})
     */
    protected $zip;

    /**
     * @var string city
     *
     * @ORM\Column(name="city", type="string", length=128, nullable=true)
     * @Serializer\Groups({"trainee", "institution", "inscription", "trainer", "api.profile"})
     */
    protected $city;

    /**
     * @var string
     * @Assert\Email(message="Vous devez renseigner un email valide.")
     * @ORM\Column(name="email", type="string", length=128, nullable=true)
     * @Serializer\Groups({"trainee", "inscription", "trainer", "session", "api.profile", "api.inscription", "api.token"})
     */
    protected $email;

    /**
     * @var string
     *
     * @ORM\Column(name="phone_number", type="string", length=255, nullable=true)
     * @Serializer\Groups({"trainee", "inscription", "trainer", "api.profile"})
     */
    protected $phonenumber;

    /**
     * @var string
     *
     * @ORM\Column(name="fax_number", type="string", length=255, nullable=true)
     * @Serializer\Groups({"organization", "trainee", "trainer", "api.profile"})
     */
    protected $faxnumber;

    /**
     * @var string
     * @ORM\Column(name="website", type="string", length=512, nullable=true)
     * @Serializer\Groups({"organization", "trainee", "trainer", "api.profile"})
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
}

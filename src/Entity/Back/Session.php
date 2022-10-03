<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Core\AbstractSession;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Form\Type\SessionType;
use App\Entity\DateSession;
use App\Entity\Alert;

/**
 *
 * @ORM\Table(name="session")
 * @ORM\Entity
 */
class Session extends AbstractSession
{
    /**
     * @var string
     * @ORM\Column(name="name", type="string", length=255)
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    protected $name;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    protected $price;

    /**
     * @ORM\Column(name="teaching_cost",type="float", nullable=true)
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    protected $teachingcost;

    /**
     * @ORM\Column(name="vacation_cost", type="float", nullable=true)
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    protected $vacationcost;

    /**
     * @ORM\Column(name="accommodation_cost", type="float", nullable=true)
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    protected $accommodationcost;

    /**
     * @ORM\Column(name="meal_cost", type="float", nullable=true)
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    protected $mealcost;

    /**
     * @ORM\Column(name="transport_cost", type="float", nullable=true)
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    protected $transportcost;

    /**
     * @ORM\Column(name="material_cost", type="float", nullable=true)
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    protected $materialcost;

    /**
     * @ORM\Column(type="float", nullable=true)
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    protected $taking;

    /**
     * @var ArrayCollection $dates
     * @ORM\OneToMany(targetEntity="App\Entity\DateSession", mappedBy="session", cascade={"persist", "remove"})
     * @ORM\OrderBy({"datebegin" = "ASC"})
     * @Serializer\Groups({"session", "api.session"})
     */
    protected $dates;

    /**
     * @var ArrayCollection $alerts
     * @ORM\OneToMany(targetEntity="App\Entity\Alert", mappedBy="session", cascade={"persist", "remove"})
     * @Serializer\Groups({"session", "api.session"})
     */
    protected $alerts;

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param mixed $price
     */
    public function setPrice($price)
    {
        $this->price = $price;
    }

    /**
     * @return mixed
     */
    public function getTeachingcost()
    {
        return $this->teachingcost;
    }

    /**
     * @param mixed $teachingCost
     */
    public function setTeachingcost($teachingCost)
    {
        $this->teachingcost = $teachingCost;
    }

    /**
     * @return mixed
     */
    public function getVacationcost()
    {
        return $this->vacationcost;
    }

    /**
     * @param mixed $vacationCost
     */
    public function setVacationCost($vacationCost)
    {
        $this->vacationcost = $vacationCost;
    }

    /**
     * @return mixed
     */
    public function getAccommodationcost()
    {
        return $this->accommodationcost;
    }

    /**
     * @param mixed $accommodationCost
     */
    public function setAccommodationcost($accommodationCost)
    {
        $this->accommodationcost = $accommodationCost;
    }

    /**
     * @return mixed
     */
    public function getMealcost()
    {
        return $this->mealcost;
    }

    /**
     * @param mixed $mealCost
     */
    public function setMealcost($mealCost)
    {
        $this->mealcost = $mealCost;
    }

    /**
     * @return mixed
     */
    public function getTransportcost()
    {
        return $this->transportcost;
    }

    /**
     * @param mixed $transportCost
     */
    public function setTransportcost($transportCost)
    {
        $this->transportcost = $transportCost;
    }

    /**
     * @return mixed
     */
    public function getMaterialcost()
    {
        return $this->materialcost;
    }

    /**
     * @param mixed $materialCost
     */
    public function setMaterialcost($materialCost)
    {
        $this->materialcost = $materialCost;
    }

    /**
     * @return mixed
     */
    public function getTaking()
    {
        return $this->taking;
    }

    /**
     * @param mixed $taking
     */
    public function setTaking($taking)
    {
        $this->taking = $taking;
    }

    /**
     * @return ArrayCollection
     */
    public function getDates()
    {
        return $this->dates;
    }

    /**
     * @param ArrayCollection $dates
     */
    public function setDates($dates)
    {
        $this->dates = $dates;
    }

    /**
     * @param DateSession $dates
     *
     * @return bool
     */
    public function addDates($dates)
    {
        if (!$this->dates->contains($dates)) {
            $this->dates->add($dates);

            return true;
        }

        return false;
    }

    /**
     * @param DateSession $dates
     *
     * @return bool
     */
    public function removeDate($dates)
    {
        if ($this->dates->contains($dates)) {
            $this->dates->removeElement($dates);

            return true;
        }

        return false;
    }

    /**
     * @return ArrayCollection
     */
    public function getAlerts()
    {
        return $this->alerts;
    }

    /**
     * @param ArrayCollection $alerts
     */
    public function setAlerts($alerts)
    {
        $this->alerts = $alerts;
    }

    /**
     * @param Alert $alerts
     *
     * @return bool
     */
    public function addAlert($alert)
    {
        if (!$this->alerts->contains($alert)) {
            $this->alerts->add($alert);

            return true;
        }

        return false;
    }

    /**
     * @param Alert $alert
     *
     * @return bool
     */
    public function removeAlert($alert)
    {
        if ($this->alerts->contains($alert)) {
            $this->alerts->removeElement($alert);

            return true;
        }

        return false;
    }

    function __construct()
    {
        $this->dates          = new ArrayCollection();
        $this->alerts          = new ArrayCollection();
    }

    public function __clone()
    {
        $this->setId(null);
        $this->dates         = new ArrayCollection();
        $this->alerts          = new ArrayCollection();
    }

    /**
     * @Serializer\VirtualProperty
     *
     * @param $front_root_url
     * @param $apiSerialization
     *
     * @return string
     * @return string
     */
    public function getFronturl($front_root_url = 'https://sygefor3.univ-amu.fr', $apiSerialization = false)
    {
        $url = $front_root_url . '/training/' . $this->getTraining()->getId() . '/';
        if (!$apiSerialization) {
            // URL permitting to register a private session
            if ($this->getRegistration() === self::REGISTRATION_PRIVATE && (!method_exists($this, 'getModule') || !$this->getModule())) {
                return $url . $this->getId() . '/' . md5($this->getId() + $this->getTraining()->getId());
            }
            // URL permitting to register a module sessions
            else if (method_exists($this, 'getModule') && $this->getModule()) {
                return $url . '/' . md5($this->training->getType() . $this->getTraining()->getId());
            }
        }

        // return public_old URL
        return $url . $this->getId();
    }

    function __toString()
    {
        $name = $this->getName() ? $this->getName() : $this->getTraining()->getName();

        return $name . " - " . $this->getDateRange();
    }

    public static function getFormType()
    {
        return SessionType::class;
    }
}

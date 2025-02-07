<?php

namespace App\Entity\Back;

use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Core\AbstractSession;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Form\Type\SessionType;
use App\Entity\Back\DateSession;
use App\Entity\Back\Alert;

#[ORM\Table(name: 'session')]
#[ORM\Entity]
class Session extends AbstractSession implements \Stringable
{
    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[ORM\Column(name: 'name', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $name = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $price = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[ORM\Column(name: 'teaching_cost', type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $teachingcost = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[ORM\Column(name: 'vacation_cost', type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $vacationcost = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[ORM\Column(name: 'accommodation_cost', type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $accommodationcost = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[ORM\Column(name: 'meal_cost', type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $mealcost = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[ORM\Column(name: 'transport_cost', type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $transportcost = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[ORM\Column(name: 'material_cost', type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $materialcost = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $taking = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<\App\Entity\Back\DateSession> $dates
     * @Serializer\Groups({"session", "api.session"})
     */
    #[ORM\OneToMany(targetEntity: \App\Entity\Back\DateSession::class, mappedBy: 'session', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['datebegin' => 'ASC'])]
    protected \Doctrine\Common\Collections\Collection $dates;

    /**
     * @var \Doctrine\Common\Collections\Collection<\App\Entity\Back\Alert> $alerts
     * @Serializer\Groups({"session", "api.session"})
     */
    #[ORM\OneToMany(targetEntity: \App\Entity\Back\Alert::class, mappedBy: 'session', cascade: ['persist', 'remove'])]
    protected \Doctrine\Common\Collections\Collection $alerts;

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
    public function setName($name): void
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

    public function setPrice(mixed $price): void
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

    public function setTeachingcost(mixed $teachingCost): void
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

    public function setVacationCost(mixed $vacationCost): void
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

    public function setAccommodationcost(mixed $accommodationCost): void
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

    public function setMealcost(mixed $mealCost): void
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

    public function setTransportcost(mixed $transportCost): void
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

    public function setMaterialcost(mixed $materialCost): void
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

    public function setTaking(mixed $taking): void
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
    public function setDates($dates): void
    {
        $this->dates = $dates;
    }

    /**
     * @param DateSession $dates
     *
     */
    public function addDates($dates): bool
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
     */
    public function removeDate($dates): bool
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
    public function setAlerts($alerts): void
    {
        $this->alerts = $alerts;
    }

    /**
     * @param Alert $alerts
     *
     */
    public function addAlert($alert): bool
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
     */
    public function removeAlert($alert): bool
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
     */
    public function getFronturl($front_root_url = 'https://sygefor3.univ-amu.fr', $apiSerialization = false): string
    {
        $url = $front_root_url . '/training/' . $this->getTraining()->getId() . '/';
        if ($apiSerialization) {
            // return public_old URL
            return $url . $this->getId();
        }
        if (method_exists($this, 'getModule') && $this->getModule()) {
            return $url . '/' . md5($this->training->getType() . $this->getTraining()->getId());
        }

        // return public_old URL
        return $url . $this->getId();
    }

    function __toString(): string
    {
        $name = $this->name ?: $this->getTraining()->getName();

        return $name . " - " . $this->getDateRange();
    }

    public static function getFormType(): string
    {
        return SessionType::class;
    }
}

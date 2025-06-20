<?php

namespace App\Entity\Back;

use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Core\AbstractSession;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Form\Type\SessionType;
use App\Entity\Back\DateSession;
use App\Entity\Back\Alert;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;

#[ORM\Table(name: 'session')]
#[ORM\Entity]
class Session extends AbstractSession
{
    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[Groups(['session', 'inscription', 'api'])]
    #[ORM\Column(name: 'name', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $name = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[Groups(['session', 'inscription', 'api'])]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $price = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[Groups(['session', 'inscription', 'api'])]
    #[ORM\Column(name: 'teaching_cost', type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $teachingcost = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[Groups(['session', 'inscription', 'api'])]
    #[ORM\Column(name: 'vacation_cost', type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $vacationcost = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[Groups(['session', 'inscription', 'api'])]
    #[ORM\Column(name: 'accommodation_cost', type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $accommodationcost = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[Groups(['session', 'inscription', 'api'])]
    #[ORM\Column(name: 'meal_cost', type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $mealcost = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[Groups(['session', 'inscription', 'api'])]
    #[ORM\Column(name: 'transport_cost', type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $transportcost = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[Groups(['session', 'inscription', 'api'])]
    #[ORM\Column(name: 'material_cost', type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $materialcost = null;

    /**
     * @Serializer\Groups({"session", "inscription", "api"})
     */
    #[Groups(['session', 'inscription', 'api'])]
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::FLOAT, nullable: true)]
    protected ?float $taking = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<\App\Entity\Back\DateSession> $dates
     * @Serializer\Groups({"session", "api.session"})
     */
    #[Groups(['api.session'])]
    #[ORM\OneToMany(mappedBy: 'session', targetEntity: \App\Entity\Back\DateSession::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['datebegin' => 'ASC'])]
    #[MaxDepth(1)]
    protected Collection $dates;

    /**
     * @var \Doctrine\Common\Collections\Collection<\App\Entity\Back\Alert> $alerts
     * @Serializer\Groups({"session", "api.session"})
     */
    #[Groups(['session', 'api.session'])]
    #[ORM\OneToMany(mappedBy: 'session', targetEntity: \App\Entity\Back\Alert::class, cascade: ['persist', 'remove'])]
    protected \Doctrine\Common\Collections\Collection $alerts;

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return float|null
     */
    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(mixed $price): void
    {
        $this->price = $price;
    }

    /**
     * @return float|null
     */
    public function getTeachingcost(): ?float
    {
        return $this->teachingcost;
    }

    public function setTeachingcost(?float $teachingCost): void
    {
        $this->teachingcost = $teachingCost;
    }

    /**
     * @return float|null
     */
    public function getVacationcost(): ?float
    {
        return $this->vacationcost;
    }

    public function setVacationCost(mixed $vacationCost): void
    {
        $this->vacationcost = $vacationCost;
    }

    /**
     * @return float|null
     */
    public function getAccommodationcost(): ?float
    {
        return $this->accommodationcost;
    }

    public function setAccommodationcost(mixed $accommodationCost): void
    {
        $this->accommodationcost = $accommodationCost;
    }

    /**
     * @return float|null
     */
    public function getMealcost(): ?float
    {
        return $this->mealcost;
    }

    public function setMealcost(mixed $mealCost): void
    {
        $this->mealcost = $mealCost;
    }

    /**
     * @return float|null
     */
    public function getTransportcost(): ?float
    {
        return $this->transportcost;
    }

    public function setTransportcost(mixed $transportCost): void
    {
        $this->transportcost = $transportCost;
    }

    /**
     * @return float|null
     */
    public function getMaterialcost(): ?float
    {
        return $this->materialcost;
    }

    public function setMaterialcost(mixed $materialCost): void
    {
        $this->materialcost = $materialCost;
    }

    /**
     * @return float|null
     */
    public function getTaking(): ?float
    {
        return $this->taking;
    }

    public function setTaking(mixed $taking): void
    {
        $this->taking = $taking;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getDates(): ArrayCollection|\Doctrine\Common\Collections\Collection
    {
        return $this->dates;
    }

    /**
     * @param ArrayCollection $dates
     */
    public function setDates(ArrayCollection $dates): void
    {
        $this->dates = $dates;
    }

    /**
     * @param DateSession $dates
     *
     */
    public function addDates(\App\Entity\Back\DateSession $dates): bool
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
    public function removeDate(\App\Entity\Back\DateSession $dates): bool
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
    public function getAlerts(): ArrayCollection|\Doctrine\Common\Collections\Collection
    {
        return $this->alerts;
    }

    /**
     * @param ArrayCollection $alerts
     */
    public function setAlerts(ArrayCollection $alerts): void
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
    public function removeAlert(\App\Entity\Back\Alert $alert): bool
    {
        if ($this->alerts->contains($alert)) {
            $this->alerts->removeElement($alert);

            return true;
        }

        return false;
    }

   public function __construct()
    {
        $this->dates          = new ArrayCollection();
        $this->alerts          = new ArrayCollection();
        parent::__Construct();
    }

    public function __clone()
    {
        $this->setId((int)null);
        $this->dates         = new ArrayCollection();
        $this->alerts          = new ArrayCollection();
    }

    /**
     * @Serializer\VirtualProperty
     *
     * @param string $front_root_url
     * @param false $apiSerialization
     *
     */
    public function getFronturl(string $front_root_url = 'https://sygefor3.univ-amu.fr', false $apiSerialization = false): string
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

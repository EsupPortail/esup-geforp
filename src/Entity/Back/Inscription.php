<?php

namespace App\Entity\Back;


use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Core\AbstractInscription;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Form\Type\InscriptionType;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;

#[ORM\Table(name: 'inscription')]
#[ORM\Entity]
class Inscription extends AbstractInscription implements \Stringable
{

    public bool $isPaying = false;
    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[Groups(['Default', 'api'])]
    #[ORM\Column(name: 'motivation', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $motivation = null;

    /**
     * @var Collection<\App\Entity\Back\EvaluationNotedCriterion>
     * @Serializer\Groups({"training", "inscription", "api.attendance", "session"})
     */
    #[Groups(['training', 'inscription', 'api.attendance', 'session'])]
    #[ORM\OneToMany(mappedBy: 'inscription', targetEntity: \App\Entity\Back\EvaluationNotedCriterion::class, cascade: ['persist', 'merge', 'remove'])]
    protected Collection $criteria;

    /**
     * @Serializer\Groups({"Default", "inscription", "api.attendance"})
     */
    #[Groups(['Default', 'inscription', 'api.attendance'])]
    #[ORM\Column(name: 'message', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $message = null;

    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[Groups(['Default', 'api'])]
    #[ORM\ManyToOne(targetEntity: \App\Entity\Term\Actiontype::class)]
    #[ORM\JoinColumn]
    protected ?\App\Entity\Term\Actiontype $actiontype = null;


    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[Groups(['Default', 'api'])]
    #[ORM\Column(name: 'refuse', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $refuse = null;

    /**
     * @var Collection<Presence> $presences
     * @Serializer\Groups({"training", "inscription", "api.attendance", "session"})
     */
    #[Groups(['training', 'inscription', 'api.attendance', 'session'])]
    #[ORM\OneToMany(mappedBy: 'inscription', targetEntity: Presence::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['datebegin' => 'ASC'])]
    protected Collection|ArrayCollection $presences;

    /**
     * @Serializer\Groups({"training", "inscription", "api.attendance", "session"})
     */
    #[Groups(['training', 'inscription', 'api.attendance', 'session'])]
    #[ORM\Column(name: 'dif', type: \Doctrine\DBAL\Types\Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $dif = null;


    /**
     *
     */
    function __construct()
    {
        $this->criteria = new ArrayCollection();
        $this->presences = new ArrayCollection();
        $this->isPaying = false;
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"api"})
     */
    #[Serializer\VirtualProperty]
    #[Groups(['api'])]
    public function getPrice(): float|int|null
    {
        return isset($this->isPaying) && $this->isPaying ? $this->getSession()->getPrice() : 0;
    }

    /**
     * @return string|null
     */
    public function getMotivation(): ?string
    {
        return $this->motivation;
    }

    public function setMotivation(mixed $motivation): void
    {
        $this->motivation = $motivation;
    }

    /**
     * @return string|null
     */
    public function getRefuse(): ?string
    {
        return $this->refuse;
    }

    /**
     * @param mixed refuse
     */

    public function checkAndLoadActionType($entityManager): void
    {
        $actionType = $this->getActiontype();
        if ($actionType !== null) {
            $entityManager->initialiszeObject($actionType);
            dump ($actionType);
        }
    }

    public function setRefuse($refuse): void
    {
        $this->refuse = $refuse;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getCriteria(): ArrayCollection|Collection
    {
        return $this->criteria;
    }

    public function setCriteria(mixed $criteria): void
    {
        $this->criteria = $criteria;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(mixed $message): void
    {
        $this->message = $message;
    }

    /**
     * @return \App\Entity\Term\Actiontype|null
     */

    public function getActiontype(): ?\App\Entity\Term\Actiontype
    {
        return $this->actiontype;
    }

    public function setActiontype(mixed $actiontype): void
    {
        $this->actiontype = $actiontype;
    }

    /**
     * @return ArrayCollection|Collection
     */
    public function getPresences(): ArrayCollection|Collection
    {
        return $this->presences;
    }

    /**
     * @param mixed presences
     */
    public function setPresences($presences): void
    {
        $this->presences = $presences;
    }

    /**
     * @return bool|null
     */
    public function getDif(): ?bool
    {
        return $this->dif;
    }

    public function setDif(mixed $dif): void
    {
        $this->dif = $dif;
    }

    /**
     * Add a noted criterion
     */
    public function addCriterion(EvaluationNotedCriterion $evaluationNotedCriterion): void
    {
        $this->criteria->add($evaluationNotedCriterion);
    }

    /**
     * Add a presence
     */
    public function addPresence(Presence $presence): void
    {
        $this->presences->add($presence);
    }


    static public function getFormType(): string
    {
        return InscriptionType::class;
    }

    function __toString(): string
    {
        return (string) $this->getId();
    }

    #[Groups(['inscription'])]
    public function getId(): ?int
    {
        return $this->id;
    }
}

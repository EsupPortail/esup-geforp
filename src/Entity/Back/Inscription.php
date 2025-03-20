<?php

namespace App\Entity\Back;


use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Core\AbstractInscription;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Form\Type\InscriptionType;
use JMS\Serializer\Annotation as Serializer;

#[ORM\Table(name: 'inscription')]
#[ORM\Entity]
class Inscription extends AbstractInscription implements \Stringable
{

    public $isPaying;
    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'motivation', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $motivation = null;

    /**
     * @var Collection<\App\Entity\Back\EvaluationNotedCriterion>
     * @Serializer\Groups({"training", "inscription", "api.attendance", "session"})
     */
    #[ORM\OneToMany(targetEntity: \App\Entity\Back\EvaluationNotedCriterion::class, mappedBy: 'inscription', cascade: ['persist', 'merge', 'remove'])]
    protected Collection $criteria;

    /**
     * @Serializer\Groups({"Default", "inscription", "api.attendance"})
     */
    #[ORM\Column(name: 'message', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $message = null;

    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\ManyToOne(targetEntity: \App\Entity\Term\Actiontype::class)]
    #[ORM\JoinColumn]
    protected ?\App\Entity\Term\Actiontype $actiontype = null;

    public function checkAndLoadActionType($entityManager): void
    {
        $actionType = $this->getActiontype();
        if ($actionType !== null) {
            $entityManager->initialiszeObject($actionType);
            dump ($actionType);
        }
    }

    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'refuse', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $refuse = null;

    /**
     * @var Collection<Presence> $presences
     * @Serializer\Groups({"training", "inscription", "api.attendance", "session"})
     */
    #[ORM\OneToMany(mappedBy: 'inscription', targetEntity: Presence::class, cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['datebegin' => 'ASC'])]
    protected Collection $presences;

    /**
     * @Serializer\Groups({"training", "inscription", "api.attendance", "session"})
     */
    #[ORM\Column(name: 'dif', type: \Doctrine\DBAL\Types\Types::BOOLEAN, options: ['default' => false])]
    protected ?bool $dif = null;


    /**
     *
     */
    function __construct()
    {
        $this->criteria = new ArrayCollection();
        $this->presences = new ArrayCollection();
    }

    /**
     * @Serializer\VirtualProperty
     * @Serializer\Groups({"api"})
     */
    public function getPrice()
    {
        return $this->isPaying ? $this->getSession()->getPrice() : 0;
    }

    /**
     * @return mixed
     */
    public function getMotivation()
    {
        return $this->motivation;
    }

    public function setMotivation(mixed $motivation): void
    {
        $this->motivation = $motivation;
    }

    /**
     * @return mixed
     */
    public function getRefuse()
    {
        return $this->refuse;
    }

    /**
     * @param mixed refuse
     */
    public function setRefuse($refuse): void
    {
        $this->refuse = $refuse;
    }

    /**
     * @return mixed
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    public function setCriteria(mixed $criteria): void
    {
        $this->criteria = $criteria;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage(mixed $message): void
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */

    public function getActiontype()
    {
        return $this->actiontype;
    }

    public function setActiontype(mixed $actiontype): void
    {
        $this->actiontype = $actiontype;
    }

    /**
     * @return mixed
     */
    public function getPresences()
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
     * @return mixed
     */
    public function getDif()
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
}

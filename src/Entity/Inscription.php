<?php

namespace App\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Core\AbstractInscription;
use Doctrine\ORM\Mapping as ORM;
use App\Form\InscriptionType;
use JMS\Serializer\Annotation as Serializer;

/**
 *
 * @ORM\Table(name="inscription")
 * @ORM\Entity
 */
class Inscription extends AbstractInscription
{

    /**
     * @var String
     * @ORM\Column(name="motivation", type="text", nullable=true)
     * @Serializer\Groups({"Default", "api"})
     */
    protected $motivation;

    /**
     * @var ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Entity\EvaluationNotedCriterion", mappedBy="inscription", cascade={"persist", "merge", "remove"})
     * @Serializer\Groups({"training", "inscription", "api.attendance", "session"})
     */
    protected $criteria;

    /**
     * @ORM\Column(name="message", type="text", nullable=true)
     * @Serializer\Groups({"Default", "inscription", "api.attendance"})
     */
    protected $message;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\Term\ActionType")
     * @ORM\JoinColumn(nullable=true)
     * @Serializer\Groups({"Default", "api"})
     */
    protected $actiontype;

    /**
     * @var String
     * @ORM\Column(name="refuse", type="text", nullable=true)
     * @Serializer\Groups({"Default", "api"})
     */
    protected $refuse;

    /**
     * @var ArrayCollection $presences
     * @ORM\OneToMany(targetEntity="App\Entity\Presence", mappedBy="inscription", cascade={"persist", "remove"})
     * @ORM\OrderBy({"dateBegin" = "ASC"})
     * @Serializer\Groups({"training", "inscription", "api.attendance", "session"})
     */
    protected $presences;

    /**
     * @var Boolean
     * @ORM\Column(name="dif", type="boolean", options={"default":false})
     * @Serializer\Groups({"training", "inscription", "api.attendance", "session"})
     */
    protected $dif;


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

    /**
     * @param mixed $motivation
     */
    public function setMotivation($motivation)
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
    public function setRefuse($refuse)
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

    /**
     * @param mixed $criteria
     */
    public function setCriteria($criteria)
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

    /**
     * @param mixed $message
     */
    public function setMessage($message)
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

    /**
     * @param mixed $actiontype
     */
    public function setActiontype($actiontype)
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
    public function setPresences($presences)
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

    /**
     * @param mixed $dif
     */
    public function setDif($dif)
    {
        $this->dif = $dif;
    }

    /**
     * Add a noted criterion
     * @param EvaluationNotedCriterion $criterion
     */
    public function addCriterion(EvaluationNotedCriterion $criterion)
    {
        $this->criteria->add($criterion);
    }

    /**
     * Add a presence
     * @param Presence $presence
     */
    public function addPresence(Presence $presence)
    {
        $this->presences->add($presence);
    }


    static public function getFormType()
    {
        return InscriptionType::class;
    }

    function __toString()
    {
        return strval($this->getId());
    }
}

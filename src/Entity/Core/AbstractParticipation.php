<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Form\Type\AbstractParticipationType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Participation.
 *
 * @ORM\Entity
 * @ORM\Table(name="participation")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @UniqueEntity(fields={"session", "trainer"}, message="Cet intervenant est déjà associé à cet évènement.")
 */
abstract class AbstractParticipation
{
    /**
     * @var int id
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"Default", "api", "session", "participation"})
     */
    protected $id;

    /**
     * @var AbstractTrainer
     * @ORM\ManyToOne(targetEntity="AbstractTrainer", inversedBy="participations")
     * @ORM\JoinColumn(name="trainer_id", referencedColumnName="id")
     * @Assert\NotNull(message="Vous devez sélectionner un intervenant")
     * @Serializer\Groups({"participation", "session", "api.training", "api"})
     */
    protected $trainer;

    /**
     * @var AbstractSession
     * @ORM\ManyToOne(targetEntity="AbstractSession", inversedBy="participations")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     * @Assert\NotNull()
     * @Serializer\Groups({"participation", "session", "trainer", "api"})
     */
    protected $session;

    /**
     * @var bool
     * @ORM\Column(name="is_organization", type="boolean", nullable=true)
     * @Serializer\Groups({"participation"})
     */
    protected $isOrganization;

    /**
     * @var AbstractOrganization
     * @ORM\ManyToOne(targetEntity="AbstractOrganization")
     * @ORM\JoinColumn(nullable=true)
     * @Serializer\Groups({"Default", "api"})
     * @Serializer\Groups({"participation"})
     */
    protected $organization;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return AbstractTrainer
     */
    public function getTrainer()
    {
        return $this->trainer;
    }

    /**
     * @param AbstractTrainer
     */
    public function setTrainer($trainer)
    {
        $this->trainer = $trainer;
    }

    /**
     * @return AbstractSession
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param AbstractSession
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * @return mixed
     */
    public function getIsOrganization()
    {
        return $this->isOrganization;
    }

    /**
     * @param mixed $isOrganization
     */
    public function setIsOrganization($isOrganization)
    {
        $this->isOrganization = $isOrganization;
    }

    /**
     * @return mixed
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param mixed $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }

    /**
     * @return mixed
     */
    public static function getFormType()
    {
        return AbstractParticipationType::class;
    }

    /**
     * @return string
     */
    public static function getType()
    {
        return 'participation';
    }
}

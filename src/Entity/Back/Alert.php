<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/14/16
 * Time: 5:33 PM
 */

namespace App\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 * @ORM\Table(name="alert")
 * @ORM\Entity
 */
class Alert
{
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
     * @ORM\ManyToOne(targetEntity="Trainee", inversedBy="alerts")
     * @ORM\JoinColumn(name="trainee_id", referencedColumnName="id")
     * @Assert\NotNull(message="Vous devez sélectionner un stagiaire.")
     * @Serializer\Groups({"session"})
     */
    protected $trainee;

    /**
     * @ORM\ManyToOne(targetEntity="Session", inversedBy="alerts")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     * @Assert\NotNull()
     * @Serializer\Groups({"trainee"})
     */
    protected $session;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at",type="datetime", nullable=true)
     * @Serializer\Groups({"inscription", "session", "trainee", "trainer", "api"})
     */
    protected $createdat;


    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param mixed $trainee
     */
    public function setTrainee($trainee)
    {
        $this->trainee = $trainee;
    }

    /**
     * @return AbstractTrainee
     */
    public function getTrainee()
    {
        return $this->trainee;
    }

    /**
     * @return ArrayCollection
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param mixed $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedat()
    {
        return $this->createdat;
    }

    /**
     * @param \DateTime $createdat
     */
    public function setCreatedat($createdAt)
    {
        $this->createdat = $createdAt;
    }

    /**
     * @return \App\Entity\Core\Organization
     */
    public function getOrganization()
    {
        return $this->getSession()->getTraining()->getOrganization();
    }

}
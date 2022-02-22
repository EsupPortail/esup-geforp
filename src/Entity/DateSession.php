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
 * @ORM\Table(name="date_session")
 * @ORM\Entity
 */
class DateSession
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
     * @ORM\Column(name="dateBegin", type="datetime")
     * @Assert\NotBlank(message="Vous devez préciser une date de début.")
     * @Serializer\Groups({"Default", "api"})
     */
    protected $dateBegin;

    /**
     * @ORM\Column(name="dateEnd", type="datetime", nullable=true)
     * @Serializer\Groups({"Default", "session", "api"})
     */
    protected $dateEnd;

    /**
     * @ORM\Column(name="scheduleMorn", type="string", length=512, nullable=true)
     * @var String
     */
    protected $scheduleMorn;

    /**
     * @ORM\Column(name="scheduleAfter", type="string", length=512, nullable=true)
     * @var String
     */
    protected $scheduleAfter;

    /**
     * @ORM\Column(name="hourNumberMorn", type="decimal", scale=2, nullable=true)
     * @var String
     */
    protected $hourNumberMorn;

    /**
     * @ORM\Column(name="hourNumberAfter", type="decimal", scale=2, nullable=true)
     * @var String
     */
    protected $hourNumberAfter;

    /**
     * @ORM\Column(name="place", type="string", length=512, nullable=true)
     * @var String
     */
    protected $place;

    /**
     * @var Session
     * @ORM\ManyToOne(targetEntity="Session", inversedBy="dates")
     * @ORM\JoinColumn(onDelete="CASCADE")
     * @Serializer\Groups({"session", "inscription", "trainee", "trainer", "api"})
     */
    protected $session;

    public function __construct()
    {
        $this->session = new ArrayCollection();
    }

    public function __clone()
    {
        $this->session = new ArrayCollection();
    }

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
     * @return mixed
     */
    public function getDateBegin()
    {
        return $this->dateBegin;
    }

    /**
     * @param mixed $dateBegin
     */
    public function setDateBegin($dateBegin)
    {
        $this->dateBegin = $dateBegin;
    }

    /**
     * @return mixed
     */
    public function getDateEnd()
    {
        return $this->dateEnd;
    }

    /**
     * @param mixed $dateEnd
     */
    public function setDateEnd($dateEnd)
    {
        $this->dateEnd = $dateEnd;
    }

    /**
     * @return mixed
     */
    public function getHourNumberMorn()
    {
        return $this->hourNumberMorn;
    }

    /**
     * @param mixed $hournumbermorn
     */
    public function setHourNumberMorn($hournumbermorn)
    {
        $this->hourNumberMorn = $hournumbermorn;
    }

    /**
     * @return mixed
     */
    public function getScheduleMorn()
    {
        return $this->scheduleMorn;
    }

    /**
     * @param mixed $scheduleMorn
     */
    public function setScheduleMorn($scheduleMorn)
    {
        $this->scheduleMorn = $scheduleMorn;
    }

    /**
     * @return mixed
     */
    public function getScheduleAfter()
    {
        return $this->scheduleAfter;
    }

    /**
     * @param mixed $scheduleAfter
     */
    public function setScheduleAfter($scheduleAfter)
    {
        $this->scheduleAfter = $scheduleAfter;
    }

    /**
     * @return mixed
     */
    public function getHourNumberAfter()
    {
        return $this->hourNumberAfter;
    }

    /**
     * @param mixed $hournumberafter
     */
    public function setHourNumberAfter($hournumberafter)
    {
        $this->hourNumberAfter = $hournumberafter;
    }

    /**
     * @return mixed
     */
    public function getPlace()
    {
        return $this->place;
    }

    /**
     * @param mixed $place
     */
    public function setPlace($place)
    {
        $this->place = $place;
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


}
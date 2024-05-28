<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/14/16
 * Time: 5:33 PM
 */

namespace App\Entity\Back;

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
    protected $datebegin;

    /**
     * @ORM\Column(name="dateEnd", type="datetime", nullable=true)
     * @Serializer\Groups({"Default", "session", "api"})
     */
    protected $dateend;

    /**
     * @ORM\Column(name="scheduleMorn", type="string", length=512, nullable=true)
     * @var String
     */
    protected $schedulemorn;

    /**
     * @ORM\Column(name="scheduleAfter", type="string", length=512, nullable=true)
     * @var String
     */
    protected $scheduleafter;

    /**
     * @ORM\Column(name="hourNumberMorn", type="decimal", scale=2, nullable=true)
     * @var String
     */
    protected $hournumbermorn;

    /**
     * @ORM\Column(name="hourNumberAfter", type="decimal", scale=2, nullable=true)
     * @var String
     */
    protected $hournumberafter;

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
    public function getDatebegin()
    {
        return $this->datebegin;
    }

    /**
     * @param mixed $dateBegin
     */
    public function setDatebegin($dateBegin)
    {
        $this->datebegin = $dateBegin;
    }

    /**
     * @return mixed
     */
    public function getDateend()
    {
        return $this->dateend;
    }

    /**
     * @param mixed $dateEnd
     */
    public function setDateend($dateEnd)
    {
        $this->dateend = $dateEnd;
    }

    /**
     * @return mixed
     */
    public function getHournumbermorn()
    {
        return $this->hournumbermorn;
    }

    /**
     * @param mixed $hournumbermorn
     */
    public function setHournumbermorn($hournumbermorn)
    {
        $this->hournumbermorn = $hournumbermorn;
    }

    /**
     * @return mixed
     */
    public function getSchedulemorn()
    {
        return $this->schedulemorn;
    }

    /**
     * @param mixed $scheduleMorn
     */
    public function setSchedulemorn($scheduleMorn)
    {
        $this->schedulemorn = $scheduleMorn;
    }

    /**
     * @return mixed
     */
    public function getScheduleafter()
    {
        return $this->scheduleafter;
    }

    /**
     * @param mixed $scheduleAfter
     */
    public function setScheduleafter($scheduleAfter)
    {
        $this->scheduleafter = $scheduleAfter;
    }

    /**
     * @return mixed
     */
    public function getHournumberafter()
    {
        return $this->hournumberafter;
    }

    /**
     * @param mixed $hournumberafter
     */
    public function setHournumberafter($hournumberafter)
    {
        $this->hournumberafter = $hournumberafter;
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
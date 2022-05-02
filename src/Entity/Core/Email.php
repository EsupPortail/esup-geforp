<?php

/**
 * Created by PhpStorm.
 * BaseUser: Erwan
 * Date: 24/08/2015
 * Time: 14:34.
 */

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Email.
 *
 * @ORM\Table(name="email")
 * @ORM\Entity
 */
class Email
{
    /**
     * @var int id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL", name="user_from_id")
     * @Serializer\Groups({"user"})
     */
    protected $userfrom;

    /**
     * @var string
     * @ORM\Column(name="emailFrom", type="string", length=128, nullable=true)
     */
    protected $emailfrom;

    /**
     * @var AbstractTrainee
     * @ORM\ManyToOne(targetEntity="AbstractTrainee")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    protected $trainee;

    /**
     * @var AbstractTrainer
     * @ORM\ManyToOne(targetEntity="AbstractTrainer")
     * @ORM\JoinColumn(nullable=true, onDelete="CASCADE")
     */
    protected $trainer;

    /**
     * @var AbstractSession
     * @ORM\ManyToOne(targetEntity="AbstractSession")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected $session;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="send_at", type="datetime", nullable=true)
     */
    protected $sendat;

    /**
     * @var string
     * @ORM\Column(name="subject", type="string", length=512, nullable=true)
     */
    protected $subject;

    /**
     * @var array
     * @ORM\Column(name="cc", type="array", nullable=true)
     */
    protected $cc;

    /**
     * @var string
     * @ORM\Column(name="body", type="text", nullable=true)
     */
    protected $body;

    public function __construct()
    {
        $this->cc = array();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUserFrom()
    {
        return $this->userFrom;
    }

    /**
     * @param User $userFrom
     */
    public function setUserFrom($userFrom)
    {
        $this->userFrom = $userFrom;
    }

    /**
     * @return string
     */
    public function getEmailFrom()
    {
        return $this->emailFrom;
    }

    /**
     * @param string $emailFrom
     */
    public function setEmailFrom($emailFrom)
    {
        $this->emailFrom = $emailFrom;
    }

    /**
     * @return AbstractTrainee
     */
    public function getTrainee()
    {
        return $this->trainee;
    }

    /**
     * @param AbstractTrainee $trainee
     */
    public function setTrainee($trainee)
    {
        $this->trainee = $trainee;
    }

    /**
     * @return AbstractTrainer
     */
    public function getTrainer()
    {
        return $this->trainer;
    }

    /**
     * @param AbstractTrainee $trainer
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
     * @param AbstractSession $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * @return string
     */
    public function getSendAt()
    {
        return $this->sendAt;
    }

    /**
     * @param string $sendAt
     */
    public function setSendAt($sendAt)
    {
        $this->sendAt = $sendAt;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return array
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * @param array $cc
     */
    public function setCc($cc)
    {
        $this->cc = $cc;
    }

    /**
     * @param string $cc
     * @param string $name
     *
     * @return bool
     */
    public function addCc($cc, $name)
    {
        if (!isset($this->cc[$cc])) {
            $this->cc[$cc] = $name;

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }
}

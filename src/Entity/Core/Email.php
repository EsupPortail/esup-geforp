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
 */
#[ORM\Table(name: 'email')]
#[ORM\Entity]
class Email
{
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     * @var User
     * @Serializer\Groups({"user"})
     */
    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(onDelete: 'SET NULL', name: 'user_from_id')]
    protected $userfrom;

    #[ORM\Column(name: 'emailFrom', type: \Doctrine\DBAL\Types\Types::STRING, length: 128, nullable: true)]
    protected ?string $emailfrom = null;

    /**
     * @var AbstractTrainee
     */
    #[ORM\ManyToOne(targetEntity: 'AbstractTrainee')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    protected $trainee;

    /**
     * @var AbstractTrainer
     */
    #[ORM\ManyToOne(targetEntity: 'AbstractTrainer')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    protected $trainer;

    /**
     * @var AbstractSession
     */
    #[ORM\ManyToOne(targetEntity: 'AbstractSession')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    protected $session;

    #[ORM\Column(name: 'send_at', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $sendat = null;

    #[ORM\Column(name: 'subject', type: \Doctrine\DBAL\Types\Types::STRING, length: 512, nullable: true)]
    protected ?string $subject = null;

    #[ORM\Column(name: 'cc', type: \Doctrine\DBAL\Types\Types::ARRAY, nullable: true)]
    protected array $cc = [];

    #[ORM\Column(name: 'body', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $body = null;

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
    public function getUserfrom()
    {
        return $this->userfrom;
    }

    /**
     * @param User $userFrom
     */
    public function setUserfrom($userFrom): void
    {
        $this->userfrom = $userFrom;
    }

    /**
     * @return string
     */
    public function getEmailfrom()
    {
        return $this->emailfrom;
    }

    /**
     * @param string $emailFrom
     */
    public function setEmailfrom($emailFrom): void
    {
        $this->emailfrom = $emailFrom;
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
    public function setTrainee($trainee): void
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
    public function setTrainer($trainer): void
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
    public function setSession($session): void
    {
        $this->session = $session;
    }

    /**
     * @return string
     */
    public function getSendat()
    {
        return $this->sendat;
    }

    /**
     * @param string $sendAt
     */
    public function setSendat($sendAt): void
    {
        $this->sendat = $sendAt;
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
    public function setSubject($subject): void
    {
        $this->subject = $subject;
    }

    public function getCc(): array
    {
        return $this->cc;
    }

    public function setCc(array $cc): void
    {
        $this->cc = $cc;
    }

    /**
     * @param string $cc
     * @param string $name
     *
     */
    public function addCc($cc, $name): bool
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
    public function setBody($body): void
    {
        $this->body = $body;
    }
}

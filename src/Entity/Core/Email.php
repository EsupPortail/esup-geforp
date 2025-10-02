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
use Symfony\Component\Serializer\Attribute\Groups;

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
    #[Groups(["user"])]
    #[ORM\ManyToOne(targetEntity: 'User')]
    #[ORM\JoinColumn(name: 'user_from_id', onDelete: 'SET NULL')]
    protected User $userfrom;

    #[ORM\Column(name: 'emailFrom', type: \Doctrine\DBAL\Types\Types::STRING, length: 128, nullable: true)]
    protected ?string $emailfrom = null;

    /**
     * @var AbstractTrainee
     */
    #[ORM\ManyToOne(targetEntity: 'AbstractTrainee')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    protected AbstractTrainee $trainee;

    /**
     * @var AbstractTrainer
     */
    #[ORM\ManyToOne(targetEntity: 'AbstractTrainer')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    protected AbstractTrainer $trainer;

    /**
     * @var AbstractSession
     */
    #[ORM\ManyToOne(targetEntity: 'AbstractSession')]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    protected AbstractSession $session;

    #[ORM\Column(name: 'send_at', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $sendat = null;

    #[ORM\Column(name: 'subject', type: \Doctrine\DBAL\Types\Types::STRING, length: 512, nullable: true)]
    protected ?string $subject = null;

    #[ORM\Column(name: 'cc', type: 'simple_array', nullable: true)]
    protected array $cc = [];

    #[ORM\Column(name: 'body', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $body = null;

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUserfrom(): User
    {
        return $this->userfrom;
    }

    /**
     * @param User $userFrom
     */
    public function setUserfrom(User $userFrom): void
    {
        $this->userfrom = $userFrom;
    }

    /**
     * @return string
     */
    public function getEmailfrom(): ?string
    {
        return $this->emailfrom;
    }

    /**
     * @param string $emailFrom
     */
    public function setEmailfrom(string $emailFrom): void
    {
        $this->emailfrom = $emailFrom;
    }

    /**
     * @return AbstractTrainee
     */
    public function getTrainee(): AbstractTrainee
    {
        return $this->trainee;
    }

    /**
     * @param AbstractTrainee $trainee
     */
    public function setTrainee(AbstractTrainee $trainee): void
    {
        $this->trainee = $trainee;
    }

    /**
     * @return AbstractTrainer
     */
    public function getTrainer(): AbstractTrainer
    {
        return $this->trainer;
    }

    /**
     * @param AbstractTrainee $trainer
     */
    public function setTrainer(AbstractTrainer $trainer): void
    {
        $this->trainer = $trainer;
    }

    /**
     * @return AbstractSession
     */
    public function getSession(): AbstractSession
    {
        return $this->session;
    }

    /**
     * @param AbstractSession $session
     */
    public function setSession(AbstractSession $session): void
    {
        $this->session = $session;
    }

    /**
     * @return string
     */
    public function getSendat(): \DateTimeInterface|string|null
    {
        return $this->sendat;
    }

    /**
     * @param string $sendAt
     */
    public function setSendat(\DateTimeInterface $sendAt): void
    {
        $this->sendat = $sendAt;
    }

    /**
     * @return string
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject): void
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
    public function addCc(string $cc, string $name): bool
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
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * @param string $body
     */
    public function setBody(string $body): void
    {
        $this->body = $body;
    }
}

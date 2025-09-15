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
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'date_session')]
#[ORM\Entity]
class DateSession
{
    /**
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[Groups(["Default", "api"])]
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[Groups(["Default", "api"])]
    #[ORM\Column(name: 'dateBegin', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'Vous devez préciser une date de début.')]
    protected ?\DateTimeInterface $datebegin = null;

    /**
     * @Serializer\Groups({"Default", "session", "api"})
     */
    #[Groups(["Default", "api", "session"])]
    #[ORM\Column(name: 'dateEnd', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $dateend = null;

    #[ORM\Column(name: 'scheduleMorn', type: \Doctrine\DBAL\Types\Types::STRING, length: 512, nullable: true)]
    protected ?string $schedulemorn = null;

    #[ORM\Column(name: 'scheduleAfter', type: \Doctrine\DBAL\Types\Types::STRING, length: 512, nullable: true)]
    protected ?string $scheduleafter = null;

    #[ORM\Column(name: 'hourNumberMorn', type: \Doctrine\DBAL\Types\Types::DECIMAL, scale: 2, nullable: true)]
    protected ?string $hournumbermorn = null;

    #[ORM\Column(name: 'hourNumberAfter', type: \Doctrine\DBAL\Types\Types::DECIMAL, scale: 2, nullable: true)]
    protected ?string $hournumberafter = null;

    #[ORM\Column(name: 'place', type: \Doctrine\DBAL\Types\Types::STRING, length: 512, nullable: true)]
    protected ?string $place = null;

    /**
     * @var Session
     * @Serializer\Groups({"session", "inscription", "trainee", "trainer", "api"})
     */
    #[Groups([ 'inscription', 'trainee', 'trainer', 'api', 'api.session'])]
    #[ORM\ManyToOne(targetEntity: Session::class, inversedBy: 'dates')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    protected Session $session;

    public function __construct()
    {
        $this->session = new Session();
    }

    public function __clone()
    {
        $this->session = new Session();
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDatebegin(): ?\DateTimeInterface
    {
        return $this->datebegin;
    }

    public function setDatebegin(mixed $dateBegin): void
    {
        $this->datebegin = $dateBegin;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateend(): ?\DateTimeInterface
    {
        return $this->dateend;
    }

    public function setDateend(mixed $dateEnd): void
    {
        $this->dateend = $dateEnd;
    }

    /**
     * @return string|null
     */
    public function getHournumbermorn(): ?string
    {
        return $this->hournumbermorn;
    }

    public function setHournumbermorn(mixed $hournumbermorn): void
    {
        $this->hournumbermorn = $hournumbermorn;
    }

    /**
     * @return string|null
     */
    public function getSchedulemorn(): ?string
    {
        return $this->schedulemorn;
    }

    public function setSchedulemorn(mixed $scheduleMorn): void
    {
        $this->schedulemorn = $scheduleMorn;
    }

    /**
     * @return string|null
     */
    public function getScheduleafter(): ?string
    {
        return $this->scheduleafter;
    }

    public function setScheduleafter(mixed $scheduleAfter): void
    {
        $this->scheduleafter = $scheduleAfter;
    }

    /**
     * @return string|null
     */
    public function getHournumberafter(): ?string
    {
        return $this->hournumberafter;
    }

    public function setHournumberafter(mixed $hournumberafter): void
    {
        $this->hournumberafter = $hournumberafter;
    }

    /**
     * @return string|null
     */
    public function getPlace(): ?string
    {
        return $this->place;
    }

    public function setPlace(mixed $place): void
    {
        $this->place = $place;
    }

    /**
     * @return ArrayCollection
     */
    public function getSession(): Session|ArrayCollection
    {
        return $this->session;
    }

    public function setSession(mixed $session): void
    {
        $this->session = $session;
    }


}
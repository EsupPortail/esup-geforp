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

#[ORM\Table(name: 'presence')]
#[ORM\Entity]
class Presence
{
    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     */
    public $session;
    /**
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'dateBegin', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    #[Assert\NotBlank(message: 'Vous devez préciser une date de début.')]
    protected ?\DateTimeInterface $datebegin = null;

    #[ORM\Column(name: 'morning', type: \Doctrine\DBAL\Types\Types::STRING, length: 512, nullable: true)]
    protected ?string $morning = null;

    #[ORM\Column(name: 'afternoon', type: \Doctrine\DBAL\Types\Types::STRING, length: 512, nullable: true)]
    protected ?string $afternoon = null;

    /**
     * @Serializer\Groups({"session", "trainee", "trainer", "api"})
     */
    #[ORM\ManyToOne(targetEntity: 'Inscription', inversedBy: 'presences')]
    #[ORM\JoinColumn(onDelete: 'CASCADE')]
    protected $inscription;

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
    public function setId($id): void
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

    public function setDatebegin(mixed $dateBegin): void
    {
        $this->datebegin = $dateBegin;
    }

    /**
     * @return mixed
     */
    public function getMorning()
    {
        return $this->morning;
    }

    public function setMorning(mixed $morning): void
    {
        $this->morning = $morning;
    }

    /**
     * @return mixed
     */
    public function getAfternoon()
    {
        return $this->afternoon;
    }

    public function setAfternoon(mixed $afternoon): void
    {
        $this->afternoon = $afternoon;
    }

    /**
     * @return ArrayCollection
     */
    public function getInscription()
    {
        return $this->inscription;
    }

    public function setInscription(mixed $inscription): void
    {
        $this->inscription = $inscription;
    }


}
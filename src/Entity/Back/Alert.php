<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/14/16
 * Time: 5:33 PM
 */

namespace App\Entity\Back;


use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTrainee;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'alert')]
#[ORM\Entity]
class Alert
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
     * @Serializer\Groups({"session"})
     */
    #[Groups(["session"])]
    #[ORM\ManyToOne(targetEntity: 'Trainee', inversedBy: 'alerts')]
    #[ORM\JoinColumn(name: 'trainee_id')]
    #[Assert\NotNull(message: 'Vous devez sélectionner un stagiaire.')]
    protected AbstractTrainee $trainee;

    /**
     * @Serializer\Groups({"trainee"})
     */
    #[Groups(["trainee"])]
    #[ORM\ManyToOne(targetEntity: 'Session', inversedBy: 'alerts')]
    #[ORM\JoinColumn(name: 'session_id')]
    #[Assert\NotNull]
    protected AbstractSession $session;

    /**
     * @Serializer\Groups({"inscription", "session", "trainee", "trainer", "api"})
     */
    #[Groups(["inscription", "session", "trainee", "trainer", "api"])]
    #[ORM\Column(name: 'created_at', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE, nullable: true)]
    protected ?\DateTimeInterface $createdat = null;


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

    public function setTrainee(mixed $trainee): void
    {
        $this->trainee = $trainee;
    }

    /**
     * @return AbstractTrainee
     */
    public function getTrainee(): AbstractTrainee
    {
        return $this->trainee;
    }

    /**
     * @return AbstractSession
     */
    public function getSession(): AbstractSession
    {
        return $this->session;
    }

    public function setSession(mixed $session): void
    {
        $this->session = $session;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedat(): \DateTime|\DateTimeInterface|null
    {
        return $this->createdat;
    }

    /**
     * @param \DateTime $createdat
     */
    public function setCreatedat($createdAt): void
    {
        $this->createdat = $createdAt;
    }

    /**
     * @return \App\Entity\Core\AbstractOrganization
     */
    public function getOrganization(): \App\Entity\Core\AbstractOrganization
    {
        return $this->session->getTraining()->getOrganization();
    }

}
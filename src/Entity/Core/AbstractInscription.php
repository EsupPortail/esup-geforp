<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\UniqueConstraint;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Core\Term\PresenceStatus;
use App\Entity\Core\Term\InscriptionStatus;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Form\Type\AbstractInscriptionType;
use App\Security\Authorization\AccessRight\SerializedAccessRights;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * Trainee.
 *
 * @ORM\Table(name="inscription", uniqueConstraints={@UniqueConstraint(name="traineesession_idx", columns={"trainee_id", "session_id"})})
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields={"trainee", "session"}, message="Cet utilisateur est déjà inscrit à cette session !")
 */
abstract class AbstractInscription implements SerializedAccessRights
{
    // Hook timestampable behavior : updates createdAt, updatedAt fields
    use TimestampableEntity;

    /**
     * @var int id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"Default", "api"})
     */
    protected $id;

    /**
     * @var AbstractTrainee
     * @ORM\ManyToOne(targetEntity="AbstractTrainee", inversedBy="inscriptions")
     * @ORM\JoinColumn(name="trainee_id", referencedColumnName="id")
     * @Assert\NotNull(message="Vous devez sélectionner un stagiaire.")
     * @Serializer\Groups({"inscription", "session"})
     */
    protected $trainee;

    /**
     * @var AbstractSession
     * @ORM\ManyToOne(targetEntity="AbstractSession", inversedBy="inscriptions")
     * @ORM\JoinColumn(name="session_id", referencedColumnName="id")
     * @Assert\NotNull()
     * @Serializer\Groups({"inscription", "trainee", "api"})
     */
    protected $session;

    /**
     * @var InscriptionStatus
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\Term\InscriptionStatus")
     * @Assert\NotNull(message="Vous devez spécifier un status d'inscription.")
     * @Serializer\Groups({"Default", "api"})
     */
    protected $inscriptionStatus;

    /**
     * @var PresenceStatus
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\Term\PresenceStatus")
     * @Serializer\Groups({"Default", "api"})
     */
    protected $presenceStatus;

    /**
     * @var bool
     */
    protected $sendInscriptionStatusMail = false;

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param InscriptionStatus
     */
    public function setInscriptionStatus($inscriptionStatus)
    {
        $this->inscriptionStatus = $inscriptionStatus;
    }

    /**
     * @return InscriptionStatus
     */
    public function getInscriptionStatus()
    {
        return $this->inscriptionStatus;
    }

    /**
     * @param PresenceStatus
     */
    public function setPresenceStatus($presenceStatus)
    {
        $this->presenceStatus = $presenceStatus;
    }

    /**
     * @return PresenceStatus
     */
    public function getPresenceStatus()
    {
        return $this->presenceStatus;
    }

    /**
     * @param AbstractSession
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * @return AbstractSession
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param AbstractTrainee
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
     * @return bool
     */
    public function isSendInscriptionStatusMail()
    {
        return $this->sendInscriptionStatusMail;
    }

    /**
     * @param bool $sendInscriptionStatusMail
     */
    public function setSendInscriptionStatusMail($sendInscriptionStatusMail)
    {
        $this->sendInscriptionStatusMail = $sendInscriptionStatusMail;
    }

    /**
     * Set the default inscription status (1).
     *
     * @ORM\PreUpdate
     * @ORM\PrePersist
     */
    public function setDefaultInscriptionStatus(LifecycleEventArgs $eventArgs)
    {
        if (!$this->getInscriptionStatus()) {
            $repository = $eventArgs->getEntityManager()->getRepository(InscriptionStatus::class);
            $status = $repository->findOneBy(array('machineName' => 'waiting'));
            $this->setInscriptionStatus($status);
        }
    }

    /**
     * @return AbstractOrganization
     */
    public function getOrganization()
    {
        return $this->getSession()->getTraining()->getOrganization();
    }

    /**
     * @return mixed
     */
    public static function getFormType()
    {
        return AbstractInscriptionType::class;
    }

    /**
     * @return string
     */
    public static function getType()
    {
        return 'inscription';
    }
}

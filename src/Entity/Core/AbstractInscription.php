<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping\UniqueConstraint;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Core\Term\Presencestatus;
use App\Entity\Core\Term\Inscriptionstatus;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Form\Type\AbstractInscriptionType;
use App\Security\AccessRight\SerializedAccessRights;
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
     * @var Inscriptionstatus
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\Term\Inscriptionstatus")
     * @ORM\JoinColumn(name="inscription_status_id", referencedColumnName="id")
     * @Assert\NotNull(message="Vous devez spécifier un status d'inscription.")
     * @Serializer\Groups({"Default", "api"})
     */
    protected $inscriptionstatus;

    /**
     * @var Presencestatus
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\Term\Presencestatus")
     * @ORM\JoinColumn(name="presence_status_id", referencedColumnName="id")
     * @Serializer\Groups({"Default", "api"})
     */
    protected $presencestatus;

    /**
     * @var bool
     */
    protected $sendinscriptionstatusmail = false;

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
     * @param Inscriptionstatus
     */
    public function setInscriptionstatus($inscriptionStatus)
    {
        $this->inscriptionstatus = $inscriptionStatus;
    }

    /**
     * @return Inscriptionstatus
     */
    public function getInscriptionstatus()
    {
        return $this->inscriptionstatus;
    }

    /**
     * @param Presencestatus
     */
    public function setPresencestatus($presenceStatus)
    {
        $this->presencestatus = $presenceStatus;
    }

    /**
     * @return Presencestatus
     */
    public function getPresencestatus()
    {
        return $this->presencestatus;
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
    public function isSendinscriptionstatusmail()
    {
        return $this->sendinscriptionstatusmail;
    }

    /**
     * @param bool $sendinscriptionstatusmail
     */
    public function setSendinscriptionstatusmail($sendinscriptionstatusmail)
    {
        $this->sendinscriptionstatusmail = $sendinscriptionstatusmail;
    }

    /**
     * Set the default inscription status (1).
     *
     * @ORM\PreUpdate
     * @ORM\PrePersist
     */
    public function setDefaultInscriptionstatus(LifecycleEventArgs $eventArgs)
    {
        if (!$this->getInscriptionstatus()) {
            $repository = $eventArgs->getEntityManager()->getRepository(Inscriptionstatus::class);
            $status = $repository->findOneBy(array('machineName' => 'waiting'));
            $this->setInscriptionstatus($status);
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

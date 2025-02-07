<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\Term\Publictype;

/**
 * Participants summary for a session.
 *
 *
 * traduction: session
 */
#[ORM\Table(name: 'participants_summary')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class ParticipantsSummary
{
    /**
     * @Serializer\Exclude
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: \App\Entity\Core\AbstractSession::class, inversedBy: 'participantsSummary')]
    protected ?\App\Entity\Core\AbstractSession $session = null;

    /**
     * @Serializer\Groups({"session"})
     */
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: \App\Entity\Term\Publictype::class)]
    protected ?\App\Entity\Term\Publictype $publictype = null;

    #[ORM\Column(name: 'count', type: \Doctrine\DBAL\Types\Types::INTEGER, nullable: true)]
    protected ?int $count = null;

    /**
     * @return mixed
     */
    public function getCount()
    {
        return $this->count;
    }

    public function setCount(mixed $count): void
    {
        $this->count = $count;
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
     * @return Publictype
     */
    public function getPublictype()
    {
        return $this->publictype;
    }

    /**
     * @param Publictype $Publictype
     */
    public function setPublictype($Publictype): void
    {
        $this->publictype = $Publictype;
    }
}

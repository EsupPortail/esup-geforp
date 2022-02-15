<?php

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\Term\PublicType;

/**
 * Participants summary for a session.
 *
 * @ORM\Table(name="participants_summary")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 *
 * traduction: session
 */
class ParticipantsSummary
{
    /**
     * @var AbstractSession
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\AbstractSession", inversedBy="participantsSummary")
     * @Serializer\Exclude
     */
    protected $session;

    /**
     * @var PublicType
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\Term\PublicType")
     * @Serializer\Groups({"session"})
     */
    protected $publicType;

    /**
     * @ORM\Column(name="count", type="integer", nullable=true)
     */
    protected $count;

    /**
     *
     */
    function __construct() {
        $this->count = null;
    }

    /**
     * @return mixed
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param mixed $count
     */
    public function setCount($count)
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
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * @return PublicType
     */
    public function getPublicType()
    {
        return $this->publicType;
    }

    /**
     * @param PublicType $publicType
     */
    public function setPublicType($publicType)
    {
        $this->publicType = $publicType;
    }
}

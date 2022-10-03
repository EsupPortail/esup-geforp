<?php

namespace App\Entity\Core\PersonTrait;

use App\Entity\Core\Term\Publictype;
use App\Entity\Core\AbstractInstitution;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\ExecutionContextInterface;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class ProfessionalSituationTraitInstitution
 * @package App\Entity\Core
 */
trait ProfessionalSituationTrait
{
    /**
     * @var AbstractInstitution Institution
     * @Assert\NotNull(message="Vous devez renseigner un établissement ou une entreprise.", groups={"api.profile"})
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\AbstractInstitution")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     * @Serializer\Groups({"trainee", "trainer", "inscription", "session", "api.profile"})
     */
    protected $institution;

    /**
     * @var Publictype
     * @ORM\ManyToOne(targetEntity="App\Entity\Core\Term\Publictype")
     * @ORM\JoinColumn(nullable=true)
     * @Serializer\Groups({"trainee", "trainer", "inscription", "api.profile","session"})
     */
    protected $publictype;

    /**
     * @var string service
     * @ORM\Column(name="service", type="string", length=255, nullable=true)
     * @Serializer\Groups({"trainee", "trainer", "inscription", "api.profile"})
     */
    protected $service;

    /**
     * @ORM\Column(name="is_paying", type="boolean")
     * @Serializer\Groups({"trainee", "inscription", "api.profile","api.token"})
     */
    protected $isPaying = false;

    /**
     * @var string status
     * @ORM\Column(name="status", type="string", length=512, nullable=true)
     * @Serializer\Groups({"trainee", "trainer", "inscription", "api.profile"})
     */
    protected $status;

    /**
     * Copy professional situation informations from another entity
     *
     * @param ProfessionalSituationTrait $entity
     * @param boolean $force
     */
    public function copyProfessionalSituation($entity, $force = true)
    {
        $propertyAccessor = new PropertyAccessor();
        foreach (array('institution', 'publictype', 'service', 'isPaying', 'status') as $property) {
            $thisValue = $propertyAccessor->getValue($this, $property);
            if ($force || ! $thisValue) {
                $propertyAccessor->setValue($this, $property, $propertyAccessor->getValue($entity, $property));
            }
        }
    }

    /**
     * @param AbstractInstitution $institution
     */
    public function setInstitution($institution)
    {
        $this->institution = $institution;
    }
    /**
     * @return AbstractInstitution
     */
    public function getInstitution()
    {
        return $this->institution;
    }

    /**
     * @param mixed $Publictype
     */
    public function setPublictype($Publictype)
    {
        $this->publictype = $Publictype;
    }

    /**
     * @return Publictype
     */
    public function getPublictype()
    {
        return $this->publictype;
    }

    /**
     * @param string $service
     */
    public function setService($service)
    {
        $this->service = $service;
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return boolean
     */
    public function getIsPaying()
    {
        return $this->isPaying;
    }

    /**
     * @param boolean $isPaying
     */
    public function setIsPaying($isPaying)
    {
        $this->isPaying = $isPaying;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
}

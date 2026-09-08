<?php

namespace App\Entity\Core;

use App\Entity\Back\Session;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\UniqueConstraint;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\AccessRight\SerializedAccessRights;
use App\Entity\Core\AbstractTraining;
use App\Entity\Core\AbstractCareer;

/**
 * CareerTraining.
 *
 */
#[ORM\Table(name: 'career_training')]
#[UniqueConstraint(name: 'careertraining_idx', columns: ['career_id', 'training_id'])]
#[ORM\Entity]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['career', 'training'], message: 'Ce stage est déjà rattaché à ce parcours.')]
abstract class AbstractCareerTraining implements SerializedAccessRights
{
    // Hook timestampable behavior : updates createdAt, updatedAt fields
    use TimestampableTrait;

    /**
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[Groups(['Default', 'api'])]
    protected ?int $id = null;

    /**
     * @var AbstractCareer
     * @Serializer\Groups({"career", "training", "careertraining"})
     */
    #[Groups(['career', 'training', 'careertraining', 'Default'])]
    #[ORM\ManyToOne(targetEntity: 'AbstractCareer', inversedBy: 'careertrainings')]
    #[ORM\JoinColumn(name: 'career_id')]
    #[Assert\NotNull(message: 'Vous devez sélectionner un parcours.')]
    protected AbstractCareer $career;

    /**
     * @var AbstractTraining
     * @Serializer\Groups({"career", "training", "careertraining"})
     */
    #[Groups(['career', 'training', 'careertraining', 'Default'])]
    #[ORM\ManyToOne(targetEntity: 'AbstractTraining', inversedBy: 'careertrainings')]
    #[ORM\JoinColumn(name: 'training_id', referencedColumnName: 'id')]
    #[Assert\NotNull]
    protected AbstractTraining $training;

    /**
     *
     * @Serializer\Groups({"career", "training", "careertraining"})
     */
    #[Groups(['career', 'training', 'careertraining', 'Default'])]
    #[ORM\Column(name: 'mandatory', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected ?bool $mandatory = false;


    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param AbstractCareer
     */
    public function setCareer($career): void
    {
        $this->career = $career;
    }

    /**
     * @return AbstractCareer
     */
    public function getCareer(): AbstractCareer
    {
        return $this->career;
    }

    /**
     * @param AbstractTraining
     */
    public function setTraining($training): void
    {
        $this->training = $training;
    }

    /**
     * @return AbstractTraining
     */
    public function getTraining(): AbstractTraining
    {
        return $this->training;
    }


    /**
     * @return bool
     */
    public function getMandatory(): bool
    {
        return $this->mandatory;
    }

    /**
     * @param bool mandatory
     */
    public function setMandatory($mandatory): void
    {
        $this->mandatory = $mandatory;
    }
}

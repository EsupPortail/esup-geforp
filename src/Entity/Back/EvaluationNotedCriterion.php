<?php

namespace App\Entity\Back;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Knp\DoctrineBehaviors\Model as ORMBehaviors;
use JMS\Serializer\Annotation as Serializer;

#[ORM\Table(name: 'evaluation_noted_criterion')]
#[ORM\Entity]
class EvaluationNotedCriterion
{
    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     * @Serializer\Exclude
     */
    #[ORM\ManyToOne(targetEntity: \App\Entity\Back\Inscription::class, inversedBy: 'criteria')]
    protected ?\App\Entity\Back\Inscription $inscription = null;

    /**
     * @Serializer\Groups({"Default", "api.attendance"})
     */
    #[ORM\ManyToOne(targetEntity: \App\Entity\Term\Evaluationcriterion::class)]
    #[ORM\JoinColumn(name: 'criterion_id', onDelete: 'CASCADE')]
    protected ?\App\Entity\Term\Evaluationcriterion $criterion = null;

    /**
     * @Serializer\Groups({"Default", "api.attendance"})
     */
    #[ORM\Column(name: 'note', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected ?int $note = null;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId(mixed $id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getInscription()
    {
        return $this->inscription;
    }

    public function setInscription(mixed $inscription): void
    {
        $this->inscription = $inscription;
    }

    /**
     * @return mixed
     */
    public function getCriterion()
    {
        return $this->criterion;
    }

    public function setCriterion(mixed $criterion): void
    {
        $this->criterion = $criterion;
    }

    /**
     * @return mixed
     */
    public function getNote()
    {
        return $this->note;
    }

    public function setNote(mixed $note): void
    {
        $this->note = $note;
    }
}

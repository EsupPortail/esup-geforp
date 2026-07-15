<?php

namespace App\Entity\Core;

use App\Form\Type\AbstractCareerType;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Doctrine\Common\Collections\ArrayCollection;
use App\Form\Type\AbstractTrainingType;
use App\Entity\Core\AbstractInstitution;
use App\Entity\Core\Material;
use App\Entity\Term\Supervisor;
use App\Entity\Term\Tag;
use App\Entity\Term\Trainingcategory;
use JMS\Serializer\Annotation\VirtualProperty;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\AccessRight\SerializedAccessRights;

#[ORM\Entity]
#[ORM\Table(name: 'career')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([])]
abstract class AbstractCareer implements SerializedAccessRights, \Stringable
{
    // Hook timestampable behavior : updates createdAt, updatedAt fields
    use TimestampableTrait;

    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[Groups(["Default", "api"])]
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private int $id;

    /**
     * @var AbstractOrganization
     * @Serializer\Groups({"Default", "career", "api"})
     */
    #[Groups(["Default", "api", "career"])]
    #[ORM\ManyToOne(targetEntity: 'AbstractOrganization')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    protected AbstractOrganization $organization;

    /**
     * @var \Doctrine\Common\Collections\Collection<\App\Entity\Core\AbstractTraining>
     * @Serializer\Groups({"career", "api.career"})
     */
    #[Groups(["api.career", "career"])]
    #[ORM\OneToMany(mappedBy: 'career', targetEntity: AbstractTraining::class, cascade: ['persist', 'remove'])]
    protected \Doctrine\Common\Collections\Collection $trainings;

    /**
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[Groups(["Default", "api", "training"])]
    #[ORM\Column(name: 'name', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Vous devez renseigner un intitulé.')]
    protected ?string $name = null;

    /**
     *
     * @Serializer\Groups({"career", "api"})
     */
    #[Groups(["career", "api"])]
    #[ORM\Column(name: 'description', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $description = null;

    /**
     *
     * @Serializer\Groups({"career", "api"})
     */
    #[Groups(["career", "api"])]
    #[ORM\Column(name: 'resources', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $resources = null;

    /**
     *
     * @Serializer\Groups({"career"})
     */
    #[Groups(["career"])]
    #[ORM\Column(name: 'comments', type: \Doctrine\DBAL\Types\Types::TEXT, nullable: true)]
    protected ?string $comments = null;


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->trainings = new ArrayCollection();
    }

    /**
     * cloning magic function.
     */
    public function __clone()
    {
        $this->setCreatedat(new \DateTime());

        //sessions are not copied.
        $this->trainings = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public static function getFormType(): mixed
    {
        return AbstractCareerType::class;
    }

    /**
     * @param $addMethod
     * @param ArrayCollection $arrayCollection
     */
    public function duplicateArrayCollection($addMethod, ArrayCollection $arrayCollection): void
    {
        foreach ($arrayCollection as $item) {
            if (method_exists($this, $addMethod)) {
                $this->$addMethod($item);
            }
        }
    }

    /**
     * @return int|null
     */
    public function getId(): int
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return AbstractOrganization
     */
    public function getOrganization(): AbstractOrganization
    {
        return $this->organization;
    }

    /**
     * @param AbstractOrganization $organization
     */
    public function setOrganization(AbstractOrganization $organization): void
    {
        $this->organization = $organization;
    }

    /**
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param ArrayCollection $trainings
     */
    public function setTrainings(ArrayCollection $trainings): void
    {
        $this->trainings = $trainings;
    }

    /**
     * @param AbstractTraining $training
     */
    public function addTraining(mixed $training): void
    {
        $this->trainings->add($training);
    }

    /**
     * @param AbstractTraining $training
     */
    public function removeTraining(mixed $training): void
    {
        $this->trainings->removeElement($training);
    }

    /**
     * @return ArrayCollection
     */
    public function getTrainings(): ArrayCollection|\Doctrine\Common\Collections\Collection
    {
        return $this->trainings;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getResources(): ?string
    {
        return $this->resources;
    }

    /**
     * @param string $resources
     */
    public function setResources(string $resources): void
    {
        $this->resources = $resources;
    }

    /**
     * @return string
     */
    public function getComments(): ?string
    {
        return $this->comments;
    }

    /**
     * @param string $comments
     */
    public function setComments(string $comments): void
    {
        $this->comments = $comments;
    }

}

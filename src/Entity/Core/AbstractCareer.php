<?php

namespace App\Entity\Core;

use App\Entity\Back\CareerTraining;
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
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[Groups(["Default", "api", "career"])]
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
     * @Serializer\Groups({"career"})
     * @var Collection<\App\Entity\Core\AbstractCareerTraining>
     */
    #[Groups('career')]
    #[ORM\OneToMany(mappedBy: 'career', targetEntity: CareerTraining::class, cascade: ['remove'], fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['createdat' => 'DESC'])]
    protected Collection $careertrainings;


    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->careertrainings = new ArrayCollection();
    }

    /**
     * cloning magic function.
     */
    public function __clone()
    {
        $this->setCreatedat(new \DateTime());

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

    /**
     * @return Collection
     */
    public function getCareerTrainings(): Collection
    {
        return $this->careertrainings;
    }

    public function setCareerTraining(mixed $careertrainings): void
    {
        $this->careertrainings = $careertrainings;
    }

    /**
     * @param AbstractCareerTraining $careertraining
     *
     * @return bool
     */
    public function addCareertraining(\App\Entity\Core\AbstractCareerTraining $careertraining): bool
    {
        if (!$this->careertrainings->contains($careertraining)) {
            $this->careertrainings->add($careertraining);

            return true;
        }

        return false;
    }

    /**
     * @param AbstractCareerTraining $careertraining
     *
     * @return bool
     */
    public function removeCareertraining(\App\Entity\Core\AbstractCareerTraining $careertraining): bool
    {
        if ($this->careertrainings->contains($careertraining)) {
            $this->careertrainings->removeElement($careertraining);

            return true;
        }

        return false;
    }
}

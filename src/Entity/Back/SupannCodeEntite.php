<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/8/16
 * Time: 12:55 PM
 */

namespace App\Entity\Back;


use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

#[ORM\Table(name: 'supanncodeentite')]
#[ORM\Entity]
class SupannCodeEntite
{
    /**
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    #[ORM\Column(name: 'supannCodeEntite', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $supannCodeEntite = null;

    /**
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    #[ORM\Column(name: 'Description', type: \Doctrine\DBAL\Types\Types::STRING)]
    protected ?string $description = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * Set supanncodeentite
     *
     *
     */
    public function setSupannCodeEntite(mixed $supannCodeEntite): void
    {
        $this->supannCodeEntite = $supannCodeEntite;
    }

    /**
     * Get birth date
     *
     */
    public function getSupannCodeEntite()
    {
        return $this->supannCodeEntite;
    }

    /**
     * Set description
     *
     *
     */
    public function setDescription(mixed $description): void
    {
        $this->description = $description;
    }

    /**
     * Get description
     *
     */
    public function getDescription()
    {
        return $this->description;
    }
}
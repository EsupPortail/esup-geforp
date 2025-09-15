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
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

#[ORM\Table(name: 'corps')]
#[ORM\Entity]
class Corps
{
    /**
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[Groups(["Default", "api"])]
    #[ORM\Column(name: 'id', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    protected ?int $id = null;

    /**
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    #[Groups(["Default", "api", "trainee"])]
    #[ORM\Column(name: 'corps', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $corps = null;

    /**
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    #[Groups(["Default", "api", "trainee"])]
    #[ORM\Column(name: 'libelle_court', type: \Doctrine\DBAL\Types\Types::STRING)]
    protected ?string $libelleCourt = null;

    /**
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    #[Groups(["Default", "api", "trainee"])]
    #[ORM\Column(name: 'libelle_long', type: \Doctrine\DBAL\Types\Types::STRING)]
    protected ?string $libelleLong = null;

    /**
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    #[Groups(["Default", "api", "trainee"])]
    #[ORM\Column(name: 'category', type: \Doctrine\DBAL\Types\Types::STRING)]
    protected ?string $category = null;



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
     * Set $corps
     *
     *
     */
    public function setCorps(mixed $corps): void
    {
        $this->$corps = $corps;
    }

    /**
     * Get corps
     *
     */
    public function getCorps()
    {
        return $this->corps;
    }

    /**
     * Set description
     *
     *
     */
    public function setLibelleCourt(mixed $libelleCourt): void
    {
        $this->libelleCourt = $libelleCourt;
    }

    /**
     * Get libelleCourt
     *
     */
    public function getLibelleCourt()
    {
        return $this->libelleCourt;
    }

    /**
     * Set description
     *
     *
     */
    public function setLibelleLong(mixed $libelleLong): void
    {
        $this->libelleLong = $libelleLong;
    }

    /**
     * Get libelleLong
     *
     */
    public function getLibelleLong()
    {
        return $this->libelleLong;
    }

    /**
     * Set category
     *
     *
     */
    public function setCategory(mixed $category): void
    {
        $this->category = $category;
    }

    /**
     * Get category
     *
     */
    public function getCategory()
    {
        return $this->category;
    }



}
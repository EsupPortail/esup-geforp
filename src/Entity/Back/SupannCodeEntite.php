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

/**
 *
 * @ORM\Table(name="supanncodeentite")
 * @ORM\Entity
 */
class SupannCodeEntite
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"Default", "api"})
     */
    protected $id;

    /**
     * @ORM\Column(name="supannCodeEntite", type="string", length=255)
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $supannCodeEntite;

    /**
     * @ORM\Column(name="Description", type="string")
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $description;

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
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Set supanncodeentite
     *
     * @param mixed $supannCodeEntite
     *
     */
    public function setSupannCodeEntite($supannCodeEntite)
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
     * @param mixed $description
     *
     */
    public function setDescription($description)
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
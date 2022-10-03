<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/8/16
 * Time: 12:55 PM
 */

namespace App\Entity;


use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 *
 * @ORM\Table(name="corps")
 * @ORM\Entity
 */
class Corps
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
     * @ORM\Column(name="corps", type="string", length=255)
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $corps;

    /**
     * @ORM\Column(name="libelle_court", type="string")
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $libelleCourt;

    /**
     * @ORM\Column(name="libelle_long", type="string")
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $libelleLong;

    /**
     * @ORM\Column(name="category", type="string")
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $category;



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
     * Set $corps
     *
     * @param mixed $corps
     *
     */
    public function setCorps($corps)
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
     * @param mixed $libelleCourt
     *
     */
    public function setLibelleCourt($libelleCourt)
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
     * @param mixed $libelleLong
     *
     */
    public function setLibelleLong($libelleLong)
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
     * @param mixed $category
     *
     */
    public function setCategory($category)
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
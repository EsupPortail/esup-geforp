<?php

namespace App\Entity\Term;

use JMS\Serializer\Annotation as Serializer;

trait SortableTrait
{
    /**
     * @ORM\Column(name="position", type="integer")
     * @Serializer\Exclude
     */
    private $position = 0;

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }
}
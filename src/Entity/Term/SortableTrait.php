<?php

namespace App\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Attribute\Groups;

trait SortableTrait
{
    /**
     * @ORM\Column(name="position", type="integer")
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'position', type: 'integer')]
    #[Groups(['Default', 'api'])]
    private int $position = 0;

    public function setPosition($position): void
    {
        $this->position = $position;
    }

    public function getPosition(): int
    {
        return $this->position;
    }
}
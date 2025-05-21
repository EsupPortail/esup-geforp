<?php

namespace App\Entity\Core;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Timestampable Trait
 *
 */
trait TimestampableTrait
{
    /**
     * @var \DateTimeInterface
     *
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'created_at', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    #[Groups(['institution', 'trainer', 'inscription'])]
    protected \DateTimeInterface $createdat;

    /**
     * @var \DateTimeInterface
     *
     */
    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'updated_at', type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    #[Groups(['institution', 'trainer', 'inscription'])]
    protected \DateTimeInterface $updatedat;

    /**
     * Sets createdAt.
     *
     * @return $this
     */
    public function setCreatedat(\DateTime $createdAt): static
    {
        $this->createdat = $createdAt;

        return $this;
    }

    /**
     * Returns createdAt.
     *
     * @return \DateTimeInterface
     */
    public function getCreatedat(): \DateTimeInterface
    {
        return $this->createdat;
    }

    /**
     * Sets updatedAt.
     *
     * @return $this
     */
    public function setUpdatedat(\DateTime $updatedAt): static
    {
        $this->updatedat = $updatedAt;

        return $this;
    }

    /**
     * Returns updatedAt.
     *
     * @return \DateTimeInterface
     */
    public function getUpdatedat(): \DateTimeInterface
    {
        return $this->updatedat;
    }
}

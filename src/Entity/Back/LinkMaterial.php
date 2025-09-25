<?php

namespace App\Entity\Back;


use App\Entity\Core\Material;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * LinkMaterial.
 *
 */
#[ORM\Table(name: 'link_material')]
#[ORM\Entity]
class LinkMaterial extends Material
{
    /**
     * @Serializer\Groups({"Default", "api.attendance"})
     */
    #[Groups('Default', 'api.attendance')]
    #[ORM\Column(name: 'url', type: \Doctrine\DBAL\Types\Types::STRING)]
    #[Assert\Url(message: 'Url non valide !')]
    private ?string $url = null;

    /**
     * @return mixed
     */
    public function getUrl(): mixed
    {
        return $this->url;
    }

    public function setUrl(mixed $link): void
    {
        $this->url = $link;
    }

    static public function getType(): string
    {
        return 'link';
    }
}

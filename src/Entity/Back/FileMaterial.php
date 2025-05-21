<?php

namespace App\Entity\Back;


use App\Entity\Core\Material;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\Core\UploadableTrait;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * FileMaterial.
 *
 */
#[ORM\Table(name: 'file_material')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class FileMaterial extends Material
{
    use UploadableTrait;

    /**
     * Get name.
     *
     * @Serializer\VirtualProperty
     * @Serializer\SerializedName("name")
     * @Serializer\Groups({"Default", "api.attendance"})
     *
     * @return string
     */
    #[Groups([ 'Default','api.attendance'])]
    public function getName(): string
    {
        return $this->filename;
    }

    static public function getType(): string
    {
        return 'file';
    }

    protected function getTemplatesRootDir(): string
    {
        // le chemin absolu du répertoire où les documents uploadés doivent être sauvegardés
        return __DIR__ . '/../../../var/Material';
    }
}

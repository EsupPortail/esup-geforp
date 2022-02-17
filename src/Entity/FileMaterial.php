<?php

namespace App\Entity;


use App\Entity\Core\AbstractMaterial;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\Core\UploadableTrait;

/**
 * FileMaterial.
 *
 * @ORM\Entity
 * @ORM\Table(name="file_material")
 * @ORM\HasLifecycleCallbacks
 */
class FileMaterial extends AbstractMaterial
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
    public function getName()
    {
        return $this->fileName;
    }

    /**
     * @return string
     */
    static public function getType()
    {
        return 'file';
    }

    /**
     * @return string
     */
    protected function getTemplatesRootDir()
    {
        // le chemin absolu du répertoire où les documents uploadés doivent être sauvegardés
        return __DIR__ . '/../../../../../app/Resources/Material';
    }
}

<?php

namespace App\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Core\UploadableTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContext;
use App\Form\Type\ImageFileVocabularyType;

/**
 * Class ImageFile.
 *
 * @ORM\Table(name="image_file")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ImageFile extends AbstractTerm implements VocabularyInterface
{
    use UploadableTrait;


    /**
     * @return mixed
     */
    public function getVocabularyName()
    {
        return 'Fichiers images';
    }

    /**
     * returns the form type name for template edition.
     *
     * @return string
     */
    public static function getFormType()
    {
        return ImageFileVocabularyType::class;
    }

    public static function getVocabularyStatus()
    {
        return VocabularyInterface::VOCABULARY_LOCAL;
    }

    /**
     * @Assert\Callback()
     */
    public function validateFile(ExecutionContext $context)
    {
        if (empty($this->file)) {
            $context->addViolationAt('file', 'Vous devez sélectionner un fichier');
        }
    }

    /**
     * @return string
     */
    protected function getTemplatesRootDir()
    {
        // le chemin absolu du répertoire où les documents uploadés doivent être sauvegardés
        return __DIR__.'/../../../public/img/vocabulary';
    }

    /**
     * @return mixed
     */
    public static function orderBy()
    {
        return 'name';
    }
}
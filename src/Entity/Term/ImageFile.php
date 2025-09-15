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
 */
#[ORM\Table(name: 'image_file')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class ImageFile extends AbstractTerm implements VocabularyInterface
{
    use UploadableTrait;


    /**
     * @return mixed
     */
    public function getVocabularyName(): mixed
    {
        return 'Fichiers images';
    }

    /**
     * returns the form type name for template edition.
     *
     */
    public static function getFormType(): string
    {
        return ImageFileVocabularyType::class;
    }

    public static function getVocabularyStatus(): int
    {
        return VocabularyInterface::VOCABULARY_LOCAL;
    }

    #[Assert\Callback]
    public function validateFile(ExecutionContext $executionContext): void
    {
        if (!$this->file instanceof \Symfony\Component\HttpFoundation\File\File) {
            $executionContext->addViolationAt('file', 'Vous devez sélectionner un fichier');
        }
    }

    protected function getTemplatesRootDir(): string
    {
        // le chemin absolu du répertoire où les documents uploadés doivent être sauvegardés
        return __DIR__.'/../../../public/img/vocabulary';
    }

    /**
     * @return mixed
     */
    public static function orderBy(): mixed
    {
        return 'name';
    }
}
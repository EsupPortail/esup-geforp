<?php

namespace App\Entity\Term;

use App\Form\Type\PublipostTemplateVocabularyType;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Core\UploadableTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContext;

/**
 * Class PublipostTemplates.
 *
 */
#[ORM\Table(name: 'publipost_template')]
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Publiposttemplate extends AbstractTerm implements VocabularyInterface
{
    use UploadableTrait;

    #[ORM\Column(name: 'entity', type: 'text', nullable: false)]
    #[Assert\NotNull]
    protected ?string $entity = null;

    /**
     * @param string $entity
     */
    public function setEntity(string $entity): void
    {
        $this->entity = $entity;
    }

    /**
     * @return string
     */
    public function getEntity(): ?string
    {
        return $this->entity;
    }

    /**
     * @return mixed
     */
    public function getVocabularyName(): string
    {
        return 'Modèles de publipostage';
    }

    /**
     * returns the form type name for template edition.
     *
     */
    public static function getFormType(): string
    {
        return PublipostTemplateVocabularyType::class;
    }

    public static function getVocabularyStatus(): int
    {
        return VocabularyInterface::VOCABULARY_LOCAL;
    }

    #[Assert\Callback]
    public function validateFile(ExecutionContext $executionContext): void
    {
        if (!$this->file instanceof \Symfony\Component\HttpFoundation\File\File) {
            $executionContext->addViolation('file', (array)'Vous devez sélectionner un fichier');
        }
    }

    protected function getTemplatesRootDir(): string
    {
        // le chemin absolu du répertoire où les documents uploadés doivent être sauvegardés
        return __DIR__.'/var/Publipost';
    }

    /**
     * @return mixed
     */
    public static function orderBy(): string
    {
        return 'name';
    }
}
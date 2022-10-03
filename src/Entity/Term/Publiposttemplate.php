<?php

namespace App\Entity\Core\Term;

use App\Form\Type\PublipostTemplateVocabularyType;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Core\UploadableTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContext;

/**
 * Class PublipostTemplates.
 *
 * @ORM\Table(name="publipost_template")
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Publiposttemplate extends AbstractTerm implements VocabularyInterface
{
    use UploadableTrait;

    /**
     * @ORM\Column(name="entity", type="text", nullable=false)
     * @Assert\NotNull()
     *
     * @var string
     */
    protected $entity;

    /**
     * @param string $entity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
    }

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return mixed
     */
    public function getVocabularyName()
    {
        return 'Modèles de publipostage';
    }

    /**
     * returns the form type name for template edition.
     *
     * @return string
     */
    public static function getFormType()
    {
        return PublipostTemplateVocabularyType::class;
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
        return __DIR__.'/../../../../var/Publipost';
    }

    /**
     * @return mixed
     */
    public static function orderBy()
    {
        return 'name';
    }
}
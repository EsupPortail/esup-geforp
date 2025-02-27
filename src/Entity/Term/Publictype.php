<?php
namespace App\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Term\AbstractTerm;
use App\Entity\Term\VocabularyInterface;
use JMS\Serializer\Annotation as Serializer;
use App\Form\Type\PublictypeVocabularyType;

/**
 * Type de personnel.
 */
#[ORM\Table(name: 'publictype')]
#[ORM\Entity]
class Publictype extends AbstractTerm implements VocabularyInterface
{
    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'machine_name', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected string $machinename;

    /**
     * @param string $machinename
     */
    public function setMachinename($machinename): void
    {
        $this->machinename = $machinename;
    }

    /**
     * @return string
     */
    public function getMachinename()
    {
        return $this->machinename;
    }

    public static function getVocabularyStatus(): int
    {
        return VocabularyInterface::VOCABULARY_NATIONAL;
    }

    public function getVocabularyName(): string
    {
        return 'Type de personnel';
    }

    /**
     * returns the form type name for template edition.
     */
    public static function getFormType(): string
    {
        return PublictypeVocabularyType::class;
    }
}


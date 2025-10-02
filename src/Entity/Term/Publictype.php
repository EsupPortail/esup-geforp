<?php
namespace App\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Term\AbstractTerm;
use App\Entity\Term\VocabularyInterface;
use JMS\Serializer\Annotation as Serializer;
use App\Form\Type\PublictypeVocabularyType;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Type de personnel.
 */
#[ORM\Table(name: 'publictype')]
#[ORM\Entity]
class Publictype extends AbstractTerm implements VocabularyInterface
{


    /**
     * @param string $machineName
     */
    public function setMachinename(string $machineName): void
    {
        $this->machinename = $machineName;
    }

    /**
     * @return string
     */
    public function getMachinename(): ?string
    {
        return $this->machinename;
    }

    public function __toString(): string
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


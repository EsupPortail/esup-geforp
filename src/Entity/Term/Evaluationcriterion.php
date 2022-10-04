<?php

namespace App\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Term\AbstractTerm;
use App\Entity\Term\VocabularyInterface;
use JMS\Serializer\Annotation as Serializer;

/**
 * Critère d'évaluation
 *
 * @ORM\Table(name="evaluation_criterion")
 * @ORM\Entity
 */
class Evaluationcriterion extends AbstractTerm implements VocabularyInterface
{
    /**
     * @return string
     */
    public function getVocabularyName()
    {
        return "Critère d'évaluation";
    }

    public static function getVocabularyStatus()
    {
        return VocabularyInterface::VOCABULARY_NATIONAL;
    }
}

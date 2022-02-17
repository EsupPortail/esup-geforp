<?php

namespace App\Entity\Core\Term;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Core\Term\AbstractTerm;
use App\Entity\Core\Term\VocabularyInterface;
use JMS\Serializer\Annotation as Serializer;

/**
 * Critère d'évaluation
 *
 * @ORM\Table(name="evaluation_criterion")
 * @ORM\Entity
 */
class EvaluationCriterion extends AbstractTerm implements VocabularyInterface
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

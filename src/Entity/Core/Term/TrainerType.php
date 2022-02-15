<?php

/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 6/22/16
 * Time: 5:46 PM.
 */
namespace App\Entity\Core\Term;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Core\Term\AbstractTerm;
use App\Entity\Core\Term\VocabularyInterface;

/**
 * Type de personnel.
 *
 * @ORM\Table(name="trainer_type")
 * @ORM\Entity
 */
class TrainerType extends AbstractTerm implements VocabularyInterface
{
    public static function getVocabularyStatus()
    {
        return VocabularyInterface::VOCABULARY_NATIONAL;
    }

    /**
     * @return string
     */
    public function getVocabularyName()
    {
        return "Type d'intervenant";
    }
}

<?php

/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 6/22/16
 * Time: 5:46 PM.
 */
namespace App\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Term\AbstractTerm;
use App\Entity\Term\VocabularyInterface;

/**
 * Type de personnel.
 *
 * @ORM\Table(name="trainer_type")
 * @ORM\Entity
 */
class Trainertype extends AbstractTerm implements VocabularyInterface
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

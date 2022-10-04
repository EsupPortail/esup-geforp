<?php

/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 5/25/16
 * Time: 10:14 AM.
 */
namespace App\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Term\AbstractTerm;
use App\Entity\Term\VocabularyInterface;

/**
 * Type de personnel.
 *
 * @ORM\Table(name="publictype")
 * @ORM\Entity
 */
class Publictype extends AbstractTerm implements VocabularyInterface
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
        return 'Type de personnel';
    }
}

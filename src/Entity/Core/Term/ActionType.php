<?php

/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 5/25/16
 * Time: 10:14 AM.
 */
namespace App\Entity\Core\Term;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Core\Term\AbstractTerm;
use App\Entity\Core\Term\VocabularyInterface;

/**
 * Type de personnel.
 *
 * @ORM\Table(name="action_type")
 * @ORM\Entity
 */
class ActionType extends AbstractTerm implements VocabularyInterface
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
        return 'Type d\'action de formation';
    }
}

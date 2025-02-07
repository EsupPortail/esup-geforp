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
 */
#[ORM\Table(name: 'action_type')]
#[ORM\Entity]
class Actiontype extends AbstractTerm implements VocabularyInterface
{
    public static function getVocabularyStatus(): int
    {
        return VocabularyInterface::VOCABULARY_NATIONAL;
    }

    public function getVocabularyName(): string
    {
        return 'Type d\'action de formation';
    }
}

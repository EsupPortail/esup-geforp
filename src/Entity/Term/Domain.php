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
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Type de personnel.
 *
 */
#[Groups(['institution'])]
#[ORM\Table(name: 'domain')]
#[ORM\Entity]
class Domain extends AbstractTerm implements VocabularyInterface
{
    public static function getVocabularyStatus(): int
    {
        return VocabularyInterface::VOCABULARY_NATIONAL;
    }

    public function getVocabularyName(): string
    {
        return 'Nom de domaine';
    }
}

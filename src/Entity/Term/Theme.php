<?php

namespace App\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Term\AbstractTerm;
use App\Entity\Term\VocabularyInterface;

/**
 * Theme.
 *
 * @ORM\Table(name="theme")
 * @ORM\Entity
 */
class Theme extends AbstractTerm implements VocabularyInterface
{
    /**
     * This term is required during term replacement.
     *
     * @var bool
     */
    static $replacementRequired = true;

    /**
     * @return mixed
     */
    public function getVocabularyName()
    {
        return 'Thématiques de formation';
    }

    public static function getVocabularyStatus()
    {
        return VocabularyInterface::VOCABULARY_LOCAl;
    }
}

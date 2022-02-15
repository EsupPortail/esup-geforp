<?php

namespace App\Entity\Core\Term;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Core\Term\AbstractTerm;
use App\Entity\Core\Term\VocabularyInterface;

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
        return VocabularyInterface::VOCABULARY_NATIONAL;
    }
}

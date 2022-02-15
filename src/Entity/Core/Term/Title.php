<?php

/**
 * Created by PhpStorm.
 * BaseUser: Erwan
 * Date: 27/05/14
 * Time: 16:43.
 */

namespace App\Entity\Core\Term;

use Doctrine\ORM\Mapping as ORM;

/**
 * Civilité.
 *
 * @ORM\Table(name="title")
 * @ORM\Entity
 */
class Title extends AbstractTerm implements VocabularyInterface
{
    /**
     * This term is required during term replacement.
     *
     * @var bool
     */
    public static $replacementRequired = true;

    /**
     * @return mixed
     */
    public function getVocabularyName()
    {
        return 'Civilités';
    }

    public static function getVocabularyStatus()
    {
        return VOCABULARY_NATIONAL;
    }
}

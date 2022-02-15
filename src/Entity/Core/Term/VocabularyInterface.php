<?php

namespace App\Entity\Core\Term;

/**
 * Interface VocabularyInterface.
 */
interface VocabularyInterface
{
    const VOCABULARY_NATIONAL = 0;
    const VOCABULARY_LOCAL = 1;
    const VOCABULARY_MIXED = 2;

    /**
     * @return bool
     */
    public static function getVocabularyStatus();

    /**
     * @return mixed
     */
    public function getVocabularyId();

    /**
     * @param string $id
     */
    public function setVocabularyId($id);

    /**
     * @return mixed
     */
    public function getVocabularyName();

}

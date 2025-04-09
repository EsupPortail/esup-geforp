<?php

namespace App\Entity\Term;

/**
 * Interface VocabularyInterface.
 */
interface VocabularyInterface
{
    /**
     * @var int
     */
    public const VOCABULARY_NATIONAL = 0;

    /**
     * @var int
     */
    public const VOCABULARY_LOCAL = 1;

    /**
     * @var int
     */
    public const VOCABULARY_MIXED = 2;

    /**
     * @return bool
     */
    public static function getVocabularyStatus(): int;

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
    public function getVocabularyName(): mixed;

}

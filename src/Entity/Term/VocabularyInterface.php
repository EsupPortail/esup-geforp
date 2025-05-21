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
    public const int VOCABULARY_NATIONAL = 0;

    /**
     * @var int
     */
    public const int VOCABULARY_LOCAL = 1;

    /**
     * @var int
     */
    public const int VOCABULARY_MIXED = 2;

    /**
     * @return bool
     */
    public static function getVocabularyStatus(): int;

    /**
     * @return mixed
     */
    public function getVocabularyId(): mixed;

    /**
     * @param string $id
     */
    public function setVocabularyId(string $id);

    /**
     * @return mixed
     */
    public function getVocabularyName(): mixed;

}

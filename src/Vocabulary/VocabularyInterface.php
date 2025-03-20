<?php

namespace App\Vocabulary;

use App\Entity\Back\Organization;

/**
 * Interface VocabularyInterface.
 */
interface VocabularyInterface
{
    /**
     * @var int
     */
    public const VOCABULARY_NATIONAL = 1;

    /**
     * @var int
     */
    public const VOCABULARY_LOCAL    = 1;

    /**
     * @var int
     */
    public const VOCABULARY_MIXED    = 2;

    /**
     * @return bool
     */
    public static function getVocabularyStatus(): int;

    /**
     * @return Organization|null mixed
     */
    public function getOrganization();

    /**
     * @param Organization $organization
     */
    public function setOrganization($organization);

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
    public function getVocabularyName(): string;

    public function isLocked();
}

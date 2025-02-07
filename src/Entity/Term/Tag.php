<?php

namespace App\Entity\Term;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Term\AbstractTerm;
use App\Entity\Term\VocabularyInterface;

/**
 * Tag
 *
 */
#[ORM\Table(name: 'tag')]
#[ORM\Entity]
class Tag extends AbstractTerm implements VocabularyInterface
{
    /**
     * @return mixed
     */
    public function getVocabularyName(): string
    {
        return "Tags";
    }

    public static function getVocabularyStatus(): int
    {
        return VocabularyInterface::VOCABULARY_LOCAL;
    }

    /**
     * @return mixed
     */
    public static function orderBy(): string
    {
        return 'name';
    }
}

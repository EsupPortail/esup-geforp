<?php

/**
 * Created by PhpStorm.
 * BaseUser: Erwan
 * Date: 27/05/14
 * Time: 16:43.
 */

namespace App\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use App\Form\Type\PresenceStatusVocabularyType;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Statut de présense.
 *
 */
#[ORM\Table(name: 'presence_status')]
#[ORM\Entity]
class Presencestatus extends AbstractTerm implements VocabularyInterface
{
    /**
     * @var int
     */
    final public const int STATUS_ABSENT = 0;

    /**
     * @var int
     */
    final public const int STATUS_PRESENT = 1;

    /**
     * This term is required during term replacement.
     *
     * @var bool
     */
    public static bool $replacementRequired = true;

    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[Groups(["Default", "api"])]
    #[ORM\Column(name: 'status', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected ?int $status = self::STATUS_ABSENT;

    /**
     * @param int $status
     */
    public function __construct(int $status = self::STATUS_ABSENT)
    {
        $this->setStatus($status);
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function getVocabularyName(): string
    {
        return 'Statut de présence';
    }

    public static function getVocabularyStatus(): int
    {
        return VocabularyInterface::VOCABULARY_MIXED;
    }

    /**
     * returns the form type name for template edition.
     *
     */
    public static function getFormType(): string
    {
        return PresenceStatusVocabularyType::class;
    }
}

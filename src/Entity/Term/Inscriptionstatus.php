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
use App\Form\Type\InscriptionStatusVocabularyType;
use Symfony\Component\Serializer\Attribute\Groups;

/**
 * Statut de l'inscription.
 *
 */
#[ORM\Table(name: 'inscription_status')]
#[ORM\Entity]
class Inscriptionstatus extends AbstractTerm implements VocabularyInterface
{
    /**
     * @var int
     */
    final public const int STATUS_PENDING = 0;

    /**
     * @var int
     */
    final public const int STATUS_WAITING = 1;

    /**
     * @var int
     */
    final public const int STATUS_ACCEPTED = 2;

    /**
     * @var int
     */
    final public const int STATUS_REJECTED = 3;

    /**
     * @var int
     */
    final public const int STATUS_CONVOKED = 4;

    /**
     * This term is required during term replacement.
     *
     * @var bool
     */
    //public static bool $replacementRequired = true;

    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'status', type: \Doctrine\DBAL\Types\Types::INTEGER)]
    protected ?int $status = self::STATUS_PENDING;

    /**
     * @Serializer\Groups({"Default", "api"})
     */
    #[Groups(['Default', 'api'])]
    #[ORM\Column(name: 'notify', type: \Doctrine\DBAL\Types\Types::BOOLEAN)]
    protected bool $notify = false;

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

    /**
     * @return int
     */
    public function getNotify(): bool|int|null
    {
        return $this->notify;
    }

    /**
     * @param int $notify
     */
    public function setNotify(int $notify): void
    {
        $this->notify = $notify;
    }

    public static function getVocabularyStatus(): int
    {
        return VocabularyInterface::VOCABULARY_MIXED;
    }

    public function getVocabularyName(): string
    {
        return "Statut de l'inscription";
    }

    /**
     * returns the form type name for template edition.
     *
     */
    public static function getFormType(): string
    {
        return InscriptionStatusVocabularyType::class;
    }
}

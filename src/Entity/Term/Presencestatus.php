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

/**
 * Statut de présense.
 *
 * @ORM\Table(name="presence_status")
 * @ORM\Entity
 */
class Presencestatus extends AbstractTerm implements VocabularyInterface
{
    const STATUS_ABSENT = 0;
    const STATUS_PRESENT = 1;

    /**
     * This term is required during term replacement.
     *
     * @var bool
     */
    public static $replacementRequired = true;

    /**
     * @var int
     * @ORM\Column(name="status", type="integer")
     * @Serializer\Groups({"Default", "api"})
     */
    protected $status = self::STATUS_ABSENT;

    /**
     * @param int $status
     */
    public function __construct($status = self::STATUS_ABSENT)
    {
        $this->setStatus($status);
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getVocabularyName()
    {
        return 'Statut de présence';
    }

    /**
     * @return int
     */
    public static function getVocabularyStatus()
    {
        return VocabularyInterface::VOCABULARY_MIXED;
    }

    /**
     * returns the form type name for template edition.
     *
     * @return string
     */
    public static function getFormType()
    {
        return PresenceStatusVocabularyType::class;
    }
}

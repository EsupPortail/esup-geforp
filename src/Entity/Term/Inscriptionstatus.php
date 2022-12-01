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

/**
 * Statut de l'inscription.
 *
 * @ORM\Table(name="inscription_status")
 * @ORM\Entity
 */
class Inscriptionstatus extends AbstractTerm implements VocabularyInterface
{
    const STATUS_PENDING = 0;
    const STATUS_WAITING = 1;
    const STATUS_ACCEPTED = 2;
    const STATUS_REJECTED = 3;

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
    protected $status = self::STATUS_PENDING;

    /**
     * @var int
     * @ORM\Column(name="notify", type="boolean")
     * @Serializer\Groups({"Default", "api"})
     */
    protected $notify = false;

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
     * @return int
     */
    public function getNotify()
    {
        return $this->notify;
    }

    /**
     * @param int $notify
     */
    public function setNotify($notify)
    {
        $this->notify = $notify;
    }

    public static function getVocabularyStatus()
    {
        return VocabularyInterface::VOCABULARY_LOCAL;
    }

    /**
     * @return string
     */
    public function getVocabularyName()
    {
        return "Statut de l'inscription";
    }

    /**
     * returns the form type name for template edition.
     *
     * @return string
     */
    public static function getFormType()
    {
        return InscriptionStatusVocabularyType::class;
    }
}

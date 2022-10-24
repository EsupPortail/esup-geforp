<?php

/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 5/25/16
 * Time: 10:14 AM.
 */
namespace App\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Term\AbstractTerm;
use App\Entity\Term\VocabularyInterface;
use JMS\Serializer\Annotation as Serializer;
use App\Form\Type\PublictypeVocabularyType;


/**
 * Type de personnel.
 *
 * @ORM\Table(name="publictype")
 * @ORM\Entity
 */
class Publictype extends AbstractTerm implements VocabularyInterface
{
    /**
     * @var int
     * @ORM\Column(name="machine_name", type="string", length=255)
     * @Serializer\Groups({"Default", "api"})
     */
    protected $machinename;

    /**
     * @param int $machinename
     */
    public function setMachinename($machinename)
    {
        $this->machinename = $machinename;
    }

    /**
     * @return int
     */
    public function getMachinename()
    {
        return $this->machinename;
    }

    public static function getVocabularyStatus()
    {
        return VocabularyInterface::VOCABULARY_NATIONAL;
    }

    /**
     * @return string
     */
    public function getVocabularyName()
    {
        return 'Type de personnel';
    }

    /**
     * returns the form type name for template edition.
     *
     * @return string
     */
    public static function getFormType()
    {
        return PublictypeVocabularyType::class;
    }

}

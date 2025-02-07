<?php

/**
 * Created by PhpStorm.
 * BaseUser: Erwan
 * Date: 27/05/14
 * Time: 16:43.
 */

namespace App\Entity\Term;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation as Serializer;
use App\Entity\Term\AbstractTerm;
use App\Entity\Term\VocabularyInterface;
use App\Form\Type\MenuItemType;

/**
 * Civilité.
 *
 */
#[ORM\Table(name: 'menu_item')]
#[ORM\Entity]
class MenuItem extends AbstractTerm implements VocabularyInterface
{
    /**
     *
     * @Serializer\Groups({"Default", "api"})
     */
    #[ORM\Column(name: 'link', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    #[Assert\NotBlank]
    private ?string $link = null;

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink($link): void
    {
        $this->link = $link;
    }

    /**
     * returns the form type name for template edition.
     *
     */
    public static function getFormType(): string
    {
        return MenuItemType::class;
    }

    /**
     * @return mixed
     */
    public function getVocabularyName(): string
    {
        return 'Onglet de menu';
    }

    public static function getVocabularyStatus(): int
    {
        return VocabularyInterface::VOCABULARY_NATIONAL;
    }
}

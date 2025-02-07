<?php

namespace App\Form\Type;

use App\Form\Type\VocabularyType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;

final class MenuItemType extends VocabularyType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        parent::buildForm($formBuilder, $options);

        $formBuilder
            ->add('link', UrlType::class, ['label' => 'Lien externe']);
    }

    public function getParent(): ?string
    {
      return VocabularyType::class;
    }
}

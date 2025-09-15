<?php

namespace App\Form\Type;

use App\Entity\Term\Publictype;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class PublictypeVocabularyType.
 */
final class PublictypeVocabularyType extends VocabularyType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        parent::buildForm($formBuilder, $options);

        $formBuilder->add('machinename', null, ['label' => 'Equivalent eduPersonAffiliation']);

    }

    public function getParent(): ?string
    {
        return VocabularyType::class;
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['data_class' => Publictype::class]);
    }
}


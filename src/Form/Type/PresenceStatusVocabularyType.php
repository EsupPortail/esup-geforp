<?php

namespace App\Form\Type;

use App\Entity\Term\Presencestatus;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class PresenceStatusVocabularyType extends VocabularyType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        parent::buildForm($formBuilder, $options);

        $formBuilder->add('status', ChoiceType::class, ['label' => 'Statut élémentaire', 'expanded' => true, 'multiple' => false, 'required' => true, 'choices' => ['Présent' => Presencestatus::STATUS_PRESENT, 'Absent' => Presencestatus::STATUS_ABSENT]]);
        $formBuilder->add('machinename', null, ['label' => 'Libellé court', 'required' => true]);
    }

    public function getParent(): ?string
    {
        return VocabularyType::class;
    }
}

<?php

namespace App\Form\Type;

use App\Entity\Term\Inscriptionstatus;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class InscriptionStatusVocabularyType.
 */
final class InscriptionStatusVocabularyType extends VocabularyType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        parent::buildForm($formBuilder, $options);

        $formBuilder->add('notify', CheckboxType::class, ['label' => "Pour les gestionnaires : notification de changement de statut", 'required' => false]);
        $formBuilder->add('status', ChoiceType::class, ['label' => 'Statut élémentaire', 'expanded' => true, 'multiple' => false, 'required' => true, 'choices' => ['Convoqué' => Inscriptionstatus::STATUS_CONVOKED, 'Accepté' => Inscriptionstatus::STATUS_ACCEPTED, 'En attente' => Inscriptionstatus::STATUS_WAITING, 'En attente de traitement' => Inscriptionstatus::STATUS_PENDING, 'Rejeté' => Inscriptionstatus::STATUS_REJECTED]]);
        $formBuilder->add('machinename', null, ['label' => 'Libellé court']);
    }

    public function getParent(): ?string
    {
        return VocabularyType::class;
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['data_class' => Inscriptionstatus::class]);
    }
}

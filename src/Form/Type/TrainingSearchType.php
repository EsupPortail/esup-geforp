<?php

namespace App\Form\Type;


use App\AccessRight\AccessRightRegistry;
use App\Entity\Core\AbstractTraining;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Bundle\SecurityBundle\Security;

final class TrainingSearchType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder
            ->add('mandatory', ChoiceType::class, ['label' => 'Stage obligatoire', 'choices' => ['Non' => 0, 'Oui' => 1], 'data' => 0])
            ->add('training', EntityHiddenType::class, ['label' => 'Nom de stage', 'class' => AbstractTraining::class]);
    }


    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['id' => 'trainingsearch']);
    }

    public function getName(): string
    {
        return 'trainingsearch';
    }
}
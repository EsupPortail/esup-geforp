<?php

/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 7/5/16
 * Time: 2:39 PM.
 */

namespace App\Form\Type;

use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTrainer;
use App\Entity\Core\AbstractParticipation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class AbstractParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $notBlank = new NotBlank(['message' => 'Vous devez sélectionner une session.']);
        $notBlank->addImplicitGroupName('session_add');

        $formBuilder
            ->add('trainer', EntityHiddenType::class, ['label' => 'Intervenant', 'class' => AbstractTrainer::class, 'constraints' => new NotBlank(['message' => 'Vous devez sélectionner un intervenant.'])])
            ->add('session', EntityHiddenType::class, ['label' => 'Session', 'class' => AbstractSession::class, 'constraints' => $notBlank]);
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['data_class' => AbstractParticipation::class]
        );
    }

}

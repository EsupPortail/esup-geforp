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

class AbstractParticipationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $notBlank = new NotBlank(array('message' => 'Vous devez sélectionner une session.'));
        $notBlank->addImplicitGroupName('session_add');

        $builder
            ->add('trainer', EntityHiddenType::class, array(
                'label' => 'Intervenant',
                'class' => AbstractTrainer::class,
                'constraints' => new NotBlank(array('message' => 'Vous devez sélectionner un intervenant.')),
            ))
            ->add('session', EntityHiddenType::class, array(
                'label' => 'Session',
                'class' => AbstractSession::class,
                'constraints' => $notBlank,
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array('data_class' => AbstractParticipation::class)
        );
    }

}

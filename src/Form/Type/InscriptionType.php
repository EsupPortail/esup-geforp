<?php

namespace App\Form\Type;


use App\Entity\Back\Inscription;
use App\Entity\Term\ActionType;
use App\Form\Type\EntityHiddenType;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Core\AbstractSession;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class InscriptionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('trainee', EntityHiddenType::class, array(
                'label' => 'Stagiaire',
                'class' => AbstractTrainee::class
            ))
            ->add('session', EntityHiddenType::class, array(
                'label' => 'Session',
                'class' => AbstractSession::class
            ))
            ->add('motivation', TextareaType::class, array(
                'label' => 'Motivation',
                'attr' => array('placeholder' => 'Expliquez les raisons pour lesquelles vous souhaitez vous inscrire à cette session.')
            ))
            ->add('actiontype', EntityType::class, array(
                'label' => 'Type de formation',
                'class' => ActionType::class
            ))
            ->add('dif', CheckboxType::class, array(
                'label' => 'Compte personnel de formation',
                'required' => false
            ))
            ->add('authorization', CheckboxType::class, array(
                'label' => 'Envoyer une demande d\'autorisation à mon supérieur hiérarchique',
                'mapped' => false,
                'required' => false,
                'disabled' => true,
                'attr' => array('checked'   => 'checked'),
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Inscription::class,
        ));
    }
}
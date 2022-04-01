<?php

/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 15/04/14
 * Time: 14:30.
 */

namespace App\Form\Type;

use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTraining;
use App\Entity\Core\Term\Sessiontype as Type;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AbstractSessionType.
 */
class AbstractSessionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('training', EntityHiddenType::class, array(
                'label' => 'Formation',
                'class' => AbstractTraining::class,
                'required' => true,
            ))
            ->add('datebegin', DateType::class, array(
                'label' => 'Date de début',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => true,
            ))
            ->add('dateend', DateType::class, array(
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => false,
            ))
            ->add('schedule', null, array(
                'label'    => "Horaires",
                'required' => false
            ))
            ->add('hourNumber', TextType::class, array(
                'label'    => "Nombre d'heures",
                'required' => true,
                'attr'     => array(
                    'min' => 1,
                    'max' => 999,
                ),
            ))
            ->add('dayNumber', TextType::class, array(
                'label'    => 'Nombre de jours',
                'required' => true,
                'attr'     => array(
                    'min' => 1,
                    'max' => 999,
                ),
            ))
            ->add('registration', ChoiceType::class, array(
                'label' => 'Inscriptions',
                'choices' => array(
                    'Désactivées' => AbstractSession::REGISTRATION_DEACTIVATED,
                    'Fermées' => AbstractSession::REGISTRATION_CLOSED,
                    'Privées' => AbstractSession::REGISTRATION_PRIVATE,
                    'Publiques' => AbstractSession::REGISTRATION_PUBLIC,
                ),
                'required' => false,
            ))
            ->add('promote', CheckboxType::class, array(
                'label' => 'Promouvoir',
            ))
            ->add('displayonline', ChoiceType::class, array(
                'label' => 'Afficher en ligne',
                'choices' => array(
                    'Non' => 0,
                    'Oui' => 1,
                ),
                'required' => false,
            ))
            ->add('status', ChoiceType::class, array(
                'label' => 'Statut',
                'choices' => array(
                    'Ouverte' => AbstractSession::STATUS_OPEN,
                    'Reportée' => AbstractSession::STATUS_REPORTED,
                    'Annulée' => AbstractSession::STATUS_CANCELED,
                ),
                'required' => false,
            ))
            ->add('sessiontype', EntityType::class, array(
                'label'    => 'Type',
                'class'    => Type::class,
                'required' => false,
            ))
            ->add('numberofregistrations', null, array(
                'label' => "Nombre d'inscrits",
                'required' => false,
            ))
            ->add('maximumnumberofregistrations', null, array(
                'label' => 'Participants max.',
                'required' => true,
            ))
           ->add('limitregistrationdate', DateType::class, array(
                'label' => "Date limite d'inscription",
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => true,
            ))
            ->add('comments', null, array(
                'required' => false,
                'label' => 'Commentaires',
            )) ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array('data_class' => AbstractSession::class)
        );
    }


}

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
use App\Entity\Term\Sessiontype as Type;
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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('training', EntityHiddenType::class, ['label' => 'Formation', 'class' => AbstractTraining::class, 'required' => true])
            ->add('datebegin', DateType::class, ['label' => 'Date de début', 'widget' => 'single_text', 'format' => 'dd/MM/yyyy', 'html5' => false, 'required' => true])
            ->add('dateend', DateType::class, ['label' => 'Date de fin', 'widget' => 'single_text', 'format' => 'dd/MM/yyyy', 'html5' => false, 'required' => false])
            ->add('schedule', null, ['label'    => "Horaires", 'required' => false])
            ->add('hournumber', TextType::class, ['label'    => "Nombre d'heures", 'required' => true, 'attr'     => ['min' => 1, 'max' => 999]])
            ->add('daynumber', TextType::class, ['label'    => 'Nombre de jours', 'required' => true,  'attr'     => ['min' => 1, 'max' => 999]])
            ->add('registration', ChoiceType::class, ['label' => 'Inscriptions', 'choices' => ['Désactivées' => AbstractSession::REGISTRATION_DEACTIVATED, 'Fermées' => AbstractSession::REGISTRATION_CLOSED, 'Privées' => AbstractSession::REGISTRATION_PRIVATE, 'Publiques' => AbstractSession::REGISTRATION_PUBLIC], 'required' => false])
            ->add('promote', CheckboxType::class, ['label' => 'Promouvoir'])
            ->add('displayonline', ChoiceType::class, ['label' => 'Afficher en ligne', 'choices' => ['Non' => 0, 'Oui' => 1], 'required' => false])
            ->add('status', ChoiceType::class, ['label' => 'Statut', 'choices' => ['Ouverte' => AbstractSession::STATUS_OPEN, 'Reportée' => AbstractSession::STATUS_REPORTED, 'Annulée' => AbstractSession::STATUS_CANCELED], 'required' => false])
            ->add('sessiontype', EntityType::class, ['label'    => 'Type', 'class'    => Type::class, 'required' => false])
            ->add('numberofregistrations', null, ['label' => "Nombre d'inscrits", 'required' => false])
            ->add('maximumnumberofregistrations', null, ['label' => 'Participants max.', 'required' => true])
           ->add('limitregistrationdate', DateType::class, ['label' => "Date limite d'inscription", 'widget' => 'single_text', 'format' => 'dd/MM/yyyy', 'html5' => false, 'required' => true])
            ->add('comments', null, ['required' => false, 'label' => 'Commentaires']) ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AbstractSession::class]
        );
    }


}

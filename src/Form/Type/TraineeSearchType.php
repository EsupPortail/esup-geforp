<?php

namespace App\Form\Type;


use App\Entity\Back\Inscription;
use App\Entity\Back\Institution;
use App\Entity\Term\ActionType;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Core\AbstractSession;
use App\Entity\Term\Theme;
use App\Entity\Back\Organization;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;

class TraineeSearchType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('institution', EntityType::class, array(
                'label' => 'Etablissement',
                'choice_label' => 'name',
                'class' => Institution::class
            ))
            ->add('nom', null, array(
                'label' => 'Recherche par nom',
                'required' => false,
                'attr' => array('placeholder' => 'Tapez un nom')
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
            'id' => 'traineesearch'
        ));
    }

    public function getName()
    {
        return 'traineesearch';
    }
}
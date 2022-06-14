<?php

namespace App\Form\Type;


use App\Entity\Inscription;
use App\Entity\Core\Term\ActionType;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\Term\Theme;
use App\Entity\Organization;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;


/**
 * Created by PhpStorm.
 */
class ProgramSearchType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('centre', EntityType::class, array(
                'label' => 'Etablissement organisateur',
                'choice_label' => 'name',
                'class' => Organization::class
            ))
            ->add('theme', EntityType::class, array(
                'label' => 'Domaine de formation',
                'choice_label' => 'name',
                'class' => Theme::class
            ))
            ->add('texte', null, array(
                'label' => 'Recherche par mot clé',
                'required' => false,
                'attr' => array('placeholder' => 'Tapez un mot clé')
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
            'id' => 'search'
        ));
    }

    public function getName()
    {
        return 'search';
    }
}
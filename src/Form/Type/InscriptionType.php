<?php

namespace App\Form\Type;


use App\Entity\Core\AbstractInscription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\Core\Term\ActionType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Class InscriptionType.
 */
class InscriptionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('motivation', TextareaType::class, array(
                'label' => 'Motivation',
                'required' => false
            ))
            ->add('actiontype', EntityType::class, array(
            'label' => 'Type de formation',
            'class' => ActionType::class
            ))
            ->add('dif', CheckboxType::class, array(
            'label'    => 'Compte personnel de formation',
            'required' => false,
            ));


        parent::buildForm($builder, $options);
    }


    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => AbstractInscription::class,
        ));
    }
}

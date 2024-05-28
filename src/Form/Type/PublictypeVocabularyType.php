<?php

namespace App\Form\Type;

use App\Entity\Term\Publictype;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class PublictypeVocabularyType.
 */
class PublictypeVocabularyType extends VocabularyType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('machinename', null, array(
            'label' => 'Equivalent eduPersonAffiliation',
        ));

    }

    /**
     * @return string
     */
    public function getParent()
    {
        return VocabularyType::class;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Publictype::class,
        ));
    }
}


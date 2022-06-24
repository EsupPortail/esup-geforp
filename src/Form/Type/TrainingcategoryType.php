<?php

/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 6/16/16
 * Time: 5:12 PM.
 */
namespace App\Form\Type;

use App\Form\Type\VocabularyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Security;

class TrainingcategoryType extends VocabularyType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices       = array('Stage' => 'internship');

        $builder->add('trainingType', ChoiceType::class, array(
            'label'    => 'Type de formation',
            'choices'  => $choices,
            'required' => true,
        ));

        parent::buildForm($builder, $options);
    }

    public function getParent()
    {
        return VocabularyType::class;
    }
}

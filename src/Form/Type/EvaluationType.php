<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/26/16
 * Time: 5:42 PM
 */

namespace App\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\Type\EvaluationNotedCriterionType;

class EvaluationType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $tabEval = $options['tab_eval'];
        $builder
            ->add('criteria', CollectionType::class, array(
                'label' => 'Critères d\'évaluation',
                'entry_type' => EvaluationNotedCriterionType::class,
                'entry_options' =>  array('tab_eval'  => $tabEval)
            ))
            ->add('message', null, array(
                'label' => $options['message'],
                'required' => false,
                'attr' => array(
                    'placeholder' => "Vous pouvez éventuellement laisser un message qui accompagnera votre évaluation."
                )
            ));
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'tab_eval' => array(
                4 => "Tout à fait d'accord",
                3 => "Plutôt d'accord",
                2 => "Pas vraiment d'accord",
                1 => "Pas du tout d'accord"),
            'message' => 'Message'));
    }
}
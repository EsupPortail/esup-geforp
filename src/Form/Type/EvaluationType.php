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
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\Type\EvaluationNotedCriterionType;

final class EvaluationType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $tabEval = $options['tab_eval'];
        $builder
            ->add('criteria', CollectionType::class, ['label' => 'Critères d\'évaluation', 'entry_type' => EvaluationNotedCriterionType::class, 'entry_options' =>  ['tab_eval'  => $tabEval], 'by_reference' => false,])
            ->add('message', null, ['label' => $options['message'], 'required' => false, 'attr' => ['placeholder' => "Vous pouvez éventuellement laisser un message qui accompagnera votre évaluation."]]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['tab_eval' => ["Tout à fait d'accord" => 4, "Plutôt d'accord" => 3, "Pas vraiment d'accord" => 2, "Pas du tout d'accord" => 1], 'message' => 'Message']);
    }
}
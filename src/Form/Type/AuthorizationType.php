<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/26/16
 * Time: 5:42 PM
 */

namespace App\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextAreaType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

final class AuthorizationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder
            ->add('validation', ChoiceType::class, ['choices' => ['Favorable' =>'ok', 'Défavorable' => 'nok'], 'expanded' => true, 'multiple' => false, 'data' => 'ok', 'label' => "Avis"])
            ->add('refuse', null, ['label' => 'Motif de refus', 'required' => false, 'attr' => ['placeholder' => 'Vous devez expliquer les raisons pour lesquelles vous émettez un avis défavorable à cette demande de formation.']]);


    }

}
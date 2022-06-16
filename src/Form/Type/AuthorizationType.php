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

class AuthorizationType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('validation', ChoiceType::class, array(
                'choices' => array('ok' => 'Favorable', 'nok' => 'Défavorable'),
                'expanded' => true,
                'multiple' => false,
                'data' => 'ok',
                'label' => "Avis"
            ))
            ->add('refuse', null, array(
                'label' => 'Motif de refus',
                'required' => false,
                'attr' => array(
                    'placeholder' => 'Vous devez expliquer les raisons pour lesquelles vous émettez un avis défavorable à cette demande de formation.'
                )
            ));


    }

}
<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/26/16
 * Time: 5:45 PM
 */

namespace App\Form\Type;


use App\Entity\Back\Inscription;
use App\Form\Type\EntityHiddenType;
use App\Entity\Term\EvaluationCriterion;
use App\Entity\Back\EvaluationNotedCriterion;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EvaluationNotedCriterionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $tabEval = $options['tab_eval'];
        $builder
            ->add('inscription', EntityHiddenType::class, array(
                'label' => 'Inscription',
                'class' => Inscription::class
            ))
            ->add('criterion', EntityHiddenType::class, array(
                'label' => 'Critère',
                'class' => EvaluationCriterion::class
            ));

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $notes = $event->getData();
            $form = $event->getForm();
            $config = $form->getConfig()->getOptions();
            $form->add('note', ChoiceType::class, array('label' => $notes->getCriterion()->getName(),
                'choices' => $config['tab_eval']
            ));
        });

    }

    /**
     * @param $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'        => EvaluationNotedCriterion::class,
            'tab_eval'         => array(
                "Tout à fait d'accord" => 4,
                "Plutôt d'accord" => 3,
                "Pas vraiment d'accord" => 2,
                "Pas du tout d'accord" => 1),
            'validation_groups' => array('Correspondent'),
        ));
    }
}
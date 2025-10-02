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

final class EvaluationNotedCriterionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder
            ->add('inscription', EntityHiddenType::class, ['label' => 'Inscription', 'class' => Inscription::class])
            ->add('criterion', EntityHiddenType::class, ['label' => 'Critère', 'class' => EvaluationCriterion::class]);

        $formBuilder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $formEvent) : void {
            $notes = $formEvent->getData();
            $form = $formEvent->getForm();
            $config = $form->getConfig()->getOptions();
            $form->add('note', ChoiceType::class, ['label' => $notes->getCriterion()->getName(), 'choices' => $config['tab_eval']]);
        });

    }

    /**
     * @param $resolver
     */
    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['data_class'        => EvaluationNotedCriterion::class, 'tab_eval'         => ["Tout à fait d'accord" => 4, "Plutôt d'accord" => 3, "Pas vraiment d'accord" => 2, "Pas du tout d'accord" => 1], 'validation_groups' => ['Correspondent']]);
    }
}
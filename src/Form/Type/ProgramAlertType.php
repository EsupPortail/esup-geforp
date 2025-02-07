<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/26/16
 * Time: 5:42 PM
 */

namespace App\Form\Type;


use App\Entity\Back\MultipleAlert;
use App\Form\Type\AlertType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextAreaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProgramAlertType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder
            ->add('alerts', CollectionType::class, ['entry_type' => AlertType::class, 'entry_options' => ['label' => false]]);

    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['data_class' => MultipleAlert::class]);
    }

}
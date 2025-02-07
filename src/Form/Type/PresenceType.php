<?php

/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 15/04/14
 * Time: 14:30.
 */
namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use App\Entity\Back\Presence;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class PresenceType.
 */
final class PresenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder
            ->add('morning', ChoiceType::class, ['label' => "Matin", 'required' => false, 'choices' => ['Absent' => 'Absent', 'Présent' => 'Présent']])
            ->add('afternoon', ChoiceType::class, ['label' => "Après-midi", 'required' => false, 'choices' => ['Absent' => 'Absent', 'Présent' => 'Présent']]);

    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['data_class' => Presence::class]
        );
    }
}

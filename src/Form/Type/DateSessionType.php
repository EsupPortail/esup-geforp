<?php

/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 15/04/14
 * Time: 14:30.
 */
namespace App\Form\Type;

use App\Entity\Core\AbstractSession;
use Doctrine\ORM\EntityRepository;
use App\Entity\Back\DateSession;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class DateSessionType.
 */
final class DateSessionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder
            ->add('datebegin', DateType::class, ['label' => 'Date de début', 'widget' => 'single_text', 'format' => 'dd/MM/yyyy', 'html5' => false, 'required' => true])
            ->add('dateend', DateType::class, ['label' => 'Date de fin', 'widget' => 'single_text', 'format' => 'dd/MM/yyyy', 'html5' => false, 'required' => true])
            ->add('schedulemorn', null, ['label' => "Horaires matin", 'required' => false])
            ->add('hournumbermorn', TextType::class, ['label'    => "Nombre d'heures matin", 'required' => true, 'attr'     => ['min' => 1, 'max' => 999]])
            ->add('scheduleafter', null, ['label' => "Horaires après-midi", 'required' => false])
            ->add('hournumberafter', TextType::class, ['label'    => "Nombre d'heures après-midi", 'required' => true, 'attr'     => ['min' => 1, 'max' => 999]])
            ->add('place', null, ['label' => "Lieu", 'required' => false]);
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['data_class' => DateSession::class]
        );
    }

}

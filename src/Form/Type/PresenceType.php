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
class PresenceType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('morning', ChoiceType::class, array(
                'label' => "Matin",
                'required' => false,
                'choices' => array(
                    'Absent' => 'Absent',
                    'Présent' => 'Présent')
                ))
            ->add('afternoon', ChoiceType::class, array(
                'label' => "Après-midi",
                'required' => false,
                'choices' => array(
                    'Absent' => 'Absent',
                    'Présent' => 'Présent')
            ));

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array('data_class' => Presence::class)
        );
    }
}

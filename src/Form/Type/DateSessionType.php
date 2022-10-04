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
class DateSessionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('datebegin', DateType::class, array(
                'label' => 'Date de début',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => true,
            ))
            ->add('dateend', DateType::class, array(
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => true,
            ))
            ->add('schedulemorn', null, array(
                'label' => "Horaires matin",
                'required' => false
            ))
            ->add('hournumbermorn', TextType::class, array(
                'label'    => "Nombre d'heures matin",
                'required' => true,
                'attr'     => array(
                    'min' => 1,
                    'max' => 999,
                ),
            ))
            ->add('scheduleafter', null, array(
                'label' => "Horaires après-midi",
                'required' => false
            ))
            ->add('hournumberafter', TextType::class, array(
                'label'    => "Nombre d'heures après-midi",
                'required' => true,
                'attr'     => array(
                    'min' => 1,
                    'max' => 999,
                ),
            ))
            ->add('place', null, array(
            'label' => "Lieu",
            'required' => false
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array('data_class' => DateSession::class)
        );
    }

}

<?php

namespace App\Form\Type;

use App\Entity\Back\Session;
use Doctrine\ORM\EntityRepository;
use App\Form\Type\AbstractSessionType;
use App\Entity\Core\AbstractSession;
use App\AccessRight\AccessRightRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class SessionType.
 */
class SessionType extends AbstractSessionType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var AbstractSession $session */
        $session = isset($options['data']) ? $options['data'] : null;

        $builder
            ->add('name', TextType::class, array(
                'label'    => "Intitulé",
                'required' => false
            ))
            ->add('teachingcost', TextType::class, array(
                'label'    => "Coûts pédagogiques",
                'required' => false
            ))
            ->add('vacationcost', TextType::class, array(
                'label'    => "Coûts en vacation",
                'required' => false
            ))
            ->add('accommodationcost', TextType::class, array(
                'label'    => "Frais de mission : hébergement",
                'required' => false
            ))
            ->add('mealcost', TextType::class, array(
                'label'    => "Frais de mission : repas",
                'required' => false
            ))
            ->add('transportcost', TextType::class, array(
                'label'    => "Frais de mission : transports",
                'required' => false
            ))
            ->add('materialcost', TextType::class, array(
                'label'    => "Frais de supports",
                'required' => false
            ))
            ->add('taking', TextType::class, array(
                'label'    => "Frais de supports",
                'required' => false
            ))
            ->add('price', TextType::class, array(
                'label'    => "Prix",
                'required' => false
            ));

        parent::buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array('data_class' => Session::class)
        );
    }

}

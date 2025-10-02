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
use Symfony\Component\Form\Extension\Core\Type\TextType;

/**
 * Class SessionType.
 */
final class SessionType extends AbstractSessionType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder
            ->add('name', TextType::class, ['label'    => "Intitulé", 'required' => false])
            ->add('teachingcost', TextType::class, ['label'    => "Coûts pédagogiques", 'required' => false])
            ->add('vacationcost', TextType::class, ['label'    => "Coûts en vacation", 'required' => false])
            ->add('accommodationcost', TextType::class, ['label'    => "Frais de mission : hébergement", 'required' => false])
            ->add('mealcost', TextType::class, ['label'    => "Frais de mission : repas", 'required' => false])
            ->add('transportcost', TextType::class, ['label'    => "Frais de mission : transports", 'required' => false])
            ->add('materialcost', TextType::class, ['label'    => "Frais de supports", 'required' => false])
            ->add('taking', TextType::class, ['label'    => "Frais de supports", 'required' => false])
            ->add('price', TextType::class, ['label'    => "Prix", 'required' => false]);

        parent::buildForm($formBuilder, $options);
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['data_class' => Session::class]
        );
    }

}

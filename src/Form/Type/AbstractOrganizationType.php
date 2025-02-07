<?php

namespace App\Form\Type;

use App\Entity\Core\AbstractOrganization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AbstractOrganizationType.
 */
final class AbstractOrganizationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        parent::buildForm($formBuilder, $options);

        $formBuilder
            ->add('name', null, ['label' => 'Nom'])
            ->add('code', null, ['label' => 'Code'])
            ->add('traineeRegistrable', null, ['label' => "Les stagiaires peuvent s'y inscrire"])
        ;
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['data_class' => AbstractOrganization::class]
        );
    }

}

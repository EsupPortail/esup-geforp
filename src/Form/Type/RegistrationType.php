<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class RegistrationType.
 */
final class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        parent::buildForm($formBuilder, $options);

        $formBuilder
            ->add('password', null, ['label' => 'Mot de passe', 'property_path' => 'plainPassword'])
        ;
    }

    /**
     * @param $resolver
     */
    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['csrf_protection' => false, 'validation_groups' => ['Default', 'trainee', 'api.profile', 'api.registration'], 'enable_security_check' => false, 'allow_extra_fields' => true]);
    }

    public function getParent(): ?string
    {
        return ProfileType::class;
    }
}

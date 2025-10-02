<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class UpdatePasswordType.
 */
final class UpdatePasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder->add('plainPassword', StrongPasswordType::class, ['attr' => ['user' => $options['data']]]);
    }
}

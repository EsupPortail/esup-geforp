<?php

/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 6/13/16
 * Time: 11:48 AM.
 */
namespace App\Form\Type;

use App\Form\Type\VocabularyType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;

final class SupervisorType extends VocabularyType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        parent::buildForm($formBuilder, $options);

        $formBuilder
            ->add('firstName', null, ['label' => 'Prénom'])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('phoneNumber', null, ['label'    => 'Numéro de téléphone', 'required' => false]);
    }

    public function getParent(): ?string
    {
        return VocabularyType::class;
    }
}

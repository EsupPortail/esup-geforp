<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

/**
 * Class CurrentPasswordType.
 */
final class CurrentPasswordType extends AbstractType
{
	/**
	 * @return string|\Symfony\Component\Form\FormTypeInterface|null
	 */
	public function getParent(): ?string
    {
        return PasswordType::class;
    }

	public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver
            ->setDefaults(['mapped' => false, 'label' => 'Mot de passe actuel', 'constraints' => [new NotBlank(['message' => 'Veuillez renseigner votre mot de passe actuel']), new UserPassword(['message' => "Mot de passe invalide"])]])
        ;
    }
}

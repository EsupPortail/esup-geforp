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
class CurrentPasswordType extends AbstractType
{
	/**
	 * @return string|\Symfony\Component\Form\FormTypeInterface|null
	 */
	public function getParent()
    {
        return PasswordType::class;
    }

	/**
	 * @param OptionsResolver $resolver
	 */
	public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults(array(
                'mapped' => false,
                'label' => 'Mot de passe actuel',
                'constraints' => array(
                    new NotBlank(array('message' => 'Veuillez renseigner votre mot de passe actuel')),
                    new UserPassword(array('message' => "Mot de passe invalide")),
                ),
            ))
        ;
    }
}

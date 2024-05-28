<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use App\Validator\Constraints\StrongPassword;

/**
 * Class StrongPasswordType.
 */
class StrongPasswordType extends AbstractType
{
	/**
	 * @return string|\Symfony\Component\Form\FormTypeInterface|null
	 */
	public function getParent()
    {
        return RepeatedType::class;
    }

	/**
	 * @param OptionsResolver $resolver
	 */
	public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'user' => null,
                'type' => PasswordType::class,
                'label' => 'Nouveau mot de passe',
                'first_options' => [
                    'label' => 'Nouveau mot de passe',
                ],
                'second_options' => [
                    'label' => 'Répétez le mot de passe',
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas',
                'constraints' => function (Options $options) {
                	$user = $options['user'];
                	if (!$user && isset($options['attr']) && isset($options['attr']['user']) && $options['attr']['user'] instanceof UserInterface) {
		                $user = $options['attr']['user'];
	                }
                    return [
                        new NotBlank(['message' => 'empty_password']),
                        new StrongPassword(['user' => $user]),
                    ];
                },
            ])
        ;
    }
}

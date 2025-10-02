<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormTypeInterface;
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
final class StrongPasswordType extends AbstractType
{
    public function getParent(): ?string
    {
        return RepeatedType::class;
    }

	public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver
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
                'constraints' => static function (Options $options) : array {
                    $user = $options['user'] ?? null;
                    if (!$user && isset($options['attr']['user']) && ($options['attr']['user']) instanceof UserInterface) {
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

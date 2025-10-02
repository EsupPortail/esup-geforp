<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 11/22/17
 * Time: 4:15 PM.
 */

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use App\Entity\Core\User;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AccountType.
 */
final class AccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder
            ->add('username', TextType::class, ['constraints' => new Length(['min' => 5]), 'invalid_message' => "Le nom d'utilisateur est trop court", 'label' => "Nom d'utilisateur"])
            ->add('email', EmailType::class, ['constraints' => new Email(['message' => 'Invalid email address']), 'label' => 'Email'])
            ->add('password', RepeatedType::class, ['type' =>  PasswordType::class, 'constraints' => new Length(['min' => 8]), 'required' => true, 'invalid_message' => 'Les mots de passe doivent correspondre', 'first_options' => ['label' => 'Mot de passe'], 'second_options' => ['label' => 'Confirmation']]);

    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['data_class' => User::class]);
    }
}

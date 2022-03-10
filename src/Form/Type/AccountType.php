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
class AccountType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', TextType::class, array(
                'constraints' => new Length(array('min' => 5)),
                'invalid_message' => 'Le nom d\'utilisateur est trop court',
                'label' => 'Nom d\'utilisateur',
            ))
            ->add('email', EmailType::class, array(
                'constraints' => new Email(array('message' => 'Invalid email address')),
                'label' => 'Email',
            ))
            ->add('password', RepeatedType::class, array(
                'type' =>  PasswordType::class,
                'constraints' => new Length(array('min' => 8)),
                'required' => true,
                'invalid_message' => 'Les mots de passe doivent correspondre',
                'first_options' => array('label' => 'Mot de passe'),
                'second_options' => array('label' => 'Confirmation'),
            ));

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => User::class,
        ));
    }
}

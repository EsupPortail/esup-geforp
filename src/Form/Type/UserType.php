<?php

/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 18/03/14
 * Time: 10:18.
 */

namespace App\Form\Type;

use App\Entity\Organization;
use Doctrine\ORM\EntityRepository;
use App\AccessRight\AccessRightRegistry;
use App\Entity\Core\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * Class UserType.
 */
class UserType extends AbstractType
{
    /**
     * @var AccessRightRegistry
     */
    private $accessRightsRegistry;

    /**
     * @param AccessRightRegistry $registry
     */
    public function __construct(AccessRightRegistry $registry)
    {
        $this->accessRightsRegistry = $registry;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('username', TextType::class, array(
            'constraints' => new Length(array('min' => 5)),
            'invalid_message' => 'Le nom d\'utilisateur est trop court',
            'label' => 'Nom d\'utilisateur',
        ))
            ->add('email', EmailType::class, array(
                'constraints' => new Email(array('message' => 'Invalid email address')),
                'label' => 'Email',
            ));

        $builder->add('password', RepeatedType::class, array(
            'type' =>  PasswordType::class,
            'constraints' => new Length(array('min' => 8)),
            'required' => true,
            'invalid_message' => 'Les mots de passe doivent correspondre',
            'first_options' => array('label' => 'Mot de passe'),
            'second_options' => array('label' => 'Confirmation'),
        ));


        $builder->add('organization', EntityType::class, array(
            'required' => true,
            'class' => Organization::class,
            'label' => 'Centre',
            'query_builder' => function (EntityRepository $er) {
                $res = $er->createQueryBuilder('o');
                return $res;

//                return $er->createQueryBuilder('o')->orderBy('o.name', 'ASC');
            },
        ));

        // add choice list for user creation
        if (!$options['data']->getId()) {
            $builder->add('accessRightScope', ChoiceType::class, array(
                'label' => 'Droits d\'accès',
                'mapped' => false,
                'choices' => array(
                    'own.view' => 'Droits locaux de lecture',
                    'own.manage' => 'Droits locaux de gestion',
                    'all.view' => 'Tous les droits de lecture',
                    'all.manage' => 'Tous les droits de gestion',
                ),
                'required' => false,
            ));
        }

        // If the user does not have the rights, remove the organization field and force the value
/*        $hasAccessRightForAll = $this->accessRightsRegistry->hasAccessRight('sygefor_core.access_right.user.all');
        if (!$hasAccessRightForAll) {
            $securityContext = $this->accessRightsRegistry->getSecurityContext();
            $user = $securityContext->getToken()->getUser();
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($user) {
                $trainer = $event->getData();
                $trainer->setOrganization($user->getOrganization());
                $event->getForm()->remove('organization');
            });
        } */
    }

	/**
	 * @param OptionsResolver $resolver
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => User::class,
			'validation_groups' => ['Default', 'user', 'organization'],
		));
	}
}

<?php

/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 18/03/14
 * Time: 10:18.
 */

namespace App\Form\Type;

use App\Entity\Back\Institution;
use App\Entity\Back\Organization;
use Doctrine\ORM\EntityRepository;
use App\AccessRight\AccessRightRegistry;
use App\Entity\Core\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
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
     * @var Security
     */
    private $security;

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
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
            'disabled' => true
        ))
            ->add('email', EmailType::class, array(
                'constraints' => new Email(array('message' => 'Invalid email address')),
                'label' => 'Email',
                'disabled' => true
            ));


        $builder->add('organization', EntityType::class, array(
            'required' => true,
            'class' => Organization::class,
            'label' => 'Centre',
            'query_builder' => function (EntityRepository $er) {
                $res = $er->createQueryBuilder('o');
                return $res;
            },
        ));

        $builder->add('isAdmin', CheckboxType::class, array(
            'label' => 'Administrateur',
            'mapped' => false,
            'required' => false
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
        // add listeners to handle conditionals fields
        $this->addEventListeners($builder);
    }

    /**
     * Add all listeners to manage conditional fields.
     */
    protected function addEventListeners(FormBuilderInterface $builder)
    {
        // PRE_SET_DATA for the parent form
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $userAccessRights = $this->security->getUser()->getAccessRights();

            if (in_array("sygefor_core.rights.user.all", $userAccessRights)) {
            } else {
                // si l'utilisateur n'a que les droits sur son centre
                if (in_array("sygefor_core.rights.user.own", $userAccessRights)) {
                    // Pas de choix possible pour l'établissement
                    $event->getForm()
                        ->add('organization', EntityType::class, array(
                        'required' => true,
                        'class' => Organization::class,
                        'label' => 'Centre',
                        'disabled' => true
                        ));
                }
            }
        });
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

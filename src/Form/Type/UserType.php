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
use Symfony\Bundle\SecurityBundle\Security;
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
final class UserType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder->add('username', TextType::class, ['constraints' => new Length(['min' => 5]), 'invalid_message' => "Le nom d'utilisateur est trop court", 'label' => "Nom d'utilisateur", 'disabled' => true])
            ->add('email', EmailType::class, ['constraints' => new Email(['message' => 'Invalid email address']), 'label' => 'Email', 'disabled' => true]);


        $formBuilder->add('organization', EntityType::class, ['required' => true, 'class' => Organization::class, 'label' => 'Centre', 'query_builder' => static fn(EntityRepository $entityRepository): \Doctrine\ORM\QueryBuilder => $entityRepository->createQueryBuilder('o')]);

        $formBuilder->add('isAdmin', CheckboxType::class, ['label' => 'Administrateur', 'mapped' => false, 'required' => false]);

        // add choice list for user creation
/*        if (!$options['data']->getId()) {
            $formBuilder->add('accessRightScope', ChoiceType::class, ['label' => 'Droits d\'accès', 'mapped' => false, 'choices' => ['own.view' => 'Droits locaux de lecture', 'own.manage' => 'Droits locaux de gestion', 'all.view' => 'Tous les droits de lecture', 'all.manage' => 'Tous les droits de gestion'], 'required' => false]);*/

        // add listeners to handle conditionals fields
        $this->addEventListeners($formBuilder);
    }

    /**
     * Add all listeners to manage conditional fields.
     */
    private function addEventListeners(FormBuilderInterface $formBuilder): void
    {
        // PRE_SET_DATA for the parent form
        $formBuilder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $formEvent): void {
            $userAccessRights = $this->security->getUser()->getAccessRights();

            if (in_array("sygefor_core.rights.user.all", $userAccessRights)) {
            } elseif (in_array("sygefor_core.rights.user.own", $userAccessRights)) {
                // si l'utilisateur n'a que les droits sur son centre
                // Pas de choix possible pour l'établissement
                $formEvent->getForm()
                    ->add('organization', EntityType::class, ['required' => true, 'class' => Organization::class, 'label' => 'Centre', 'disabled' => true]);
            }
        });
    }


	public function configureOptions(OptionsResolver $optionsResolver): void
	{
		$optionsResolver->setDefaults(['data_class' => User::class, 'validation_groups' => ['Default', 'user', 'organization']]);
	}
}

<?php

namespace App\Form\Type;

use App\Entity\Term\Publictype;
use Doctrine\ORM\EntityRepository;
use App\Entity\Term\Title;
use App\Entity\Back\Organization;
use App\Form\Type\AccountType;
use App\AccessRight\AccessRightRegistry;
use App\Entity\Core\AbstractOrganization;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Core\AbstractInstitution;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormInterface;

/**
 * Class TraineeType.
 */
final class AbstractTraineeType extends AbstractType
{
    /**InscriptionListener
     * @param AccessRightRegistry $accessRightsRegistry
     */
    public function __construct(protected AccessRightRegistry $accessRightRegistry)
    {
    }

    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder
            ->add('title', null, ['label' => 'Civilité'])
            ->add('lastname', null, ['label' => 'Nom'])
            ->add('firstname', null, ['label' => 'Prénom'])

            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('phonenumber', null, ['label'    => 'Numéro de téléphone', 'required' => false])

            ->add('addresstype', ChoiceType::class, ['required' => true, 'choices' => ['0' => 'Adresse personnelle', '1' => 'Adresse professionnelle'], 'label' => "Type d'adresse"])
            ->add('address', null, ['label'    => 'Adresse professionnelle', 'required' => false])
            ->add('zip', null, ['label'    => 'Code postal', 'required' => false])
            ->add('city', null, ['label'    => 'Ville', 'required' => false])
            ->add('institution', EntityType::class, ['label'         => 'Etablissement', 'class'         => AbstractInstitution::class])
            ->add('service', null, ['required' => false, 'label'    => 'Service'])
            ->add('isPaying', CheckboxType::class, ['required' => false, 'label'    => 'Payant'])
            ->add('status', null, ['required' => false, 'label'    => 'Statut'])
            ->add('publictype', EntityType::class, ['label'    => 'Type de personnel', 'class'    => Publictype::class, 'required' => false])
            ->add('birthdate', null, ['required' => false, 'label'    => 'Date de naissance (format aaaammjj)'])
            ->add('amustatut', null, ['required' => false, 'label'    => 'Statut'])
            ->add('bap', null, ['required' => false, 'label'    => 'BAP'])
            ->add('corps', null, ['required' => false, 'label'    => 'Corps'])
            ->add('category', null, ['required' => false, 'label'    => 'Catégorie'])
            ->add('campus', null, ['required' => false, 'label'    => 'Campus'])
            ->add('lastnamesup', null, ['required' => false, 'label'    => 'Nom'])
            ->add('firstnamesup', null, ['required' => false, 'label'    => 'Prénom'])
            ->add('emailsup', null, ['required' => false, 'label'    => 'Email', 'attr' => ['placeholder' => 'Entrez le mail INSTITUTIONNEL de votre responsable hiérarchique']])
            ->add('lastnamecorr', null, ['required' => false, 'label'    => 'Nom'])
            ->add('firstnamecorr', null, ['required' => false, 'label'    => 'Prénom'])
            ->add('emailcorr', null, ['required' => false, 'label'    => 'Email'])
            ->add('fonction', null, ['required' => true, 'label'    => 'Fonction exercée'])
            ->add('isActive', CheckboxType::class, ['label' => 'Validé', 'required' => false]);

        // add listeners to handle conditionals fields
        $this->addEventListeners($formBuilder);

    }

    /**
     * Add all listeners to manage conditional fields.
     */
    private function addEventListeners(FormBuilderInterface $formBuilder): void
    {
        // PRE_SET_DATA for the parent form
        $formBuilder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $formEvent) : void {
            $user = $formEvent->getData();
            //recuperation de l'objet sur lequel le formulaire se base
            // Si le stagaire est prÃ©-rempli
            if ($user->getLastname() != null) {
                if (($user->getPublictype() != null) && ($user->getPublictype()->getId() == 1)) { // Cas des biatss (employee) -> responsable hiÃ©rarchique obligatoire
                    $formEvent->getForm()
                        ->add('lastnamesup', null, ['required' => true, 'label' => 'Nom'])
                        ->add('firstnamesup', null, ['required' => true, 'label' => 'Prénom'])
                        ->add('emailsup', null, ['required' => true, 'label' => 'Email', 'attr' => ['placeholder' => 'Entrez le mail INSTITUTIONNEL de votre responsable hiérarchique']]);
                    /*                        ->add('lastNameAut', null, array(
                                                'required' => true,
                                                'label' => 'Nom',
                                            ))
                                            ->add('firstNameAut', null, array(
                                                'required' => true,
                                                'label' => 'Prénom',
                                            ))
                                            ->add('emailAut', null, array(
                                                'required' => true,
                                                'label' => 'Email',
                                            ));*/
                } else { // Autres cas : saisie du responsable non obligatoire
                    $formEvent->getForm()
                        ->add('lastnamesup', null, ['required' => false, 'label' => 'Nom'])
                        ->add('firstnamesup', null, ['required' => false, 'label' => 'Prénom'])
                        ->add('emailsup', null, ['required' => false, 'label' => 'Email', 'attr' => ['placeholder' => 'Entrez le mail INSTITUTIONNEL de votre responsable hiérarchique']]);
                    /*                        ->add('lastNameAut', null, array(
                                                'required' => false,
                                                'label' => 'Nom',
                                            ))
                                            ->add('firstNameAut', null, array(
                                                'required' => false,
                                                'label' => 'Prénom',
                                            ))
                                            ->add('emailAut', null, array(
                                                'required' => false,
                                                'label' => 'Email',
                                            ));*/
                }
            }
        });

    }

	public function configureOptions(OptionsResolver $optionsResolver): void
	{
		$optionsResolver->setDefaults(['data_class' => AbstractTrainee::class, 'validation_groups' => ['Default', 'trainee', 'organization'], 'enable_security_check' => true]);
	}
}

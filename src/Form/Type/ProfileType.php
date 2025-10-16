<?php

namespace App\Form\Type;

use App\Entity\Term\Publictype;
use App\Entity\Back\Trainee;
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
 * Class ProfileType.
 */
final class ProfileType extends AbstractType
{
    /**InscriptionListener
     * @param AccessRightRegistry $accessRightsRegistry
     */
    public function __construct(protected AccessRightRegistry $accessRightRegistry)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', null, ['label' => 'Civilité', 'disabled' => true])
            ->add('lastname', null, ['label' => 'Nom', 'disabled' => true])
            ->add('firstname', null, ['label' => 'Prénom', 'disabled' => true])

            ->add('email', EmailType::class, ['label' => 'Email', 'disabled' => true])
            ->add('phonenumber', null, ['label'    => 'Numéro de téléphone', 'required' => false, 'disabled' => true])
            ->add('address', null, ['label'    => 'Adresse professionnelle', 'required' => false])
            ->add('zip', null, ['label'    => 'Code postal', 'required' => false])
            ->add('city', null, ['label'    => 'Ville', 'required' => false])
            ->add('institution', EntityType::class, ['label'         => 'Etablissement', 'class'         => AbstractInstitution::class, 'disabled' => true])
            ->add('service', null, ['required' => false, 'label'    => 'Service', 'disabled' => true])
            ->add('publictype', EntityType::class, ['label'    => 'Type de personnel', 'class'    => Publictype::class, 'required' => false, 'disabled' => true])
            ->add('birthdate', null, ['required' => false, 'label'    => 'Date de naissance (format aaaammjj)', 'disabled' => true])
            ->add('amustatut', null, ['required' => false, 'label'    => 'Statut', 'disabled' => true])
            ->add('bap', null, ['required' => false, 'label'    => 'BAP', 'disabled' => true])
            ->add('corps', null, ['required' => false, 'label'    => 'Corps', 'disabled' => true])
            ->add('category', null, ['required' => false, 'label'    => 'Catégorie', 'disabled' => true])
            ->add('campus', null, ['required' => false, 'label'    => 'Campus', 'disabled' => true])
            ->add('lastnamesup', null, ['required' => false, 'label'    => 'Nom'])
            ->add('firstnamesup', null, ['required' => false, 'label'    => 'Prénom'])
            ->add('emailsup', null, ['required' => false, 'label'    => 'Email', 'attr' => ['placeholder' => 'Entrez le mail INSTITUTIONNEL de votre responsable hiérarchique']])
            ->add('lastnamecorr', null, ['required' => false, 'label'    => 'Nom'])
            ->add('firstnamecorr', null, ['required' => false, 'label'    => 'Prénom'])
            ->add('emailcorr', null, ['required' => false, 'label'    => 'Email'])
            ->add('fonction', null, ['required' => true, 'label'    => 'Fonction exercée']);

        // add listeners to handle conditionals fields
        $this->addEventListeners($builder);

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

                } else { // Autres cas : saisie du responsable non obligatoire
                    $formEvent->getForm()
                        ->add('lastnamesup', null, ['required' => false, 'label' => 'Nom'])
                        ->add('firstnamesup', null, ['required' => false, 'label' => 'Prénom'])
                        ->add('emailsup', null, ['required' => false, 'label' => 'Email', 'attr' => ['placeholder' => 'Entrez le mail INSTITUTIONNEL de votre responsable hiérarchique']]);
                }
            }
        });
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(['data_class' => Trainee::class, 'validation_groups' => ['Default', 'trainee'], 'enable_security_check' => true]);
	}
}

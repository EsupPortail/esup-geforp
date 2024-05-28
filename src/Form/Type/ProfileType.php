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
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormInterface;

/**
 * Class ProfileType.
 */
class ProfileType extends AbstractType
{
    /** @var AccessRightRegistry $accessRightsRegistry */
    protected $accessRightsRegistry;

    /**
     * @var Security
     */
    private $security;

    /**InscriptionListener
     * @param AccessRightRegistry $accessRightsRegistry
     */
    public function __construct(AccessRightRegistry $accessRightsRegistry, Security $security)
    {
        $this->accessRightsRegistry = $accessRightsRegistry;
        $this->security = $security;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', null, array(
                'label' => 'Civilité',
                'disabled' => true
            ))
            ->add('lastname', null, array(
                'label' => 'Nom',
                'disabled' => true
            ))
            ->add('firstname', null, array(
                'label' => 'Prénom',
                'disabled' => true
            ))

            ->add('email', EmailType::class, array(
                'label' => 'Email',
                'disabled' => true
            ))
            ->add('phonenumber', null, array(
                'label'    => 'Numéro de téléphone',
                'required' => false,
                'disabled' => true
            ))
            ->add('address', null, array(
                'label'    => 'Adresse professionnelle',
                'required' => false,
            ))
            ->add('zip', null, array(
                'label'    => 'Code postal',
                'required' => false,
            ))
            ->add('city', null, array(
                'label'    => 'Ville',
                'required' => false,
            ))
            ->add('institution', EntityType::class, array(
                'label'         => 'Etablissement',
                'class'         => AbstractInstitution::class,
                'disabled' => true
            ))
            ->add('service', null, array(
                'required' => false,
                'label'    => 'Service',
                'disabled' => true
            ))
            ->add('publictype', EntityType::class, array(
                'label'    => 'Type de personnel',
                'class'    => Publictype::class,
                'required' => false,
                'disabled' => true
            ))
            ->add('birthdate', null, array(
                'required' => false,
                'label'    => 'Date de naissance (format aaaammjj)',
                'disabled' => true
            ))
            ->add('amustatut', null, array(
                'required' => false,
                'label'    => 'Statut',
                'disabled' => true
            ))
            ->add('bap', null, array(
                'required' => false,
                'label'    => 'BAP',
                'disabled' => true
            ))
            ->add('corps', null, array(
                'required' => false,
                'label'    => 'Corps',
                'disabled' => true
            ))
            ->add('category', null, array(
                'required' => false,
                'label'    => 'Catégorie',
                'disabled' => true
            ))
            ->add('campus', null, array(
                'required' => false,
                'label'    => 'Campus',
                'disabled' => true
            ))
            ->add('lastnamesup', null, array(
                'required' => false,
                'label'    => 'Nom',
            ))
            ->add('firstnamesup', null, array(
                'required' => false,
                'label'    => 'Prénom',
            ))
            ->add('emailsup', null, array(
                'required' => false,
                'label'    => 'Email',
                'attr' => array('placeholder' => 'Entrez le mail INSTITUTIONNEL de votre responsable hiérarchique')
            ))
            ->add('lastnamecorr', null, array(
                'required' => false,
                'label'    => 'Nom',
            ))
            ->add('firstnamecorr', null, array(
                'required' => false,
                'label'    => 'Prénom',
            ))
            ->add('emailcorr', null, array(
                'required' => false,
                'label'    => 'Email',
            ))
            ->add('fonction', null, array(
                'required' => true,
                'label'    => 'Fonction exercée',
            ));

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
            $user = $event->getData();//recuperation de l'objet sur lequel le formulaire se base
            // Si le stagaire est prÃ©-rempli
            if ($user->getLastname()!=null) {
                if (($user->getPublictype() != null) && ($user->getPublictype()->getId() == 1)) { // Cas des biatss (employee) -> responsable hiÃ©rarchique obligatoire
                    $event->getForm()
                        ->add('lastnamesup', null, array(
                            'required' => true,
                            'label' => 'Nom',
                        ))
                        ->add('firstnamesup', null, array(
                            'required' => true,
                            'label' => 'Prénom',
                        ))
                        ->add('emailsup', null, array(
                            'required' => true,
                            'label' => 'Email',
                            'attr' => array('placeholder' => 'Entrez le mail INSTITUTIONNEL de votre responsable hiérarchique')
                        ));

                } else { // Autres cas : saisie du responsable non obligatoire
                    $event->getForm()
                        ->add('lastnamesup', null, array(
                            'required' => false,
                            'label' => 'Nom',
                        ))
                        ->add('firstnamesup', null, array(
                            'required' => false,
                            'label' => 'Prénom',
                        ))
                        ->add('emailsup', null, array(
                            'required' => false,
                            'label' => 'Email',
                            'attr' => array('placeholder' => 'Entrez le mail INSTITUTIONNEL de votre responsable hiérarchique')
                        ));
                }
            }
        });
    }

    /**
     * Add institution field depending organization.
     *
     * @param FormInterface $form
     * @param Organization  $organization
     */
    protected function addInstitutionField(FormInterface $form, $organization)
    {
        if ($organization) {
            $form->add('institution', EntityType::class, array(
                'class'         => AbstractInstitution::class,
                'label'         => 'Etablissement',
                'query_builder' => function (EntityRepository $er) use ($organization) {
                    return $er->createQueryBuilder('i')
                        ->where('i.organization = :organization')
                        ->setParameter('organization', $organization)
                        ->orWhere('i.organization is null')
                        ->orderBy('i.name', 'ASC');
                },
            ));
        }
    }

	/**
	 * @param OptionsResolver $resolver
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => Trainee::class,
			'validation_groups' => ['Default', 'trainee'],
			'enable_security_check' => true,
		));
	}
}

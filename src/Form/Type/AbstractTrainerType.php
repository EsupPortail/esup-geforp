<?php

namespace App\Form\Type;

use App\Entity\Core\AbstractOrganization;
use App\Entity\Institution;
use Doctrine\ORM\EntityRepository;
use App\Security\AccessRight\AccessRightRegistry;
use App\Entity\Organization;
use App\Entity\Core\Term\Title;
use App\Entity\Core\AbstractTrainer;
use App\Entity\Core\AbstractInstitution;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\SecurityContext;


/**
 * Class TrainerType.
 */
class AbstractTrainerType extends AbstractType
{
    /**
     * @var AccessRightRegistry
     */
    private $accessRightsRegistry;

    /**
     * @var Security
     */
    private $security;

    /**
     */
    public function __construct(AccessRightRegistry $registry, Security $security)
    {
        $this->accessRightsRegistry = $registry;
        $this->security = $security;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // this field will be removed by a listener after a failed rights check
            ->add('organization', EntityType::class, array(
                'required'      => true,
                'class'         => Organization::class,
                'label'         => 'Centre',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('o')->orderBy('o.name', 'ASC');
                },
            ))
            ->add('title', EntityType::class, array(
                'label'    => 'Civilité',
                'class'    => Title::class,
                'required' => true,
            ))
            ->add('firstname', null, array(
                'label' => 'Prénom',
            ))
            ->add('lastname', null, array(
                'label' => 'Nom',
            ))
            ->add('email', EmailType::class, array(
                'label' => 'Email',
            ))
            ->add('phonenumber', null, array(
                'label' => 'Numéro de téléphone',
            ))
            ->add('website', UrlType::class, array(
                'label' => 'Site internet',
            ))
            ->add('addresstype', ChoiceType::class, array(
                'label' => 'Type d\'adresse',
                'choices' => array(
                    '0' => 'Adresse personnelle',
                    '1' => 'Adresse professionnelle'
                ),
                'required' => false
            ))
            ->add('address', null, array(
                'label' => 'Adresse',
            ))
            ->add('zip', null, array(
                'label' => 'Code postal',
            ))
            ->add('city', null, array(
                'label' => 'Ville',
            ))
            ->add('trainerType', EntityType::class, array(
                'label'    => "Type d'intervenant",
                'class'    => \App\Entity\Core\Term\TrainerType::class,
                'required' => false,
            ))
            ->add('service', null, array(
                'label' => 'Service',
            ))
            ->add('status', null, array(
                'label' => 'Statut',
            ))
            ->add('isArchived', null, array(
                'label' => 'Archivé',
            ))
            ->add('isAllowSendMail', null, array(
                'label' => 'Autoriser les courriels',
            ))
            ->add('isOrganization', null, array(
                'label' => 'Formateur interne',
            ))
            ->add('isPublic', null, array(
                'label' => 'Publié sur le web',
            ))
            ->add('comments', null, array(
                'label' => 'Observations',
            ));

        // If the user does not have the rights, remove the organization field and force the value
/*        $hasAccessRightForAll = $this->accessRightsRegistry->hasAccessRight('sygefor_core.access_right.trainer.all.create');
        if (!$hasAccessRightForAll) {
            $securityContext = $this->accessRightsRegistry->getSecurityContext();
            $user = $securityContext->getToken()->getUser();*/
            $user            = $this->security->getUser();
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($user) {
                $trainer = $event->getData();
                $trainer->setOrganization($user->getOrganization());
                $event->getForm()->remove('organization');
            });/*
        }*/
    }

    /**
     * Add all listeners to manage conditional fields.
     */
    protected function addEventListeners(FormBuilderInterface $builder)
    {
        // PRE_SET_DATA for the parent form
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $this->addInstitutionField($event->getForm(), $event->getData()->getOrganization());
        });
        // POST_SUBMIT for each field
        if($builder->has('organization')) {
            $builder->get('organization')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $this->addInstitutionField($event->getForm()->getParent(), $event->getForm()->getData());
            });
        }
    }

    /**
     * Add institution field depending organization.
     *
     * @param FormInterface $form
     * @param Organization $organization
     */
    function addInstitutionField(FormInterface $form, $organization)
    {
        if ($organization) {
            $form->add('institution', EntityType::class, array(
                'label'         => 'Etablissement',
                'class'         => Institution::class,
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
			'data_class' => AbstractTrainer::class,
		));
	}
}

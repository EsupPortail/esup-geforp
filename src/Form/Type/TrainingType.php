<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use App\Entity\Back\Organization;
use App\Entity\Core\AbstractInstitution;
use App\Entity\Term\Supervisor;
use App\Entity\Term\Tag;
use App\Entity\Term\Theme;
use App\Entity\Term\Trainingcategory;
use App\Entity\Core\AbstractTraining;
use App\AccessRight\AccessRightRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class TrainingType.
 */
class TrainingType extends AbstractType
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
     * @param AccessRightRegistry $registry
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
        /** @var AbstractTraining $training */
        $training = isset($options['data']) ? $options['data'] : null;

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
            ->add('name', null, array(
                'label' => 'Titre',
            ))
            ->add('theme', EntityType::class, array(
                'label' => 'Thématique',
                'class' => Theme::class,
            ))
            ->add('program', null, array(
                'label'    => 'Programme',
                'required' => false,
            ))
            ->add('description', null, array(
                'label'    => 'Objectifs',
                'required' => true,
            ))
            ->add('teachingmethods', null, array(
                'label'    => 'Méthodes pédagogiques',
                'required' => false,
            ))
            ->add('interventiontype', null, array(
                'label'    => 'Type d\'intervention',
                'required' => false,
            ))
            ->add('externalinitiative', CheckboxType::class, array(
                'label'    => 'Initiative externe',
                'required' => false,
            ))
            ->add('category', EntityType::class, array(
                'label'         => 'Catégorie de formation',
                'class'         => Trainingcategory::class,
                'query_builder' => $training ? function (EntityRepository $er) use ($training) {
                    return $er->createQueryBuilder('c')
                        ->where('c.trainingType = :trainingType')
                        ->setParameter('trainingType', $training->getType());
                } : null,
                'required' => false,
            ))
            ->add('comments', null, array(
                'label'    => 'Commentaires',
                'required' => false,
            ))
            ->add('firstsessionperiodsemester', ChoiceType::class, array(
                'label'    => '1ère session',
                'choices'  => array('1er semestre' => '1', '2nd semestre' => '2'),
                'required' => true,
            ))
            ->add('firstsessionperiodyear', null, array(
                'label'    => 'Année',
                'required' => true,
            ));

        // add listeners to handle conditionals fields
        $this->addEventListeners($builder);

        // If the user does not have the rights, remove the organization field and force the value
        $hasAccessRightForAll = $this->accessRightsRegistry->hasAccessRight('sygefor_training.rights.training.all.create');
        if (!$hasAccessRightForAll) {
            $user            = $this->security->getUser();
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($user) {
                $training = $event->getData();
                $training->setOrganization($user->getOrganization());
                $event->getForm()->remove('organization');
            });
        }
    }

    /**
     * Add all listeners to manage conditional fields.
     */
    protected function addEventListeners(FormBuilderInterface $builder)
    {
        // PRE_SET_DATA for the parent form
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $this->addSupervisorField($event->getForm(), $event->getData()->getOrganization());
            $this->addTagField($event->getForm(), $event->getData()->getOrganization());
        });

        // POST_SUBMIT for each field
        if ($builder->has('organization')) {
            $builder->get('organization')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $this->addSupervisorField($event->getForm()->getParent(), $event->getForm()->getData());
                $this->addTagField($event->getForm()->getParent(), $event->getForm()->getData());
            });
        }
    }


    /**
     * Add supervisor field depending organization.
     *
     * @param FormInterface $form
     * @param Organization  $organization
     */
    protected function addSupervisorField(FormInterface $form, $organization)
    {
        if ($organization) {
            $form->add('supervisor', EntityType::class, array(
                'class'         => Supervisor::class,
                'label'         => 'Responsable pédagogique',
                'query_builder' => function (EntityRepository $er) use ($organization) {
                    return $er->createQueryBuilder('s')
                        ->where('s.organization = :organization')
                        ->setParameter('organization', $organization)
                        ->orWhere('s.organization is null')
                        ->orderBy('s.name', 'ASC');
                },
                'required' => false,
            ));
        }
    }

    /**
     * Add institution field depending organization.
     *
     * @param FormInterface $form
     * @param Organization  $organization
     */
    protected function addTagField(FormInterface $form, $organization)
    {
        if ($organization) {
            $form->add('tags', EntityType::class, array(
                'label' => 'Tags',
                'class' => Tag::class,
                'choice_label' => 'name',
                'multiple' => true,
                'required' => false,
                'query_builder' => function (EntityRepository $er) use ($organization) {
                    return $er->createQueryBuilder('t')
                        ->where('t.organization = :organization')
                        ->setParameter('organization', $organization)
                        ->orWhere('t.organization is null')
                        ->orderBy('t.name', 'ASC');
                },
            ));
        }
    }

}

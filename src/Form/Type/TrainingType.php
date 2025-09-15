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
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Class TrainingType.
 */
class TrainingType extends AbstractType
{
    public function __construct(private readonly AccessRightRegistry $accessRightRegistry, private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AbstractTraining $training */
        $training = $options['data'] ?? null;

        $builder
            // this field will be removed by a listener after a failed rights check
            ->add('organization', EntityType::class, ['required'      => true, 'class'         => Organization::class, 'label'         => 'Centre', 'query_builder' => static fn(EntityRepository $entityRepository): \Doctrine\ORM\QueryBuilder => $entityRepository->createQueryBuilder('o')->orderBy('o.name', 'ASC')])
            ->add('name', null, ['label' => 'Titre'])
            ->add('program', null, ['label'    => 'Programme', 'required' => false])
            ->add('description', null, ['label'    => 'Objectifs', 'required' => true])
            ->add('teachingmethods', null, ['label'    => 'Méthodes pédagogiques', 'required' => false])
            ->add('interventiontype', null, ['label'    => "Type d'intervention", 'required' => false])
            ->add('externalinitiative', CheckboxType::class, ['label'    => 'Initiative externe', 'required' => false])
            ->add('category', EntityType::class, ['label'         => 'Catégorie de formation', 'class'         => Trainingcategory::class, 'query_builder' => $training ? static fn(EntityRepository $entityRepository): \Doctrine\ORM\QueryBuilder => $entityRepository->createQueryBuilder('c')
                ->where('c.trainingType = :trainingType')
                ->setParameter('trainingType', $training->getType()) : null, 'required' => false])
            ->add('comments', null, ['label'    => 'Commentaires', 'required' => false])
            ->add('firstsessionperiodsemester', ChoiceType::class, ['label'    => '1ère session', 'choices'  => ['1er semestre' => '1', '2nd semestre' => '2'], 'required' => true])
            ->add('firstsessionperiodyear', null, ['label'    => 'Année', 'required' => true]);

        // add listeners to handle conditionals fields
        $this->addEventListeners($builder);

        // If the user does not have the rights, remove the organization field and force the value
        $hasAccessRightForAll = $this->accessRightRegistry->hasAccessRight('sygefor_training.rights.training.all.create');
        if (!$hasAccessRightForAll) {
            $user            = $this->security->getUser();
            $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $formEvent) use ($user) : void {
                $training = $formEvent->getData();
                $training->setOrganization($user->getOrganization());
                $formEvent->getForm()->remove('organization');
            });
        }
    }

    /**
     * Add all listeners to manage conditional fields.
     */
    protected function addEventListeners(FormBuilderInterface $formBuilder)
    {
        // PRE_SET_DATA for the parent form
        $formBuilder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $formEvent): void {
            $this->addSupervisorField($formEvent->getForm(), $formEvent->getData()->getOrganization());
            $this->addTagField($formEvent->getForm(), $formEvent->getData()->getOrganization());
            $this->addThemeField($formEvent->getForm(), $formEvent->getData()->getOrganization());
        });

        // POST_SUBMIT for each field
        if ($formBuilder->has('organization')) {
            $formBuilder->get('organization')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $formEvent): void {
                $this->addSupervisorField($formEvent->getForm()->getParent(), $formEvent->getForm()->getData());
                $this->addTagField($formEvent->getForm()->getParent(), $formEvent->getForm()->getData());
                $this->addThemeField($formEvent->getForm()->getParent(), $formEvent->getForm()->getData());
            });
        }
    }


    /**
     * Add supervisor field depending organization.
     *
     * @param Organization  $organization
     */
    protected function addSupervisorField(FormInterface $form, $organization)
    {
        if ($organization) {
            $form->add('supervisor', EntityType::class, ['class'         => Supervisor::class, 'label'         => 'Responsable pédagogique', 'query_builder' => static fn(EntityRepository $entityRepository): \Doctrine\ORM\QueryBuilder => $entityRepository->createQueryBuilder('s')
                ->where('s.organization = :organization')
                ->setParameter('organization', $organization)
                ->orWhere('s.organization is null')
                ->orderBy('s.name', 'ASC'), 'required' => false]);
        }
    }

    /**
     * Add institution field depending organization.
     *
     * @param Organization  $organization
     */
    protected function addTagField(FormInterface $form, $organization)
    {
        if ($organization) {
            $form->add('tags', EntityType::class, ['label' => 'Tags', 'class' => Tag::class, 'choice_label' => 'name', 'multiple' => true, 'required' => false, 'query_builder' => static fn(EntityRepository $entityRepository): \Doctrine\ORM\QueryBuilder => $entityRepository->createQueryBuilder('t')
                ->where('t.organization = :organization')
                ->setParameter('organization', $organization)
                ->orWhere('t.organization is null')
                ->orderBy('t.name', 'ASC')]);
        }
    }

    /**
     * Add theme field depending organization.
     *
     * @param Organization  $organization
     */
    protected function addThemeField(FormInterface $form, $organization)
    {
        if ($organization) {
            $form->add('theme', EntityType::class, ['class'         => Theme::class, 'label'         => 'Thématique', 'query_builder' => static fn(EntityRepository $entityRepository): \Doctrine\ORM\QueryBuilder => $entityRepository->createQueryBuilder('th')
                ->where('th.organization = :organization')
                ->setParameter('organization', $organization)
                ->orWhere('th.organization is null')
                ->orderBy('th.name', 'ASC'), 'required' => false]);
        }
    }

}

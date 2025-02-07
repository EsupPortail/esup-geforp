<?php

namespace App\Form\Type;

use App\Entity\Core\AbstractOrganization;
use App\Entity\Back\Institution;
use Doctrine\ORM\EntityRepository;
use App\AccessRight\AccessRightRegistry;
use App\Entity\Back\Organization;
use App\Entity\Term\Title;
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
use Symfony\Bundle\SecurityBundle\Security;


/**
 * Class TrainerType.
 */
final class AbstractTrainerType extends AbstractType
{
    /**
     */
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder
            // this field will be removed by a listener after a failed rights check
            ->add('organization', EntityType::class, ['required'      => true, 'class'         => Organization::class, 'label'         => 'Centre', 'query_builder' => static fn(EntityRepository $entityRepository): \Doctrine\ORM\QueryBuilder => $entityRepository->createQueryBuilder('o')->orderBy('o.name', 'ASC')])
            ->add('title', EntityType::class, ['label'    => 'Civilité', 'class'    => Title::class, 'required' => true])
            ->add('firstname', null, ['label' => 'Prénom'])
            ->add('lastname', null, ['label' => 'Nom'])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('phonenumber', null, ['label' => 'Numéro de téléphone'])
            ->add('website', UrlType::class, ['label' => 'Site internet'])
            ->add('addresstype', ChoiceType::class, ['label' => "Type d'adresse", 'choices' => ['0' => 'Adresse personnelle', '1' => 'Adresse professionnelle'], 'required' => false])
            ->add('address', null, ['label' => 'Adresse'])
            ->add('zip', null, ['label' => 'Code postal'])
            ->add('city', null, ['label' => 'Ville'])
            ->add('trainertype', EntityType::class, ['label'    => "Type d'intervenant", 'class'    => \App\Entity\Term\Trainertype::class, 'required' => false])
            ->add('service', null, ['label' => 'Service'])
            ->add('status', null, ['label' => 'Statut'])
            ->add('isarchived', null, ['label' => 'Archivé'])
            ->add('isallowsendmail', null, ['label' => 'Autoriser les courriels'])
            ->add('isorganization', null, ['label' => 'Formateur interne'])
            ->add('ispublic', null, ['label' => 'Publié sur le web'])
            ->add('comments', null, ['label' => 'Observations'])
            ->add('institution', EntityType::class, ['required'      => true, 'class'         => Institution::class, 'label'         => 'Etablissement', 'query_builder' => static fn(EntityRepository $entityRepository): \Doctrine\ORM\QueryBuilder => $entityRepository->createQueryBuilder('i')->orderBy('i.name', 'ASC')]);


            $user            = $this->security->getUser();
            $formBuilder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $formEvent) use ($user) : void {
                $trainer = $formEvent->getData();
                $trainer->setOrganization($user->getOrganization());
                $formEvent->getForm()->remove('organization');
            });
    }


    public function configureOptions(OptionsResolver $optionsResolver): void
	{
		$optionsResolver->setDefaults(['data_class' => AbstractTrainer::class]);
	}
}

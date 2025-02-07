<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use App\Entity\Core\AbstractTraining;
use App\Entity\Core\AbstractOrganization;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AbstractTrainingType.
 */
final class AbstractTrainingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder
            ->add('name', null, ['label' => 'Titre'])
            // this field will be removed by a listener after a failed rights check
            ->add('organization', EntityType::class, ['required' => true, 'class' => AbstractOrganization::class, 'label' => 'Centre', 'query_builder' => static fn(EntityRepository $entityRepository): \Doctrine\ORM\QueryBuilder => $entityRepository->createQueryBuilder('o')->orderBy('o.name', 'ASC')])
            ->add('firstSessionPeriodSemester', ChoiceType::class, ['label' => '1ère session', 'choices' => ['1' => '1er semestre', '2' => '2nd semestre'], 'required' => true])
            ->add('firstSessionPeriodYear', null, ['label' => 'Année', 'required' => true])
            ->add('comments', null, ['label' => 'Commentaires', 'required' => false]);
    }

	public function configureOptions(OptionsResolver $optionsResolver): void
	{
		$optionsResolver->setDefaults(['data_class' => AbstractTraining::class, 'validation_groups' => ['Default', 'training', 'organization']]);
	}
}

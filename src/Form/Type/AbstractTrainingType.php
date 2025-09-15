<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use App\Entity\Core\AbstractTraining;
use App\Entity\Core\AbstractOrganization;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AbstractTrainingType.
 */
final class AbstractTrainingType extends AbstractType
{
    private const array SEMESTER_CHOICES = [
        '1er semestre' => '1',
        '2nd semestre' => '2',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, ['label' => 'Titre'])
            // this field will be removed by a listener after a failed rights check
            ->add('organization', EntityType::class, ['required' => true, 'class' => AbstractOrganization::class, 'label' => 'Centre', 'query_builder' => static fn(EntityRepository $entityRepository): \Doctrine\ORM\QueryBuilder => $entityRepository->createQueryBuilder('o')->orderBy('o.name', 'ASC')])
            ->add('firstSessionPeriodSemester', ChoiceType::class, ['label' => '1ère session', 'choices' => self::SEMESTER_CHOICES, 'required' => true])
            ->add('firstSessionPeriodYear', IntegerType::class, ['label' => 'Année', 'required' => true, 'empty_data' => '0'])
            ->add('comments', null, ['label' => 'Commentaires', 'required' => false]);
    }

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults(['data_class' => AbstractTraining::class, 'validation_groups' => ['Default', 'training', 'organization']]);
	}
}

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
class AbstractTrainingType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, array(
                'label' => 'Titre',
            ))
            // this field will be removed by a listener after a failed rights check
            ->add('organization', EntityType::class, array(
                'required' => true,
                'class' => AbstractOrganization::class,
                'label' => 'Centre',
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('o')->orderBy('o.name', 'ASC');
                },
            ))
            ->add('firstSessionPeriodSemester', ChoiceType::class, array(
                'label' => '1ère session',
                'choices' => array('1' => '1er semestre', '2' => '2nd semestre'),
                'required' => true,
            ))
            ->add('firstSessionPeriodYear', null, array(
                'label' => 'Année',
                'required' => true,
            ))
            ->add('comments', null, array(
                'label' => 'Commentaires',
                'required' => false,
            ));
    }

	/**
	 * @param OptionsResolver $resolver
	 */
	public function configureOptions(OptionsResolver $resolver)
	{
		$resolver->setDefaults(array(
			'data_class' => AbstractTraining::class,
			'validation_groups' => ['Default', 'training', 'organization'],
		));
	}
}

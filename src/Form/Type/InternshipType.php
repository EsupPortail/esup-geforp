<?php

namespace App\Form\Type;

use App\Entity\Core\Term\PublicType;
use App\Form\Type\TrainingType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

/**
 * Class InternshipType.
 */
class InternshipType extends TrainingType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            /*            ->add('publicTypes', EntityType::class, array(
                            'label' => 'Publics prioritaires',
                            'class' => PublicType::class,
                            'choice_label' => 'machineName',
                            'multiple' => true,
                            'required' => false,
                        ))
                        ->add('publicTypesRestrict', EntityType::class, array(
                            'label' => 'Publics cibles',
                            'class' => PublicType::class,
                            'choice_label' => 'machineName',
                            'multiple' => true,
                            'required' => false,
                        ))
*/                        ->add('prerequisites', null, array(
                            'label'    => 'Pré-requis',
                            'required' => false,
                        ))
 /*                       ->add('designatedPublic', CheckboxType::class, array(
                            'label'    => 'Public désigné',
                            'required' => false,
                        ))*/;

        parent::buildForm($builder, $options);
    }
}

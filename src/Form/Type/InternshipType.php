<?php

namespace App\Form\Type;

use App\Entity\Term\Publictype;
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
                                    ->add('publictypes', EntityType::class, array(
                                        'label' => 'Publics prioritaires',
                                        'class' => Publictype::class,
                                        'choice_label' => 'name',
                                        'multiple' => true,
                                        'required' => false,
                                    ))
                                   ->add('publictypesrestrict', EntityType::class, array(
                                       'label' => 'Publics cibles',
                                       'class' => Publictype::class,
                                       'choice_label' => 'name',
                                       'multiple' => true,
                                       'required' => false,
                                   ))
                                   ->add('prerequisites', null, array(
                            'label'    => 'Pré-requis',
                            'required' => false,
                        ))
                       ->add('designatedpublic', CheckboxType::class, array(
                            'label'    => 'Public désigné',
                            'required' => false,
                        ));

        parent::buildForm($builder, $options);
    }
}

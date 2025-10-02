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
final class InternshipType extends TrainingType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder
                                    ->add('publictypes', EntityType::class, ['label' => 'Publics prioritaires', 'class' => Publictype::class, 'choice_label' => 'name', 'multiple' => true, 'required' => false])
                                   ->add('publictypesrestrict', EntityType::class, ['label' => 'Publics cibles', 'class' => Publictype::class, 'choice_label' => 'name', 'multiple' => true, 'required' => false])
                                   ->add('prerequisites', null, ['label'    => 'Pré-requis', 'required' => false])
                       ->add('designatedpublic', CheckboxType::class, ['label'    => 'Public désigné', 'required' => false]);

        parent::buildForm($formBuilder, $options);
    }
}

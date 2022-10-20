<?php

namespace App\Form\Type;

use App\Entity\Term\Domain;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class InstitutionType
 */
class InstitutionType extends BaseInstitutionType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
           ->add('domains', EntityType::class, array(
               'label' => 'Noms de domaines',
               'class' => Domain::class,
               'choice_label' => 'name',
               'multiple' => true,
               'required' => false,
           ));

        parent::buildForm($builder, $options);
    }
}

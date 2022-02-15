<?php

namespace App\Form\Type;

use App\Entity\Core\AbstractOrganization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AbstractOrganizationType.
 */
class AbstractOrganizationType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('name', null, array(
                'label' => 'Nom',
            ))
            ->add('code', null, array(
                'label' => 'Code',
            ))
            ->add('traineeRegistrable', null, array(
                'label' => 'Les stagiaires peuvent s\'y inscrire',
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array('data_class' => AbstractOrganization::class)
        );
    }

}

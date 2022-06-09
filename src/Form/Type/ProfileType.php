<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use App\Form\Type\TraineeType;
use App\Entity\Organization;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Class ProfileType.
 */
class ProfileType extends TraineeType
{/**
 * @param FormBuilderInterface $builder
 * @param array                $options
 */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->remove('status');
        //$builder->remove('service');
        $builder->remove('isPaying');
        $builder->remove('addressType');
        $builder->get('email')->setDisabled(true);
        $builder->get('firstName')->setDisabled(true);
        $builder->get('lastName')->setDisabled(true);
        $builder->get('title')->setDisabled(true);
        $builder->get('title')->setRequired(false);
        $builder->remove('organization');
        $builder->get('institution')->setDisabled(true);
        $builder->get('service')->setDisabled(true);
        $builder->remove('isActive');
        $builder->get('amuStatut')->setDisabled(true);
        $builder->get('corps')->setDisabled(true);
        $builder->get('category')->setDisabled(true);
        $builder->get('bap')->setDisabled(true);
        $builder->get('birthDate')->setDisabled(true);
        $builder->get('campus')->setDisabled(true);

        //$builder
        //    ->add('service', null, array(
        //        'required' => false,
        //       'label'    => 'Service',
        //    ))
        //    ->add('status', null, array(
        //        'required' => false,
        //        'label'    => 'Statut / fonction',
        //    ));
	    $builder->get('publicType')->setDisabled(true);

        $builder->get('lastNameSup')->setDisabled(true);
        $builder->get('firstNameSup')->setDisabled(true);
        $builder->get('emailSup')->setDisabled(true);

    }

    /**
     * Add institution field depending organization.
     *
     * @param FormInterface $form
     * @param Organization  $organization
     */
    protected function addInstitutionField(FormInterface $form, $organization)
    {

    }
}

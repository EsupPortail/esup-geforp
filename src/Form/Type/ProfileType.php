<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use App\Form\Type\TraineeType;
use App\Entity\Organization;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use App\Entity\Core\Term\Publictype;

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
        $builder->remove('isPaying');
        $builder->remove('addresstype');
        $builder->get('email')->setDisabled(true);
        $builder->get('firstname')->setDisabled(true);
        $builder->get('lastname')->setDisabled(true);
        $builder->get('title')->setDisabled(true);
        $builder->get('title')->setRequired(false);
        $builder->remove('organization');
        $builder->get('institution')->setDisabled(true);
        $builder->get('service')->setDisabled(true);
        $builder->remove('isActive');
        $builder->get('amustatut')->setDisabled(true);
        $builder->get('corps')->setDisabled(true);
        $builder->get('category')->setDisabled(true);
        $builder->get('bap')->setDisabled(true);
        $builder->get('birthdate')->setDisabled(true);
        $builder->get('campus')->setDisabled(true);
	    $builder->get('publictype')->setDisabled(true);
        $builder->get('lastnamesup')->setDisabled(true);
        $builder->get('firstnamesup')->setDisabled(true);
        $builder->get('emailsup')->setDisabled(true);

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

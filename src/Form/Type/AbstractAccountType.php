<?php

namespace App\Form\Type;

use App\Entity\Core\Term\Title;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;

abstract class AbstractAccountType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', null, array(
                'label' => 'Civilité',
            ))
            ->add('lastname', null, array(
                'label' => 'Nom',
            ))
            ->add('firstname', null, array(
                'label' => 'Prénom',
            ))

            ->add('email', EmailType::class, array(
                'label' => 'Email',
            ))
            ->add('phonenumber', null, array(
                'label'    => 'Numéro de téléphone',
                'required' => false,
            ))

            ->add('addresstype', ChoiceType::class, array(
                'required' => true,
                'choices' => array(
                    '0' => 'Adresse personnelle',
                    '1' => 'Adresse professionnelle'
                ),
                'label' => 'Type d\'adresse'
            ))
            ->add('address', null, array(
                'label'    => 'Adresse professionnelle',
                'required' => false,
            ))
            ->add('zip', null, array(
                'label'    => 'Code postal',
                'required' => false,
            ))
            ->add('city', null, array(
                'label'    => 'Ville',
                'required' => false,
            ));
    }
}

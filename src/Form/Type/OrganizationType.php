<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use App\Entity\Core\AbstractInstitution;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class OrganizationType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('name', TextType::class, array(
                'label' => 'Nom',
            ))
            ->add('code', TextType::class, array(
                'label' => 'Code',
            ))
            ->add('email', EmailType::class, array(
                'label' => 'Email',
            ))
            ->add('phoneNumber', TextType::class, array(
                'label'    => 'Téléphone',
                'required' => false,
            ))
            ->add('faxNumber', TextType::class, array(
                'label'    => 'Numéro de fax',
                'required' => false,
            ))
            ->add('address', TextareaType::class, array(
                'label'    => 'Adresse',
                'required' => false,
            ))
            ->add('zip', TextType::class, array(
                'label'    => 'Code postal',
                'required' => false,
            ))
            ->add('city', TextType::class, array(
                'label'    => 'Ville',
                'required' => false,
            ))
            ->add('website', TextType::class, array(
                'label'    => 'Site internet',
                'required' => false,
            ))
            ->add('traineeRegistrable', CheckboxType::class, array(
                'label'    => 'Les stagiaires peuvent choisir cette organisation',
                'required' => false,
            ));

        // PRE_SET_DATA for the parent form
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
              $builder = $event->getForm();
              $organization = $event->getData();

              $builder->add('institution', EntityType::class, array(
                  'label'         => 'Etablissement de rattachement',
                  'class'         => AbstractInstitution::class,
                  'required'      => false,
                  'query_builder' => $organization->getId() ? function (EntityRepository $er) use ($organization) {
                      return $er->createQueryBuilder('i')
                        ->where('i.organization = :organization')
                        ->setParameter('organization', $organization)
                        ->orWhere('i.organization is null')
                        ->orderBy('i.name');
                  } : null,
                ));
          });
    }
}

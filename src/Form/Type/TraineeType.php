<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/9/16
 * Time: 4:35 PM
 */

namespace App\Form\Type;


use Doctrine\ORM\EntityRepository;
use App\Entity\Trainee;
use App\Form\Type\BaseTraineeType;
use App\Entity\Core\Term\Publictype;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TraineeType extends BaseTraineeType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

       $builder
           ->add('birthdate', null, array(
               'required' => false,
               'label'    => 'Date de naissance (format aaaammjj)',
           ))
           ->add('amustatut', null, array(
               'required' => false,
               'label'    => 'Statut',
           ))
           ->add('bap', null, array(
               'required' => false,
               'label'    => 'BAP',
           ))
           ->add('corps', null, array(
               'required' => false,
               'label'    => 'Corps',
           ))
           ->add('category', null, array(
               'required' => false,
               'label'    => 'Catégorie',
           ))
           ->add('campus', null, array(
               'required' => false,
               'label'    => 'Campus',
           ))
           ->add('lastnamesup', null, array(
               'required' => false,
               'label'    => 'Nom',
           ))
           ->add('firstnamesup', null, array(
               'required' => false,
               'label'    => 'Prénom',
           ))
           ->add('emailsup', null, array(
               'required' => false,
               'label'    => 'Email',
               'attr' => array('placeholder' => 'Entrez le mail INSTITUTIONNEL de votre responsable hiérarchique')
           ))
           ->add('lastnamecorr', null, array(
               'required' => false,
               'label'    => 'Nom',
           ))
           ->add('firstnamecorr', null, array(
               'required' => false,
               'label'    => 'Prénom',
           ))
           ->add('emailcorr', null, array(
               'required' => false,
               'label'    => 'Email',
           ))
           ->add('fonction', null, array(
               'required' => true,
               'label'    => 'Fonction exercée',
           ))
           ->remove('publictype')
           ->add('publictype', 'entity', array(
               'label'    => 'Type de personnel',
               'class'    => Publictype::class,
               'required' => false,
           ))
           ->add('publictype', 'entity', array(
               'label'    => 'Type de personnel',
               'class'    => Publictype::class,
               'choice_label' => 'machine_name',
               'query_builder' => function (EntityRepository $er) {
                   return $er->createQueryBuilder('o')->orderBy('o.name', 'ASC');
               },
           ))
        ;
    }

    /**
     * Add all listeners to manage conditional fields.
     */
    protected function addEventListeners(FormBuilderInterface $builder)
    {
        // PRE_SET_DATA for the parent form
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $this->addInstitutionField($event->getForm(), $event->getData()->getOrganization());
            $user = $event->getData();//recuperation de l'objet sur lequel le formulaire se base
            // Si le stagaire est prÃ©-rempli
            if ($user->getLastName()!=null) {
                if (($user->getPublictype() != null) && ($user->getPublictype()->getId() == 1)) { // Cas des biatss (employee) -> responsable hiÃ©rarchique obligatoire
                    $event->getForm()
                        ->add('lastnamesup', null, array(
                            'required' => true,
                            'label' => 'Nom',
                        ))
                        ->add('firstnamesup', null, array(
                            'required' => true,
                            'label' => 'Prénom',
                        ))
                        ->add('emailsup', null, array(
                            'required' => true,
                            'label' => 'Email',
                            'attr' => array('placeholder' => 'Entrez le mail INSTITUTIONNEL de votre responsable hiérarchique')
                        ));
                } else { // Autres cas : saisie du responsable non obligatoire
                    $event->getForm()
                        ->add('lastnamesup', null, array(
                            'required' => false,
                            'label' => 'Nom',
                        ))
                        ->add('firstnamesup', null, array(
                            'required' => false,
                            'label' => 'Prénom',
                        ))
                        ->add('emailsup', null, array(
                            'required' => false,
                            'label' => 'Email',
                            'attr' => array('placeholder' => 'Entrez le mail INSTITUTIONNEL de votre responsable hiérarchique')
                        ));
                }
            }

        });

        // POST_SUBMIT for each field
        if ($builder->has('organization')) {
            $builder->get('organization')->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $this->addInstitutionField($event->getForm()->getParent(), $event->getForm()->getData());
            });
        }

    }


    /**
     * @param $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'            => Trainee::class,
            'validation_groups'     => array('Default', 'trainee'),
            'enable_security_check' => true,
        ));
    }
}
<?php

namespace App\Form\Type;


use App\AccessRight\AccessRightRegistry;
use App\Entity\Back\Inscription;
use App\Entity\Back\Institution;
use App\Entity\Term\ActionType;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Core\AbstractSession;
use App\Entity\Term\Theme;
use App\Entity\Back\Organization;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Security\Core\Security;

class TraineeSearchType extends AbstractType
{
    private $security;

    public function __construct(Security $security )
    {
        $this->security = $security;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('institution', EntityType::class, array(
                'label' => 'Etablissement',
                'choice_label' => 'name',
                'class' => Institution::class
            ))
            ->add('nom', null, array(
                'label' => 'Recherche par nom',
                'required' => false,
                'attr' => array('placeholder' => 'Tapez un nom')
            ));

        // add listeners to handle conditionals fields
        $this->addEventListeners($builder);
    }

    /**
     * Add all listeners to manage conditional fields.
     */
    protected function addEventListeners(FormBuilderInterface $builder)
    {
        // PRE_SET_DATA for the parent form
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $userAccessRights = $this->security->getUser()->getAccessRights();

            if (in_array("sygefor_core.rights.user.all", $userAccessRights)) {
            } else {
                // si l'utilisateur n'a que les droits sur son centre
                if (in_array("sygefor_core.rights.user.own", $userAccessRights)) {
                    // Pas de choix possible pour l'établissement
                    $event->getForm()
                        ->add('institution', EntityType::class, array(
                            'label' => 'Etablissement',
                            'choice_label' => 'name',
                            'disabled' => true,
                            'class' => Institution::class
                        ));
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => null,
            'id' => 'traineesearch'
        ));
    }

    public function getName()
    {
        return 'traineesearch';
    }
}
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
use Symfony\Bundle\SecurityBundle\Security;

final class TraineeSearchType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder
            ->add('institution', EntityType::class, ['label' => 'Etablissement', 'choice_label' => 'name', 'class' => Institution::class])
            ->add('nom', null, ['label' => 'Recherche par nom', 'required' => false, 'attr' => ['placeholder' => 'Tapez un nom']]);

        // add listeners to handle conditionals fields
        $this->addEventListeners($formBuilder);
    }

    /**
     * Add all listeners to manage conditional fields.
     */
    private function addEventListeners(FormBuilderInterface $formBuilder): void
    {
        // PRE_SET_DATA for the parent form
        $formBuilder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $formEvent): void {
            $userAccessRights = $this->security->getUser()->getAccessRights();

            if (in_array("sygefor_core.rights.user.all", $userAccessRights)) {
            } elseif (in_array("sygefor_core.rights.user.own", $userAccessRights)) {
                // si l'utilisateur n'a que les droits sur son centre
                // Pas de choix possible pour l'établissement
                $formEvent->getForm()
                    ->add('institution', EntityType::class, ['label' => 'Etablissement', 'choice_label' => 'name', 'disabled' => true, 'class' => Institution::class]);
            }
        });
    }

    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['data_class' => null, 'id' => 'traineesearch']);
    }

    public function getName(): string
    {
        return 'traineesearch';
    }
}
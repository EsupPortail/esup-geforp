<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 19/03/14
 * Time: 15:18.
 */

namespace App\Form\Type;

use App\AccessRight\AccessRightRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AccessRightType.
 */
final class AccessRightType extends AbstractType
{
    public function __construct(private readonly AccessRightRegistry $accessRightRegistry)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->preSubmit(...));
    }

    /**
     * This PRESUBMIT listener check if unauthorized right has been changed.
     *
     */
    public function preSubmit(FormEvent $formEvent): void
    {
        $form = $formEvent->getForm();
        $rights = $formEvent->getData();

        // $form->getData() return an array with index reseted
        // we need to set the right key for each initial right
        $initialRights = [];
        $choices = $form->getConfig()->getOption('choices');
        // Transformer le tableau
        $newChoices = [];
        foreach ($choices as $choice) {
                $newChoices = array_merge($newChoices, $choice);
        }

        foreach ($form->getData() as $right) {
            $key = array_search($right, $newChoices, true);
            $initialRights[$key] = $right;
        }

        // foreach initial rights,
        foreach ($initialRights as $key => $right) {
            // if unauthorized, force it the the submitted value
            if (!$this->accessRightRegistry->hasAccessRight($right)) {
                $rights[$key] =  $right;
            }
        }

        // foreach submitted right
        foreach ($rights as $key => $right) {
            // if unauthorized & not in initial rights, remove it
            if ($this->accessRightRegistry->hasAccessRight($right)) {
                continue;
            }
            if (in_array($right, $initialRights, true)) {
                continue;
            }
            unset($rights[$key]);
        }

        // set the reworked rights
        $formEvent->setData($rights);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $choices = [];
        $rightsGroups = $this->accessRightRegistry->getGroups();

        //building choices list on the form of a double dimension array : category -> rights
        foreach ($rightsGroups as $cat => $rightsIds) {
            $choices[$cat] = [];
            foreach ($rightsIds as $rightId) {
//                $choices[$cat][$rightId] = $this->accessRightsRegistry->getAccessRightById($rightId)->getLabel();
                $choices[$cat][$this->accessRightRegistry->getAccessRightById($rightId)->getLabel()] = $rightId;
            }
        }

        $resolver->setDefaults(['expanded' => true, 'multiple' => true, 'choices' => $choices]);
    }

    /**
     * Disabled all unauthorized rights.
     *
     */
    public function finishView(FormView $formView, FormInterface $form, array $options): void
    {
        foreach ($formView->children as $item) {
            $value = $item->vars['value'];
            if (!$this->accessRightRegistry->hasAccessRight($value)) {
                $item->vars['attr']['disabled'] = 'disabled';
                $item->vars['attr']['title'] = "Vous ne pouvez pas modifier ce droit d'accès.";
            }
        }
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName(): string
    {
        return 'access_rights';
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}

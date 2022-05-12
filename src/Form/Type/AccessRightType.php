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
class AccessRightType extends AbstractType
{
    /**
     * @var AccessRightRegistry
     */
    private $accessRightsRegistry;

    /**
     * @param AccessRightRegistry $registry
     */
    public function __construct(AccessRightRegistry $registry)
    {
        $this->accessRightsRegistry = $registry;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, array($this, 'preSubmit'));
    }

    /**
     * This PRESUBMIT listener check if unauthorized right has been changed.
     *
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $rights = $event->getData();

        // $form->getData() return an array with index reseted
        // we need to set the right key for each initial right
        $initialRights = array();
        $choices = $form->getConfig()->getOption('choices');
        // Transformer le tableau
        $newChoices = array();
        foreach ($choices as $choice) {
                $newChoices = array_merge($newChoices, $choice);
        }
        foreach ($form->getData() as $right) {
            $key = array_search($right, $newChoices);
            $initialRights[$key] = $right;
        }

        // foreach initial rights,
        foreach ($initialRights as $key => $right) {
            // if unauthorized, force it the the submitted value
            if (!$this->accessRightsRegistry->hasAccessRight($right)) {
                $rights[$key] =  $right;
            }
        }

        // foreach submitted right
        foreach ($rights as $key => $right) {
            // if unauthorized & not in initial rights, remove it
            if (!$this->accessRightsRegistry->hasAccessRight($right)) {
                if (!in_array($right, $initialRights, true)) {
                    unset($rights[$key]);
                }
            }
        }

        // set the reworked rights
        $event->setData($rights);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $choices = array();
        $rightsGroups = $this->accessRightsRegistry->getGroups();

        //building choices list on the form of a double dimension array : category -> rights
        foreach ($rightsGroups as $cat => $rightsIds) {
            $choices[$cat] = array();
            foreach ($rightsIds as $rightId) {
//                $choices[$cat][$rightId] = $this->accessRightsRegistry->getAccessRightById($rightId)->getLabel();
                $choices[$cat][$this->accessRightsRegistry->getAccessRightById($rightId)->getLabel()] = $rightId;
            }
        }

        $resolver->setDefaults(array(
            'expanded' => true,
            'multiple' => true,
            'choices' => $choices,
        ));
    }

    /**
     * Disabled all unauthorized rights.
     *
     * @param FormView      $view
     * @param FormInterface $form
     * @param array         $options
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        foreach ($view->children as $key => $item) {
            $value = $item->vars['value'];
            if (!$this->accessRightsRegistry->hasAccessRight($value)) {
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
    public function getName()
    {
        return 'access_rights';
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}

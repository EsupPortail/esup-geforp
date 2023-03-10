<?php

namespace App\Form\Type;

use App\Entity\Term\Inscriptionstatus;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class InscriptionStatusVocabularyType.
 */
class InscriptionStatusVocabularyType extends VocabularyType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('notify', CheckboxType::class, array('label' => "Pour les gestionnaires : notification de changement de statut", 'required' => false));
        $builder->add('status', ChoiceType::class, array(
            'label' => 'Statut élémentaire',
            'expanded' => true,
            'multiple' => false,
            'required' => true,
            'choices' => array(
                'Accepté' => Inscriptionstatus::STATUS_ACCEPTED,
                'En attente' => Inscriptionstatus::STATUS_WAITING,
                'En attente de traitement' => Inscriptionstatus::STATUS_PENDING,
                'Rejeté' => Inscriptionstatus::STATUS_REJECTED,
            ),
        ));
        $builder->add('machinename', null, array(
            'label' => 'Libellé court',
        ));
    }

    /**
     * @return string
     */
    public function getParent()
    {
        return VocabularyType::class;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => Inscriptionstatus::class,
        ));
    }
}

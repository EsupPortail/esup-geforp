<?php

/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 15/04/14
 * Time: 14:30.
 */

namespace App\Form\Type;

use App\Entity\Core\AbstractCareer;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTraining;
use App\Entity\Term\Sessiontype as Type;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class AbstractSessionType.
 */
class AbstractCareerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, ['label' => 'Nom du parcours', 'required' => true])
            ->add('description', null, ['label' => 'Description', 'required' => false])
            ->add('resources', null, ['label' => 'Ressources', 'required' => false])
            ->add('comments', null, ['required' => false, 'label' => 'Commentaires']) ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AbstractCareer::class]
        );
    }


}

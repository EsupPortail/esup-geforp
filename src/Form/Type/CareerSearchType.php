<?php

namespace App\Form\Type;


use App\AccessRight\AccessRightRegistry;
use App\Entity\Back\Career;
use App\Entity\Back\Inscription;
use App\Entity\Back\Institution;
use App\Entity\Core\AbstractCareer;
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
use Symfony\Component\Validator\Constraints\NotBlank;

final class CareerSearchType extends AbstractType
{
    public function __construct(private readonly Security $security)
    {
    }

    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder
            ->add('career', EntityHiddenType::class, ['label' => 'Nom de parcours', 'class' => AbstractCareer::class]);
    }


    public function configureOptions(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(['id' => 'careersearch']);
    }

    public function getName(): string
    {
        return 'careersearch';
    }
}
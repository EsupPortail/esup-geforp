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

final class OrganizationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        parent::buildForm($formBuilder, $options);

        $formBuilder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('code', TextType::class, ['label' => 'Code'])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('phoneNumber', TextType::class, ['label'    => 'Téléphone', 'required' => false])
            ->add('faxNumber', TextType::class, ['label'    => 'Numéro de fax', 'required' => false])
            ->add('address', TextareaType::class, ['label'    => 'Adresse', 'required' => false])
            ->add('zip', TextType::class, ['label'    => 'Code postal', 'required' => false])
            ->add('city', TextType::class, ['label'    => 'Ville', 'required' => false])
            ->add('website', TextType::class, ['label'    => 'Site internet', 'required' => false])
            ->add('traineeRegistrable', CheckboxType::class, ['label'    => 'Les stagiaires peuvent choisir cette organisation', 'required' => false])
            ->add('institution', EntityType::class, ['label'         => 'Etablissement de rattachement', 'class'         => AbstractInstitution::class, 'required'      => true]);

    }
}

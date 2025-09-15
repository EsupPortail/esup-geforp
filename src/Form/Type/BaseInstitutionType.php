<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use App\Entity\Back\Organization;
use App\AccessRight\AccessRightRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Class BaseInstitutionType.
 */
class BaseInstitutionType extends AbstractType
{
    public function __construct()
    {
    }

    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $formBuilder
            ->add('name', TextType::class, ['label' => 'Nom'])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('address', TextareaType::class, ['label' => 'Adresse', 'required' => false])
            ->add('zip', TextType::class, ['label' => 'Code postal', 'required' => false])
            ->add('city', TextType::class, ['label' => 'Ville', 'required' => false])
            ->add('website', TextType::class, ['label' => 'Lien', 'required' => false]);

    }
}

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
use Symfony\Component\Security\Core\Security;

/**
 * Class BaseInstitutionType.
 */
class BaseInstitutionType extends AbstractType
{
    /**
     * @var AccessRightRegistry
     */
    private $accessRightsRegistry;

    /**
     * @var Security
     */
    private $security;

    /**
     * @param AccessRightRegistry $registry
     */
    public function __construct(AccessRightRegistry $registry, Security $security)
    {
        $this->accessRightsRegistry = $registry;
        $this->security = $security;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, array(
                'label' => 'Nom',
            ))
            ->add('email', EmailType::class, array(
                'label' => 'Email',
            ))
            ->add('address', TextareaType::class, array(
                'label' => 'Adresse',
                'required' => false,
            ))
            ->add('zip', TextType::class, array(
                'label' => 'Code postal',
                'required' => false,
            ))
            ->add('city', TextType::class, array(
                'label' => 'Ville',
                'required' => false,
            ));

    }
}

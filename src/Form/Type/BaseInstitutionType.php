<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use App\Entity\Back\Organization;
use App\AccessRight\AccessRightRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
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
            ->add('organization', EntityType::class, array(
                'label'         => 'Centre',
                'class'         => Organization::class,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('o')->orderBy('o.name', 'ASC');
                },
                'required' => true,
            ))
            ->add('name', TextType::class, array(
                'label' => 'Nom',
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

        // If the user does not have the rights, remove the organization field and force the value
/*        $hasAccessRightForAll = $this->accessRightsRegistry->hasAccessRight('sygefor_training.rights.institution.all.create');
        if (!$hasAccessRightForAll) {
            $securityContext = $this->accessRightsRegistry->getSecurityContext();
            $user            = $securityContext->getToken()->getUser();*/
            $user            = $this->security->getUser();
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($user) {
                $institution = $event->getData();
                if ($institution) {
                    $institution->setOrganization($user->getOrganization());
                    $event->getForm()->remove('organization');
                }
            });
//        }
    }
}

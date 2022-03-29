<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use App\Entity\Core\AbstractOrganization;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ChangeOrganizationType.
 */
class ChangeOrganizationType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // we cant add event listener in listener, so we have to build the organization field now
        $entity = $builder->getData();

        $builder
            ->add('organization', EntityType::class, array(
                'label' => 'Nouveau centre',
                'class' => AbstractOrganization::class,
                'query_builder' => function (EntityRepository $er) use ($entity) {
                    return $er->createQueryBuilder('o')
                        ->where('o != :organization')
                        ->setParameter('organization', $entity->getOrganization())
                        ->orderBy('o.name', 'ASC');
                },
                'required' => true,
            ));
    }
}

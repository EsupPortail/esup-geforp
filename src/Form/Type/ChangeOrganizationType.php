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
final class ChangeOrganizationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        // we cant add event listener in listener, so we have to build the organization field now
        $entity = $formBuilder->getData();

        $formBuilder
            ->add('organization', EntityType::class, ['label' => 'Nouveau centre', 'class' => AbstractOrganization::class, 'query_builder' => static fn(EntityRepository $entityRepository): \Doctrine\ORM\QueryBuilder => $entityRepository->createQueryBuilder('o')
                ->where('o != :organization')
                ->setParameter('organization', $entity->getOrganization())
                ->orderBy('o.name', 'ASC'), 'required' => true]);
    }
}

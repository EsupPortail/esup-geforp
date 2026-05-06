<?php

namespace App\Form\Type;

use App\Entity\Term\Domain;
use App\Entity\Core\AbstractInstitution;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;

/**
 * Class InstitutionType
 */
final class InstitutionType extends BaseInstitutionType
{
    public function buildForm(FormBuilderInterface $formBuilder, array $options): void
    {
        $object = $formBuilder->getData();
        $formBuilder
            ->add('idp', TextType::class, ['label' => 'URL IDP', 'required' => false])
            ->add('domains', EntityType::class, ['label' => 'Noms de domaines', 'class' => Domain::class, 'choice_label' => 'name', 'multiple' => true, 'required' => false])
            ->add('visuinstitutions', EntityType::class, ['label' => 'Autres établissements visibles', 'class' => AbstractInstitution::class, 'choice_label' => 'name', 'multiple' => true, 'required' => false, 'query_builder' => static fn(EntityRepository $entityRepository): \Doctrine\ORM\QueryBuilder => $entityRepository->createQueryBuilder('i')
                ->where('i != :institution')
                ->setParameter('institution', $object)
                ->orderBy('i.name', 'ASC')])
            ->add('siret', TextType::class, ['label' => 'SIRET', 'required' => false])
            ->add('rs', TextType::class, ['label' => 'Raison sociale', 'required' => false]);



        parent::buildForm($formBuilder, $options);
    }
}

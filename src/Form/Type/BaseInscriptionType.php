<?php

namespace App\Form\Type;

use App\Entity\Core\AbstractOrganization;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Term\Inscriptionstatus;
use App\Entity\Term\Presencestatus;
use Doctrine\ORM\EntityRepository;
use App\Form\Type\EntityHiddenType;
use App\Entity\Core\AbstractInscription;
use App\Entity\Core\AbstractSession;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class BaseInscriptionType.
 */
final class BaseInscriptionType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $options['data']->getSession();

        /** @var AbstractOrganization $organization */
        $organization = $options['attr']['organization'];

        $builder
            ->add('trainee', EntityHiddenType::class, ['label'           => 'Stagiaire', 'class'           => AbstractTrainee::class, 'invalid_message' => ''])
            ->add('session', EntityHiddenType::class, ['label'           => 'Session', 'class'           => AbstractSession::class, 'invalid_message' => 'Session non reconnue'])
            ->add('inscriptionstatus', EntityType::class, ['label'         => "Status d'inscription", 'class'         => Inscriptionstatus::class, 'query_builder' => static function (EntityRepository $entityRepository) use ($organization) : \Doctrine\ORM\QueryBuilder {
                $queryBuilder = $entityRepository->createQueryBuilder('i');
                $queryBuilder->where('i.organization = :organization')
                    ->setParameter('organization', $organization)
                    ->orWhere('i.organization is null');
                return $queryBuilder;
            }])
            ->add('presencestatus', EntityType::class, ['label'         => 'Status de présence', 'class'         => Presencestatus::class, 'query_builder' => static function (EntityRepository $entityRepository) use ($organization) : \Doctrine\ORM\QueryBuilder {
                $queryBuilder = $entityRepository->createQueryBuilder('i');
                $queryBuilder->where('i.organization = :organization')
                    ->setParameter('organization', $organization)
                    ->orWhere('i.organization is null');
                return $queryBuilder;
            }]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AbstractInscription::class]);
    }
}

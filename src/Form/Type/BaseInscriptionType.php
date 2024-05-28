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
class BaseInscriptionType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var AbstractSession $session */
        $session = $options['data']->getSession();

        /** @var AbstractOrganization $organization */
        $organization = $options['attr']['organization'];

        $builder
            ->add('trainee', EntityHiddenType::class, array(
                'label'           => 'Stagiaire',
                'class'           => AbstractTrainee::class,
                'invalid_message' => '',
            ))
            ->add('session', EntityHiddenType::class, array(
                'label'           => 'Session',
                'class'           => AbstractSession::class,
                'invalid_message' => 'Session non reconnue',
            ))
            ->add('inscriptionstatus', EntityType::class, array(
                'label'         => 'Status d\'inscription',
                'class'         => Inscriptionstatus::class,
                'query_builder' => function (EntityRepository $repository) use ($organization) {
                    $qb = $repository->createQueryBuilder('i');
                    $qb->where('i.organization = :organization')
                        ->setParameter('organization', $organization)
                        ->orWhere('i.organization is null');

                    return $qb;
                },
            ))
            ->add('presencestatus', EntityType::class, array(
                'label'         => 'Status de présence',
                'class'         => Presencestatus::class,
                'query_builder' => function (EntityRepository $repository) use ($organization) {
                    $qb = $repository->createQueryBuilder('i');
                    $qb->where('i.organization = :organization')
                        ->setParameter('organization', $organization)
                        ->orWhere('i.organization is null');

                    return $qb;
                },
            ));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => AbstractInscription::class,
        ));
    }
}

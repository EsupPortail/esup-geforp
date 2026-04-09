<?php

namespace App\Repository;

use App\Entity\Term\Theme;
use App\Entity\Back\Internship;
use App\Entity\Back\Organization;
use App\Entity\Back\Session;
use App\Entity\Back\Trainer;
use App\Entity\Core\AbstractParticipation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, AbstractParticipation::class);
    }

    public function getParticipationsList(?string $keyword, array $filters)
    {
        $qb = $this->createQueryBuilder('p')
            ->select('p', 'trainer', 's', 'o')
            ->innerJoin('p.trainer', 'trainer')
            ->innerJoin('p.session', 's')
            ->innerJoin('p.organization', 'o');

        if (!empty($keyword)) {
            $qb
                ->andWhere('(trainer.firstname LIKE :keyword OR trainer.lastname LIKE :keyword)')
                ->setParameter('keyword', '%' . addcslashes((string) $keyword, '%_') . '%');
        }


        // FILTRE CENTRE
        if (isset($filters['training.organization.name.source'])) {
            $qb
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $filters['training.organization.name.source']);
        }

        // FILTRE FORMATEUR
        if( isset($filters['trainer.id']) ) {
            $qb
                ->andWhere('trainer = :id')
                ->setParameter('id', $filters['trainer.id']);
        }

        // TRI DES RESULTATS
        $qb->addOrderBy('s.datebegin', 'DESC');

        return $qb->getQuery()->getResult();
    }



}

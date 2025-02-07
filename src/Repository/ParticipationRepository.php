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

    public function getParticipationsList($keyword, $filters)
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $queryBuilder
            ->select('p')
            ->innerJoin('p.trainer', 'trainer', 'WITH', 'trainer = p.trainer')
            ->innerJoin('p.session', 's', 'WITH', 's = p.session')
            ->innerJoin('p.organization', 'o', 'WITH', 'o = p.organization')

            // FILTRE KEYWORD
            ->where('trainer.firstname LIKE :keyword')
            ->orWhere('trainer.lastname LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes((string) $keyword, '%_') . '%');


        // FILTRE CENTRE
        if (isset($filters['training.organization.name.source'])) {
            $queryBuilder
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $filters['training.organization.name.source']);
        }

        // FILTRE FORMATEUR
        if( isset($filters['trainer.id']) ) {
            $queryBuilder
                ->andWhere('trainer = :id')
                ->setParameter('id', $filters['trainer.id']);
        }



        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }



}

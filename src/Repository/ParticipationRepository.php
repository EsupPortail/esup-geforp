<?php

namespace App\Repository;

use App\Entity\Term\Theme;
use App\Entity\Back\Internship;
use App\Entity\Back\Organization;
use App\Entity\Back\Session;
use App\Entity\Back\Trainer;
use App\Entity\Back\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    public function getParticipationsList($keyword, $filters)
    {
        $qb = $this->createQueryBuilder('p');
        $qb
            ->select('p')
            ->innerJoin('p.trainer', 'trainer', 'WITH', 'trainer = p.trainer')
            ->innerJoin('p.session', 's', 'WITH', 's = p.session')
            ->innerJoin('p.organization', 'o', 'WITH', 'o = p.organization')

            // FILTRE KEYWORD
            ->where('trainer.firstname LIKE :keyword')
            ->orWhere('trainer.lastname LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');


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



        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }



}

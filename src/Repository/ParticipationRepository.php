<?php

namespace App\Repository;

use App\Entity\Core\Term\Theme;
use App\Entity\Internship;
use App\Entity\Organization;
use App\Entity\Session;
use App\Entity\Trainer;
use App\Entity\Participation;
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
            /* Keyword (recherche par mot clé) */
            ->innerJoin(Organization::class, 'o')
            ->innerJoin(Trainer::class, 'trainer')
            ->innerJoin(Session::class, 's')

            // FILTRE KEYWORD
            ->where('trainer.firstname LIKE :keyword')
            ->orWhere('trainer.lastname LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');


        // FILTRE CENTRE
        if (isset($filters['training.organization.name.source'])) {
            $qb
                ->andWhere('internship.organization = o.id')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $filters['training.organization.name.source']);
        }

        // FILTRE FORMATEUR
        if( isset($filters['trainer.id']) ) {
            $qb
                ->andWhere('p.trainer = :id')
                ->setParameter('id', $filters['trainer.id']);
        }



        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }



}

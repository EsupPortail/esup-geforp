<?php

namespace App\Repository;

use App\Entity\Core\Email;
use App\Entity\Session;
use App\Entity\Trainer;
use App\Entity\Trainee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Email::class);
    }

    public function getEmailsList($keyword, $filters)
    {
        $qb = $this->createQueryBuilder('e');
        $qb
            ->select('e')
            /* Keyword (recherche par mot clé) */
            ->innerJoin(Trainer::class, 'trainer')
            ->innerJoin(Trainee::class, 'trainee')
            ->innerJoin(Session::class, 's')

            // FILTRE KEYWORD
            ->where('e.subject LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');


        // FILTRE TRAINEE
        if (isset($filters['trainee.id'])) {
            $qb
                ->andWhere('e.trainee = :id')
                ->setParameter('id', $filters['trainee.id']);
        }

        // FILTRE FORMATEUR
        if( isset($filters['trainer.id']) ) {
            $qb
                ->andWhere('e.trainer = :id')
                ->setParameter('id', $filters['trainer.id']);
        }

        // FILTRE SESSION
        if( isset($filters['session.id']) ) {
            $qb
                ->andWhere('e.session = :id')
                ->setParameter('id', $filters['session.id']);
        }



        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }



}

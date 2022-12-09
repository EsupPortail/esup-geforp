<?php

namespace App\Repository;

use App\Entity\Core\Email;
use App\Entity\Back\Session;
use App\Entity\Back\Trainer;
use App\Entity\back\Trainee;
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

        $qb->addOrderBy('e.sendat', 'DESC');

        $query = $qb->getQuery();
        $result = $query->getResult();

        return $result;
    }



}

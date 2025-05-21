<?php

namespace App\Repository;

use App\Entity\Core\Email;
use App\Entity\Back\Session;
use App\Entity\Back\Trainer;
use App\Entity\back\Trainee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class EmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Email::class);
    }

    public function getEmailsList($keyword, $filters, int $limit = 100)
    {
        $queryBuilder = $this->createQueryBuilder('e');
        $queryBuilder
            ->select('e')
            /* Keyword (recherche par mot clé) */
            // FILTRE KEYWORD
            ->where('e.subject LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes((string) $keyword, '%_') . '%')

            ->setMaxResults($limit);


        // FILTRE TRAINEE
        if (isset($filters['trainee.id'])) {
            $queryBuilder
                ->andWhere('e.trainee = :id')
                ->setParameter('id', $filters['trainee.id']);
        }

        // FILTRE FORMATEUR
        if( isset($filters['trainer.id']) ) {
            $queryBuilder
                ->andWhere('e.trainer = :id')
                ->setParameter('id', $filters['trainer.id']);
        }

        // FILTRE SESSION
        if( isset($filters['session.id']) ) {
            $queryBuilder
                ->andWhere('e.session = :id')
                ->setParameter('id', $filters['session.id']);
        }

        $queryBuilder->addOrderBy('e.sendat', 'DESC');

        $query = $queryBuilder->getQuery();

        return $query->getResult();
    }



}

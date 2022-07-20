<?php

namespace App\Repository;

use App\Entity\Core\Term\Theme;
use App\Entity\Institution;
use App\Entity\Internship;
use App\Entity\Organization;
use App\Entity\Session;
use App\Entity\Trainer;
use App\Entity\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class TrainerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trainer::class);
    }

    public function getTrainersList($keyword, $filters, $page, $pageSize)
    {
        $qb = $this->createQueryBuilder('trainer');
        $qb
            ->select('trainer')

            // FILTRE KEYWORD
            ->where('trainer.firstname LIKE :keyword')
            ->orWhere('trainer.lastname LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');


        // FILTRE CENTRE
        if (isset($filters['organization.name.source'])) {
            $qb
                ->innerJoin('trainer.organization', 'o', 'WITH', 'o = trainer.organization')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $filters['organization.name.source']);
        }

        //FILTRE ETABLISSEMENT
        if( isset($filters['institution.name.source'])) {
            $qb
                ->innerJoin('trainer.institution', 'i', 'WITH', 'trainer.institution = i')
                ->andWhere('i.name in (:inst)')
                ->setParameter('inst', $filters['institution.name.source']);
        }

        //FILTRE STATUT (true,false) = (0,1)
        if (isset($filters['isOrganization'])) {
            $qb
                ->andWhere('trainer.isOrganization = :isOrg')
                ->setParameter('isOrg', $filters['isOrganization']);
        }

        //FILTRE PUBLIE (true,false) = (0,1)
        if (isset($filters['isPublic'])) {
            $qb
                ->andWhere('trainer.isPublic = :isPub')
                ->setParameter('isPub', $filters['isPublic']);
        }

        //FILTRE ARCHIVE (true,false) = (0,1)
        if (isset($filters['isArchived'])) {
            $qb
                ->andWhere('trainer.isArchived = :isArch')
                ->setParameter('isArch', $filters['isArchived']);
        }

        // TRI DES RESULTATS
        $qb->addOrderBy('trainer.lastname');

        // PAGINATION
        if (($page == 'NO PAGE') && ($pageSize == 'NO SIZE')) {
            // on met une valeur par défaut (pour l'autocompletion)
            $page = 1;
            $pageSize = 50;
        }
        $offset = ($page-1) * $pageSize;
        $qb->setFirstResult($offset)
            ->setMaxResults($pageSize);

        $query = $qb->getQuery();

        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $c = count($paginator);
        $tabTrainers = array();
        foreach($paginator as $tr)
            $tabTrainers[] = $tr;

        $res = array('total' => $c,
            'pageSize' => $pageSize,
            'items' => $tabTrainers);

        return $res;
    }

    public function getNbTrainers($query_filters, $keyword, $aggs, $name)
    {
        $qb = $this->createQueryBuilder('trainer');
        $qb
            ->select('trainer')

            // FILTRE KEYWORD
            ->where('trainer.firstname LIKE :keyword')
            ->orWhere('trainer.lastname LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');


        // FILTRE CENTRE
        if(isset( $aggs['organization.name.source'])) {
            $qb
                ->innerJoin('trainer.organization', 'o', 'WITH', 'o = trainer.organization')
                ->andWhere('o.name = :center')
                ->setParameter('center', $name);
        } elseif (isset($query_filters['organization.name.source'])) {
            $qb
                ->innerJoin('trainer.organization', 'o', 'WITH', 'o = trainer.organization')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $query_filters['organization.name.source']);
        }

        // FILTRE ETABLISSEMENT
        if (isset($aggs['institution.name.source'])) {
            $qb
                ->innerJoin('trainer.institution', 'i', 'WITH', 'trainer.institution = i')
                ->andWhere('i.name = :inst')
                ->setParameter('inst', $name);
        } elseif (isset($query_filters['institution.name.source'])) {
            $qb
                ->innerJoin('trainer.institution', 'i', 'WITH', 'trainer.institution = i')
                ->andWhere('i.name in (:inst)')
                ->setParameter('inst', $query_filters['institution.name.source']);
        }

        //FILTRE STATUT (true,false) = (0,1)
        if(isset( $aggs['isOrganization'])) {
            $qb
                ->andWhere('trainer.isOrganization = :isOrg')
                ->setParameter('isOrg', $name);
        } elseif( isset($query_filters['isOrganization']) ) {
            $qb
                ->andWhere('trainer.isOrganization = :isOrg')
                ->setParameter('isOrg', $query_filters['isOrganization']);
        }

        //FILTRE PUBLIE (true,false) = (0,1)
        if(isset( $aggs['isPublic'])) {
            $qb
                ->andWhere('trainer.isPublic = :isPub')
                ->setParameter('isPub', $name);
        } elseif( isset($query_filters['isPublic']) ) {
            $qb
                ->andWhere('trainer.isPublic = :isPub')
                ->setParameter('isPub', $query_filters['isPublic']);
        }

        //FILTRE ARCHIVE (true,false) = (0,1)
        if(isset( $aggs['isArchived'])) {
            $qb
                ->andWhere('trainer.isArchived = :isArch')
                ->setParameter('isArch', $name);
        } elseif( isset($query_filters['isArchived']) ) {
            $qb
                ->andWhere('trainer.isArchived = :isArch')
                ->setParameter('isArch', $query_filters['isArchived']);
        }

        // On compte le nb de sessions en résultat
        $paginator = new Paginator($qb->getQuery());
        $totalRows = count($paginator);

        return $totalRows;
    }

}

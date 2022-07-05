<?php

namespace App\Repository;

use App\Entity\Institution;
use App\Entity\Session;
use App\Entity\Core\Term\Theme;
use App\Entity\Internship;
use App\Entity\Organization;
use App\Entity\Trainer;
use App\Entity\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class InstitutionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Institution::class);
    }

    public function getInstitutionsList($keyword, $filters, $page, $pageSize)
    {
        $qb = $this->createQueryBuilder('i');
        $qb
            ->select(' i')
            ->innerJoin('i.organization', 'o', 'WITH', 'i.organization = o')

            // FILTRE KEYWORD
            ->where('i.name LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');


        // FILTRE CENTRE
        if (isset($filters['organization.name.source'])) {
            $qb
                ->andWhere('i.organization = o.id')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $filters['organization.name.source']);
        }

        // FILTRE VILLE
        if (isset($filters['city.source'])) {
            $qb
                /* On récupère l'année du dateBegin (à l'aide d'une doctrine extension) */
                ->andWhere('i.city in (:cities)')
                ->setParameter('cities', $filters['city.source']);
        }

        // TRI DES RESULTATS
        $qb->addOrderBy('i.name');

        // PAGINATION
        $offset = ($page-1) * $pageSize;
        $qb->setFirstResult($offset)
            ->setMaxResults($pageSize);

        $query = $qb->getQuery();

        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $c = count($paginator);
        $tabInst = array();
        foreach($paginator as $inst)
            $tabInst[] = $inst;

        $res = array('total' => $c,
            'pageSize' => $pageSize,
            'items' => $tabInst);

        return $res;
    }

    public function getNbInstitutions($query_filters, $keyword, $aggs, $name)
    {
        $qb = $this->createQueryBuilder('i');
        $qb
            ->select('i')
            ->innerJoin('i.organization', 'o', 'WITH', 'i.organization = o')

            // FILTRE KEYWORD
            ->where('i.name LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');


        // FILTRE CENTRE
        if(isset( $aggs['organization.name.source'])) {
            $qb
                ->andWhere('i.organization = o.id')
                ->andWhere('o.name = :center')
                ->setParameter('center', $name);
        } elseif (isset($query_filters['organization.name.source'])) {
            $qb
                ->andWhere('i.organization = o.id')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $query_filters['organization.name.source']);
        }

        // FILTRE ANNEE
        if (isset($aggs['city.source'])) {
            $qb
                ->andWhere('i.city = :city')
                ->setParameter('city', $name);
        } elseif (isset($query_filters['year'])) {
            $qb
                ->andWhere('i.city in (:cities)')
                ->setParameter('cities', $query_filters['city.source']);
        }

        // On compte le nb de sessions en résultat
        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($qb->getQuery());
        $totalRows = count($paginator);

        return $totalRows;
    }

    public function getAllCities()
    {
        $qb = $this->createQueryBuilder('i');
        $qb
            ->select('i.city')
            ->groupBy('i.city');

        $query = $qb->getQuery();
        $result = $query->getResult();

        $tabCities = array();
        for ($i=0; $i<count($result); $i++){
            $tabCities[] = $result[$i]["city"];
        }

        return $tabCities;
    }

}

<?php

namespace App\Repository;

use App\Entity\Back\Institution;
use App\Entity\Back\Session;
use App\Entity\Term\Theme;
use App\Entity\Back\Internship;
use App\Entity\Back\Organization;
use App\Entity\Back\Trainer;
use App\Entity\Back\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

final class InstitutionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Institution::class);
    }

    /**
     * @return array{total: int, pageSize: mixed, items: mixed[]}
     */
    public function getInstitutionsList($keyword, $filters, $page, $pageSize): array
    {
        $qb = $this->createQueryBuilder('i');
        $qb
            ->select(' i')

            // FILTRE KEYWORD
            ->where('i.name LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes((string) $keyword, '%_') . '%');

        // FILTRE VILLE
        if (isset($filters['city.source']) && is_array($filters['city.source'])) {
            $qb
                /* On récupère l'année du dateBegin (à l'aide d'une doctrine extension) */
                ->andWhere('i.city in (:cities)')
                ->setParameter('cities', array_values($filters['city.source']));
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
        $tabInst = [];
        foreach($paginator as $inst)
            $tabInst[] = $inst;

        return ['total' => $c, 'pageSize' => $pageSize, 'items' => $tabInst];
    }

    public function getNbInstitutions($query_filters, $keyword, $aggs, $name): int
    {
        $qb = $this->createQueryBuilder('i');
        $qb
            ->select('i')

            // FILTRE KEYWORD
            ->where('i.name LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes((string) $keyword, '%_') . '%');

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

        return count($paginator);
    }

    /**
     * @return mixed[]
     */
    public function getAllCities(): array
    {
        $qb = $this->createQueryBuilder('i');
        $qb
            ->select('i.city')
            ->groupBy('i.city');

        $query = $qb->getQuery();
        $result = $query->getResult();

        $tabCities = [];
        $resultCount = count($result);
        for ($i=0; $i<(is_countable($result) ? $resultCount : 0); ++$i){
            $tabCities[] = $result[$i]["city"];
        }

        return $tabCities;
    }

}

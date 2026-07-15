<?php

namespace App\Repository;

use App\Entity\Back\Career;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

final class CareerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Career::class);
    }

    /**
     * @return array{total: int, pageSize: mixed, items: array<int, array{id: mixed, name: mixed, number: mixed, sessionscount: mixed, trainers?: array{fullname: mixed}[]|array{id: mixed}[]&mixed[], training: array{id: mixed, type: mixed, typeLabel: mixed, organization: mixed, number: mixed, theme: mixed, tags: mixed, name: mixed, program: mixed, description: mixed, interventionType: mixed, externalInitiative: mixed, category: mixed, comments: mixed, firstSessionPeriodSemester: mixed, firstSessionPeriodYear: mixed, publictypes: mixed, trainers: string}, nextsession: mixed, lastsession: mixed, theme: mixed, inscriptionsStats: never[]}>}
     */
    public function getCareersList($keyword,
                                     array $filters = [],
                                     int $page = 1,
                                     int $pageSize = 1,
                                     array $sorts = ['createdat' => 'DESC']): array
    {
        /* addcslashes empêchera des manipulations malveillantes éventuelles */
        $keywordPr = '%' . addcslashes((string) $keyword, '%_') . '%';
        $qb = $this->createQueryBuilder('career');
        $qb
            ->select('career')

            // FILTRE KEYWORD
            ->where('career.name LIKE :keywordPr')
            ->setParameter('keywordPr', $keywordPr);

        // FILTRE CENTRE
/*        if (isset($filters['career.organization.name.source'])) {
            $qb
                ->innerJoin('career.organization', 'o', 'WITH', 'o = career.organization')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $filters['career.organization.name.source']);
        }

        // TRI DES RESULTATS
        if ((is_array($sorts)) && (array_key_exists('career.name.source', $sorts)))
            $qb->addOrderBy('career.name', $sorts['career.name.source']);
        else
            $qb->addOrderBy('career.name'); */

        // PAGINATION
        if (($page == 'NO PAGE') && ($pageSize == 'NO SIZE')) {
            // on met une valeur par défaut (pour l'autocompletion)
            $page = 1;
            $pageSize = 50;
            
        }

        $page = max(1, (int)$page);
        $pageSize = max(1, (int)$pageSize);
        $offset = ($page - 1) * $pageSize;

        $qb->setFirstResult($offset)
            ->setMaxResults($pageSize);


        $query = $qb->getQuery();
        $paginator = new Paginator($query, $fetchJoinCollection = true);
        $c = count($paginator);

        $items = [];

        foreach ($paginator as $career) {

            $items[] = [
                'id' => $career->getId(),
                'name' => $career->getName(),
                'description' => $career->getDescription(),
            ];
    }

        return ['total' => $c, 'pageSize' => $pageSize, 'items' => $items];
    }

    public function getNbCareers(array $query_filters = [], ?string $keyword = '', array $aggs = [], mixed $name = "", $facet = null): array
    {
        $qb = $this->createQueryBuilder('career');
        $qb
            ->select('COUNT(DISTINCT career)')
            // FILTRE KEYWORD
            ->where('career.name LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes((string) $keyword, '%_') . '%');


        // FILTRE CENTRE
        if(isset( $aggs['career.organization.name.source'])) {
            $qb
                ->innerJoin('career.organization', 'o', 'WITH', 'o = career.organization')
                ->andWhere('o.name = :center')
                ->setParameter('center', $name);
        } elseif (isset($query_filters['career.organization.name.source'])) {
            $qb
                ->innerJoin('career.organization', 'o', 'WITH', 'o = career.organization')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $query_filters['career.organization.name.source']);
        }



        $total = (int) $qb->getQuery()->getSingleScalarResult();

        return [
            'total' => $total,
            'items' => [],
        ];
    }

}

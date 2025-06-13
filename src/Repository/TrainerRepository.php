<?php

namespace App\Repository;

use App\Entity\Term\Theme;
use App\Entity\Back\Institution;
use App\Entity\Back\Internship;
use App\Entity\Back\Organization;
use App\Entity\Back\Session;
use App\Entity\Back\Trainer;
use App\Entity\Back\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

final class TrainerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Trainer::class);
    }

    /**
     * @return array{total: int, pageSize: mixed, items: mixed[]}
     */
    public function getTrainersList($keyword, $filters, $page, $pageSize, $sorts, $fields): array
    {

        $MAX_EXPORT_LIMIT = 10000; // Limite sécurisée
        $MAX_PAGE_SIZE = 50; // Limite "normale" pour la navigation

        $isExport = isset($filters['_export']) && $filters['_export'] === true;

        $pageSize = max(1, (int) $pageSize);
        $pageSize = $isExport
            ? min($pageSize, $MAX_EXPORT_LIMIT)
            : min($pageSize, $MAX_PAGE_SIZE);


        $qb = $this->createQueryBuilder('trainer')
            ->select('trainer')
            ->where('trainer.firstname LIKE :keyword OR trainer.lastname LIKE :keyword')
            ->setParameter('keyword', '%' . addcslashes((string)$keyword, '%_') . '%');

        // Join & Filter: Organization
        if (!empty($filters['organization.name.source'])) {
            $qb->innerJoin('trainer.organization', 'o')
                ->andWhere('o.name IN (:centers)')
                ->setParameter('centers', $filters['organization.name.source']);
        }

        // Join & Filter: Institution
        if (!empty($filters['institution.name.source'])) {
            $qb->innerJoin('trainer.institution', 'i')
                ->andWhere('i.name IN (:institutions)')
                ->setParameter('institutions', $filters['institution.name.source']);
        }

        // Other filters
        foreach (['isOrganization', 'isPublic', 'isArchived'] as $filter) {
            if (isset($filters[$filter])) {
                $param = lcfirst($filter);
                $qb->andWhere("trainer.$param = :$param")
                    ->setParameter($param, $filters[$filter]);
            }
        }

        // Sorting
        if (is_array($sorts)) {
            foreach ($sorts as $field => $direction) {
                switch ($field) {
                    case 'lastname':
                        $qb->addOrderBy('trainer.lastname', $direction);
                        break;
                    case 'organization.name':
                        $qb->leftJoin('trainer.organization', 'o')
                            ->addOrderBy('o.name', $direction);
                        break;
                    case 'institution.name':
                        $qb->leftJoin('trainer.institution', 'i')
                            ->addOrderBy('i.name', $direction);
                        break;
                    default:
                        if (property_exists(Trainer::class, $field)) {
                            $qb->addOrderBy("trainer.$field", $direction);
                        }
                        break;
                }
            }
        } else {
            $qb->addOrderBy('trainer.lastname', 'ASC');
        }

        $qb->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        $paginator = new Paginator($qb);

        $items = [];
        foreach ($paginator as $trainer) {
            $items[] = [
                'id' => $trainer->getId(),
                'firstname' => $trainer->getFirstname(),
                'lastname' => $trainer->getLastname(),
                'fullname' => $trainer->getFirstname() . ' ' . $trainer->getLastname(),
                'isPublic' => $trainer->isIspublic(),
                'isArchived' => $trainer->isIsarchived(),
                'service' => $trainer->getService(),
                'organization' => $trainer->getOrganization(),
                'institution' => $trainer->getInstitution(),
            ];
        }


        return [
            'total' => count($paginator),
            'pageSize' => $pageSize,
            'items' => $items,
        ];
    }

    public function getNbTrainers($query_filters, $keyword, $aggs, $name): array
    {
        $qb = $this->createQueryBuilder('trainer');
        $qb
            ->select('trainer')

            // FILTRE KEYWORD
            ->where('trainer.firstname LIKE :keyword')
            ->orWhere('trainer.lastname LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes((string) $keyword, '%_') . '%');


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
                ->innerJoin('trainer.institution', 'i')
                ->andWhere('i.name = :i')
                ->setParameter('i', $name);
        } elseif (isset($query_filters['institution.name.source'])) {
            $qb
                ->innerJoin('trainer.institution', 'i')
                ->andWhere('i.name in (:i)')
                ->setParameter('i', $query_filters['institution.name.source']);
        }

        //FILTRE STATUT (true,false) = (0,1)
        if(isset( $aggs['isOrganization'])) {
            $qb
                ->andWhere('trainer.isorganization = :isOrg')
                ->setParameter('isOrg', $name);
        } elseif( isset($query_filters['isOrganization']) ) {
            $qb
                ->andWhere('trainer.isorganization = :isOrg')
                ->setParameter('isOrg', $query_filters['isOrganization']);
        }

        //FILTRE PUBLIE (true,false) = (0,1)
        if(isset( $aggs['isPublic'])) {
            $qb
                ->andWhere('trainer.ispublic = :isPub')
                ->setParameter('isPub', $name);
        } elseif( isset($query_filters['isPublic']) ) {
            $qb
                ->andWhere('trainer.ispublic = :isPub')
                ->setParameter('isPub', $query_filters['isPublic']);
        }

        //FILTRE ARCHIVE (true,false) = (0,1)
        if(isset( $aggs['isArchived'])) {
            $qb
                ->andWhere('trainer.isarchived = :isArch')
                ->setParameter('isArch', $name);
        } elseif( isset($query_filters['isArchived']) ) {
            $qb
                ->andWhere('trainer.isarchived = :isArch')
                ->setParameter('isArch', $query_filters['isArchived']);
        }


        // On compte le nb de sessions en résultat
        $paginator = new Paginator($qb->getQuery());

        return [
            'total' => count($paginator),
            'items' => iterator_to_array($paginator),
        ];
    }

}

<?php

namespace App\Repository;

use App\Entity\Core\AbstractTrainee;
use App\Entity\Term\Publictype;
use App\Entity\Term\Title;
use App\Entity\Back\Institution;
use App\Entity\Back\Organization;
use App\Entity\Back\Trainee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Class TraineeRepository.
 *
 * @see http://symfony.com/fr/doc/current/cookbook/security/entity_provider.html
 */
final class TraineeSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Trainee::class);
    }

    /**
     * @param $noPage
     * @return array{total: int, pageSize: mixed, items: array}
     */
    public function getTraineesList(        string $keyword = "",
                                            array $filters = [],
                                            int $page = 1,
                                            int $pageSize = 1,
                                            array $sort = ['createdAt' => 'DESC'],
                                            array $fields = []): array
    {
        // Mise en forme en cas de recherche nom + prénom

        $MAX_EXPORT_LIMIT = 10000; // Limite sécurisée, à adapter selon mémoire/disque/dispo
        $MAX_PAGE_SIZE = 100; // Limite "normale" pour la navigation

        // Cas normal (pagination UI) vs. export
        $isExport = isset($filters['_export']) && $filters['_export'] === true;

        $pageSize = max(1, (int) $pageSize);
        $pageSize = $isExport
            ? min($pageSize, $MAX_EXPORT_LIMIT)
            : min($pageSize, $MAX_PAGE_SIZE);

        $qb = $this->createQueryBuilder('trainee');

        $qb->select('trainee');

        // Gestion du mot-clé
        $keyword = trim($keyword);
        if (!empty($keyword)) {
            $tabKey = explode(' ', $keyword, 2);
            if (count($tabKey) === 2) {
                $qb->andWhere('(trainee.firstname LIKE :keyword AND trainee.lastname LIKE :keyword2)')
                    ->setParameter('keyword1', '%' . addcslashes($tabKey[0], '%_') . '%')
                    ->setParameter('keyword2', '%' . addcslashes($tabKey[1], '%_') . '%');
            } else {
                $qb->andWhere(
                    'trainee.firstname LIKE :keyword OR trainee.lastname LIKE :keyword OR trainee.email LIKE :keyword'
                )->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');
            }
        }

        // Filtres
        if (!empty($filters['createdAt'])) {
            $dates = explode('-', (string)$filters['createdAt']);
            if (count($dates) === 2) {
                $from = date('Y-m-d 00:00:00', strtotime(str_replace('/', '-', trim($dates[0]))));
                $to = date('Y-m-d 23:59:59', strtotime(str_replace('/', '-', trim($dates[1]))));

                $qb->andWhere('trainee.createdat BETWEEN :dateFrom AND :dateTo')
                    ->setParameter('dateFrom', $from)
                    ->setParameter('dateTo', $to);
            }
        }

        if (isset($filters['title'])) {
            $qb
                ->innerJoin('trainee.title', 'ti', 'WITH', 'trainee.title = ti')
                ->andWhere('ti.name in (:title)')
                ->setParameter('title', $filters['title']);
        }

        if (isset($filters['institution.name.source'])) {
            $qb
                ->innerJoin('trainee.institution', 'institution')
                ->andWhere('institution.name in (:institution)')
                ->setParameter('institution', $filters['institution.name.source']);
        }

        if (isset($filters['publicType.source'])) {
            $qb
                ->innerJoin('trainee.publictype', 'pt', 'WITH', 'trainee.publictype = pt')
                ->andWhere('pt.name in (:publictype)')
                ->setParameter('publictype', $filters['publicType.source']);
        }

        // TRI DES RESULTATS
        if ((is_array($sort)) && (array_key_exists('lastName.source', $sort)))
            $qb->addOrderBy('trainee.lastname', $sort['lastName.source']);
        elseif ((is_array($sort)) && (array_key_exists('title', $sort))) {
            if(!isset($filters['title']))
                $qb->innerJoin('trainee.title', 'title', 'WITH', 'trainee.title = title');

            $qb->addOrderBy('title.name', $sort['title']);
        } elseif ((is_array($sort)) && (array_key_exists('publicType.source', $sort))) {
            if(!isset($filters['publicType.source']))
                $qb->innerJoin('trainee.publictype', 'pt', 'WITH', 'trainee.publictype = pt');

            $qb->addOrderBy('pt.name', $sort['publicType.source']);
        } elseif ((is_array($sort)) && (array_key_exists('institution.name.source', $sort))) {
            $qb->innerJoin('trainee.institution', 'institution');

            $qb->addOrderBy('institution.name', $sort['institution.name.source']);
            $qb->addSelect('institution');
        } elseif ((is_array($sort)) && (array_key_exists('createdAt', $sort)))
            $qb->addOrderBy('trainee.createdat', $sort['createdAt']);
        else
            $qb->addOrderBy('trainee.createdat', 'desc');

        // Pagination
        $qb->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        $paginator = new Paginator($qb);

        $items = [];
        foreach ($paginator as $trainee) {
            $items[] = [
                'id' => $trainee->getId(),
                'firstname' => $trainee->getFirstname(),
                'lastname' => $trainee->getLastname(),
                'fullname' => $trainee->getFirstname() . ' ' . $trainee->getLastname(),
                'institution' => $trainee->getInstitution(),
                'title' => $trainee->getTitle(),
                'createdat' => $trainee->getCreatedAt(),
                'publictype' => $trainee->getPublictype(),
            ];
        }

        return [
            'total' => count($paginator),
            'pageSize' => $pageSize,
            'items' => $items,
        ];
    }

    /**
     * Compte les stagiaires selon filtres et agrégats
     */
    public function getNbTrainees(array $query_filters = [], ?string $keyword = '', array $aggs = [], ?string $name = null): int
    {
        $qb = $this->createQueryBuilder('trainee')->select('trainee');

        if ($keyword) {
            $qb->andWhere('trainee.firstname LIKE :keyword OR trainee.lastname LIKE :keyword')
                ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');
        }

        if (isset($aggs['title']) || isset($query_filters['title'])) {
            $qb->innerJoin('trainee.title', 'ti');
            if (isset($aggs['title'])) {
                $qb->andWhere('ti.name = :title')->setParameter('title', $name);
            } else {
                $qb->andWhere('ti.name IN (:titles)')->setParameter('titles', $query_filters['title']);
            }
        }

        if (isset($aggs['institution']) || isset($query_filters['institution'])) {
            $qb->innerJoin('trainee.institution', 'institution');
            if (isset($aggs['institution'])) {
                $qb->andWhere('institution.name = :institution')->setParameter('institution', $name);
            } else {
                $qb->andWhere('institution.name IN (:institutions)')
                    ->setParameter('institutions', $query_filters['institution']);
            }
        }

        if (isset($aggs['publicType']) || isset($query_filters['publicType'])) {
            $qb->innerJoin('trainee.publictype', 'pt');
            if (isset($aggs['publicType'])) {
                $qb->andWhere('pt.name = :publictype')->setParameter('publictype', $name);
            } else {
                $qb->andWhere('pt.name IN (:publictypes)')
                    ->setParameter('publictypes', $query_filters['publicType']);
            }
        }

        $paginator = new Paginator($qb->getQuery());

        return count($paginator);
    }

    /**
     * Transforme un stagiaire en tableau de données
     */
    private function sanitizeTrainee(AbstractTrainee $trainee): array
    {
        return [
            'id' => $trainee->getId(),
            'firstname' => $trainee->getFirstname(),
            'lastname' => $trainee->getLastname(),
            'email' => $trainee->getEmail(),
            'createdAt' => $trainee->getCreatedAt()?->format('Y-m-d H:i:s'),
            'title' => $trainee->getTitle()?->getName(),
            'institution' => $trainee->getInstitution()?->getName(),
            'publictype' => $trainee->getPublictype()?->getName(),
        ];
    }
}

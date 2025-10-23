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
        $tabKey = explode(" ", $keyword, 2);

        $qb = $this->createQueryBuilder('trainee');

        if (count($tabKey) == 2) {
            $qb
                ->select(' trainee')

                // FILTRE KEYWORD
                ->andWhere('(trainee.firstname LIKE :keyword1 AND trainee.lastname LIKE :keyword2) OR (trainee.lastname LIKE :keyword)')
                /* addcslashes empêchera des manipulations malveillantes éventuelles */
                ->setParameter('keyword1', '%' . addcslashes($tabKey[0], '%_') . '%')
                ->setParameter('keyword2', '%' . addcslashes($tabKey[1], '%_') . '%')
                ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');
        } else {
            $qb
                ->select(' trainee')

                // FILTRE KEYWORD
                ->where('trainee.firstname LIKE :keyword')
                ->orWhere('trainee.lastname LIKE :keyword')
                ->orWhere('trainee.email LIKE :keyword')
                /* addcslashes empêchera des manipulations malveillantes éventuelles */
                ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');
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
        if (($page == 'NO PAGE') && ($pageSize == 'NO SIZE')) {
            // on met une valeur par défaut (pour l'autocompletion)
            $page = 1;
            $pageSize = 50;
        }
        $offset = ($page - 1) * $pageSize;
        $qb->setFirstResult($offset)
            ->setMaxResults($pageSize);

        $query = $qb->getQuery();

        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $c = count($paginator);
        $tabTrainees = array();
        foreach($paginator as $tr) {
            if ((is_array($fields)) && (in_array("_id", $fields))) {
                $tabTrainees[]['id'] = $tr->getId();
            } else {
                $tabTrainees[] = $tr;
            }
        }

        $res = array('total' => $c,
            'pageSize' => $pageSize,
            'items' => $tabTrainees);

        return $res;

    }

    /**
     * Compte les stagiaires selon filtres et agrégats
     */
    public function getNbTrainees(array $query_filters = [], ?string $keyword = '', array $aggs = [], ?string $name = null): array
    {
        $qb = $this->createQueryBuilder('trainee')->select('COUNT(DISTINCT trainee.id)');

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

        //FILTRE DATE
        if( isset($aggs['createdAt']) || isset($query_filters['createdAt']) ) {
            $dates = explode('-', (string) $aggs["createdAt"]);
            $dateFrom = date('d/m/Y 00:00:00', strtotime(trim($dates[0])));
            $dateTo   = date('d/m/Y 23:59:59', strtotime(trim($dates[1])));

            $qb
                ->andWhere("trainee.createdat BETWEEN :dateFrom AND :dateTo")
                ->setParameter('dateFrom', $dateFrom)
                ->setParameter('dateTo', $dateTo);
        }

        if (isset($aggs['publicType']) || isset($query_filters['publicType.source'])  || isset($query_filters['publicType']))  {
            $qb->innerJoin('trainee.publictype', 'pt');
            if (isset($aggs['publicType'])|| isset($aggs['publicType.source'])) {
                $qb->andWhere('pt.name = :publictype')->setParameter('publictype', $name);
            } else {
                $qb->andWhere('pt.name IN (:publictypes)')
                    ->setParameter('publictypes', $query_filters['publicType.source']);
            }
        }
        $total = (int) $qb->getQuery()->getSingleScalarResult();

        return [
            'total' => $total,
            'items' => [],
        ];
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

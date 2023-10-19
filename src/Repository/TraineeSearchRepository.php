<?php

namespace App\Repository;

use App\Entity\Term\Publictype;
use App\Entity\Term\Title;
use App\Entity\Back\Institution;
use App\Entity\Back\Organization;
use App\Entity\Back\Trainee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * Class TraineeRepository.
 *
 * @see http://symfony.com/fr/doc/current/cookbook/security/entity_provider.html
 */
class TraineeSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trainee::class);
    }

    public function getTraineesList($keyword, $filters, $page, $pageSize, $sort, $fields)
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


        //FILTRE DATE DE CREATION
        if( isset($filters['createdAt']) ) {
            /* La date envoyée par le formulaire en JS a un format : "dd/mm/yy - dd/mm/yy" il faut donc séparer les 2 dates */
            $dates = explode('-', $filters["createdAt"]);
            /* on retire les caractères non utiles */
            $from = str_replace('/','-', $dates[0]);
            $to = str_replace('/', '-', $dates[1]);
            /* on convertit au même format qu'en base de données */
            $dateFrom = date('Y/m/d 00:00:00' ,strtotime($from));
            $dateTo = date('Y/m/d 00:00:00',strtotime($to));

            $qb
                /* si la date de début d'une session est entre les 2 dates envoyées dans le formulaire */
                ->andWhere("trainee.createdat BETWEEN :dateFrom AND :dateTo")
                ->setParameter('dateFrom', $dateFrom)
                ->setParameter('dateTo', $dateTo);
        }

        //FILTRE CIVILITE
        if( isset($filters['title'])) {
            $qb
                ->innerJoin('trainee.title', 'ti', 'WITH', 'trainee.title = ti')
                ->andWhere('ti.name in (:title)')
                ->setParameter('title', $filters['title']);
        }

        // FILTRE ETABLISSEMENT
        if( isset($filters['institution.name.source']) ) {
            $qb
                ->innerJoin('trainee.institution', 'institution', 'WITH', 'trainee.institution = institution')
                ->andWhere('institution.name in (:institution)')
                ->setParameter('institution', $filters['institution.name.source']);
        }

        // FILTRE PUBLIC TYPE
        if( isset($filters['publicType.source']) ){
            $qb
                ->innerJoin('trainee.publictype', 'pt', 'WITH', 'trainee.publictype = pt')
                ->andWhere('pt.name in (:publictype)')
                ->setParameter('publictype', $filters['publicType.source']);
        }
                $query = $qb->getQuery();

        // TRI DES RESULTATS
        if ((is_array($sort)) && (array_key_exists('lastName.source', $sort)))
            $qb->addOrderBy('trainee.lastname', $sort['lastName.source']);
        elseif ((is_array($sort)) && (array_key_exists('title', $sort))) {
            $qb->innerJoin('trainee.title', 'title', 'WITH', 'trainee.title = title');
            $qb->addOrderBy('title.name', $sort['title']);
        } elseif ((is_array($sort)) && (array_key_exists('createdAt', $sort)))
            $qb->addOrderBy('trainee.createdat', $sort['createdAt']);
        else
            $qb->addOrderBy('trainee.createdat', 'desc');

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

    public function getNbTrainees($query_filters, $keyword, $aggs, $name)
    {
        $qb = $this->createQueryBuilder('trainee');
        $qb
            ->select('trainee')

            // FILTRE KEYWORD
            ->where('trainee.firstname LIKE :keyword')
            ->orWhere('trainee.lastname LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');

        // FILTRE CIVILITE
        if (isset($aggs['title'])) {
            $qb
                ->innerJoin('trainee.title', 'ti', 'WITH', 'trainee.title = ti')
                ->andWhere('ti.name = :title')
                ->setParameter('title', $name);
        } elseif (isset($query_filters['title'])) {
            $qb
                ->innerJoin('trainee.title', 'ti', 'WITH', 'trainee.title = ti')
                ->andWhere('ti.name in (:titles)')
                ->setParameter('titles', $query_filters['title']);
        }

        //FILTRE ETABLISSEMENT
        if(isset( $aggs['institution.name.source'])) {
            $qb
                ->innerJoin('trainee.institution', 'institution', 'WITH', 'trainee.institution = institution')
                ->andWhere('institution.name = :institution')
                ->setParameter('institution', $name);
        } elseif (isset($query_filters['institution.name.source'])) {
            $qb
                ->innerJoin('trainee.institution', 'institution', 'WITH', 'trainee.institution = institution')
                ->andWhere('institution.name in (:institutions)')
                ->setParameter('institutions', $query_filters['institution.name.source']);
        }

        // FILTRE PUBLIC TYPE
        if(isset( $aggs['publicType.source'])) {
            $qb
                ->innerJoin('trainee.publictype', 'pt', 'WITH', 'trainee.publictype = pt')
                ->andWhere('pt.name = :publictype')
                ->setParameter('publictype', $name);
        } elseif( isset($query_filters['publicType.source']) ) {
            $qb
                ->innerJoin('trainee.publictype', 'pt', 'WITH', 'trainee.publictype = pt')
                ->andWhere('pt.name in (:publictypes)')
                ->setParameter('publictypes', $query_filters['publicType.source']);
        }

        // On compte le nb de stagiaires en résultat
        $paginator = new Paginator($qb->getQuery());
        $totalRows = count($paginator);

        return $totalRows;
    }
}

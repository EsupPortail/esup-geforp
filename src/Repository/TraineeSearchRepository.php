<?php

namespace App\Repository;

use App\Entity\Core\Term\Publictype;
use App\Entity\Core\Term\Title;
use App\Entity\Institution;
use App\Entity\Organization;
use App\Entity\Trainee;
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

    public function getTraineesList($keyword, $filters, $page, $pageSize, $sort)
    {
        $qb = $this->createQueryBuilder('trainee');
        $qb
            ->select(' trainee')
            ->innerJoin('trainee.organization', 'o', 'WITH', 'o = trainee.organization')
            ->innerJoin('trainee.institution', 'institution', 'WITH', 'trainee.institution = institution')
            ->innerJoin('trainee.publictype', 'pt', 'WITH', 'trainee.publictype = pt')
            ->innerJoin('trainee.title', 'ti', 'WITH', 'trainee.title = ti')

            // FILTRE KEYWORD
            ->where('trainee.firstname LIKE :keyword')
            ->orWhere('trainee.lastname LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');


        // FILTRE CENTRE
        if (isset($filters['organization.name.source'])) {
            $qb
                ->andWhere('trainee.organization = o.id')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $filters['organization.name.source']);
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
                ->andWhere('trainee.title = ti.id')
                ->andWhere('ti.name = :title')
                ->setParameter('title', $filters['title']);
        }

        // FILTRE ETABLISSEMENT
        if( isset($filters['institution.name.source']) ) {
            $qb
                ->andWhere('trainee.institution = institution.id')
                ->andWhere('institution.name = :institution')
                ->setParameter('institution', $filters['institution.name.source']);
        }

        // FILTRE PUBLIC TYPE
        if( isset($filters['publicType.source']) ){
            $qb
                ->andWhere('trainee.publictype = pt.id')
                ->andWhere('pt.name = :publictype')
                ->setParameter('publictype', $filters['publicType.source']);
        }
                $query = $qb->getQuery();

        // TRI DES RESULTATS
        if (array_key_exists('lastname', $sort))
            $qb->addOrderBy('trainee.lastname');
        else
            $qb->addOrderBy('trainee.createdat', 'desc');

        // PAGINATION
        $offset = ($page-1) * $pageSize;
        $qb->setFirstResult($offset)
            ->setMaxResults($pageSize);

        $query = $qb->getQuery();

        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $c = count($paginator);
        $tabTrainees = array();
        foreach($paginator as $tr)
            $tabTrainees[] = $tr;

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
            ->innerJoin('trainee.organization', 'o', 'WITH', 'o = trainee.organization')
            ->innerJoin('trainee.institution', 'institution', 'WITH', 'trainee.institution = institution')
            ->innerJoin('trainee.publictype', 'pt', 'WITH', 'trainee.publictype = pt')
            ->innerJoin('trainee.title', 'ti', 'WITH', 'trainee.title = ti')

            // FILTRE KEYWORD
            ->where('trainee.firstname LIKE :keyword')
            ->orWhere('trainee.lastname LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');


        // FILTRE CENTRE
        if(isset( $aggs['organization.name.source'])) {
            $qb
                ->andWhere('trainee.organization = o.id')
                ->andWhere('o.name = :center')
                ->setParameter('center', $name);
        } elseif (isset($query_filters['organization.name.source'])) {
            $qb
                ->andWhere('trainee.organization = o.id')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $query_filters['organization.name.source']);
        }

        // FILTRE CIVILITE
        if (isset($aggs['title'])) {
            $qb
                ->andWhere('trainee.title = ti.id')
                ->andWhere('ti.name = :title')
                ->setParameter('title', $name);
        } elseif (isset($query_filters['title'])) {
            $qb
                ->andWhere('trainee.title = ti.id')
                ->andWhere('ti.name = :title')
                ->setParameter('title', $query_filters['title']);
        }

        //FILTRE ETABLISSEMENT
        if(isset( $aggs['institution.name.source'])) {
            $qb
                ->andWhere('trainee.institution = institution.id')
                ->andWhere('institution.name = :institution')
                ->setParameter('institution', $name);
        } elseif (isset($query_filters['institution.name.source'])) {
            $qb
                ->andWhere('trainee.institution = institution.id')
                ->andWhere('institution.name = :institution')
                ->andWhere('th.name in (:institutions)')
                ->setParameter('institutions', $query_filters['institution.name.source']);
        }

        // FILTRE PUBLIC TYPE
        if(isset( $aggs['publicType.source'])) {
            $qb
                ->andWhere('trainee.publictype = pt.id')
                ->andWhere('pt.name = :publictype')
                ->setParameter('publictype', $name);
        } elseif( isset($query_filters['publicType.source']) ) {
            $qb
                ->andWhere('trainee.publictype = pt.id')
                ->andWhere('pt.name = :publictype')
                ->setParameter('publictype', $query_filters['publicType.source']);
        }

        // On compte le nb de stagiaires en résultat
        $paginator = new Paginator($qb->getQuery());
        $totalRows = count($paginator);

        return $totalRows;
    }
}

<?php

namespace App\Repository;

use App\Entity\Term\Inscriptionstatus;
use App\Entity\Term\Presencestatus;
use App\Entity\Term\Publictype;
use App\Entity\Back\Inscription;
use App\Entity\Back\Institution;
use App\Entity\Back\Session;
use App\Entity\Back\Internship;
use App\Entity\Back\Organization;
use App\Entity\Back\Trainee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

class InscriptionSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inscription::class);
    }

    public function getInscriptionsList($keyword, $filters, $page, $pageSize, $sorts, $fields)
    {
        $qb = $this->createQueryBuilder('i');
        $qb
            ->select(' i')
            // join sur trainee et session car toujours vrai
            ->innerJoin('i.trainee', 'trainee', 'WITH', 'trainee = i.trainee')
            ->innerJoin('i.session', 's', 'WITH', 's = i.session')
            ->innerJoin('s.training', 'tr', 'WITH', 'tr = s.training')


            // FILTRE KEYWORD
            ->where('trainee.firstname LIKE :keyword OR trainee.lastname LIKE :keyword OR tr.name LIKE :keyword')
            ->andWhere('i.trainee = trainee.id')
            ->andWhere('s.training = tr.id')
            ->andWhere('i.session = s.id')

            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');

        // FILTRE CENTRE
        if (isset($filters['session.training.organization.name.source'])) {
            $qb
                ->innerJoin('tr.organization', 'o', 'WITH', 'o = tr.organization')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $filters['session.training.organization.name.source']);
        }

        // FILTRE ANNEE
        if (isset($filters['session.year'])) {
            $qb
                /* On récupère l'année du dateBegin (à l'aide d'une doctrine extension) */
                ->andWhere('YEAR(s.datebegin) in (:years)')
                ->setParameter('years', $filters['session.year']);
        }

        // FILTRE SEMESTRE
        if( isset($filters['session.semester']) ) {
            if ($filters['session.semester']  == 1) {
                $monthFrom = 1; $monthTo = 6;
            } else {
                $monthFrom = 7; $monthTo = 12;
            }
            $qb
                ->andWhere('MONTH(s.datebegin) BETWEEN :monthFrom and :monthTo')
                ->setParameter('monthFrom', $monthFrom)
                ->setParameter('monthTo', $monthTo);
        }

        //FILTRE DATE
        if( isset($filters['session.datebegin']) ) {
            /* La date envoyée par le formulaire en JS a un format : "dd/mm/yy - dd/mm/yy" il faut donc séparer les 2 dates */
            $dates = explode('-', $filters["session.datebegin"]);
            /* on retire les caractères non utiles */
            $from = str_replace('/','-', $dates[0]);
            $to = str_replace('/', '-', $dates[1]);
            /* on convertit au même format qu'en base de données */
            $dateFrom = date('Y/m/d 00:00:00' ,strtotime($from));
            $dateTo = date('Y/m/d 00:00:00',strtotime($to));

            $qb
                /* si la date de début d'une session est entre les 2 dates envoyées dans le formulaire */
                ->andWhere("s.datebegin BETWEEN :dateFrom AND :dateTo")
                ->setParameter('dateFrom', $dateFrom)
                ->setParameter('dateTo', $dateTo);
        }

        //FILTRE STATUT D'INSCRIPTION
        if( isset($filters['inscriptionStatus.name.source'])) {
            $qb
                ->innerJoin('i.inscriptionstatus', 'istatus', 'WITH', 'istatus = i.inscriptionstatus')
                ->andWhere('istatus.name in (:status)')
                ->setParameter('status', $filters['inscriptionStatus.name.source']);
        }

        //FILTRE STATUT DE PRESENCE
        if( isset($filters['presenceStatus.name.source'])) {
            $qb
                ->innerJoin('i.presencestatus', 'pstatus', 'WITH', 'pstatus = i.presencestatus')
                ->andWhere('pstatus.name in (:status)')
                ->setParameter('status', $filters['presenceStatus.name.source']);
        }

        //FILTRE ETABLISSEMENT
        if( isset($filters['institution.name.source'])) {
            $qb
                ->innerJoin('trainee.institution', 'inst', 'WITH', 'trainee.institution = inst')
                ->andWhere('institution.name in (:institutions)')
                ->setParameter('institutions', $filters['institution.name.source']);
        }

        //FILTRE CATEGORIE DE PERSONNEL
        if( isset($filters['publicType.source'])) {
            $qb
                ->innerJoin('trainee.publictype', 'publictype', 'WITH', 'trainee.publictype = publictype')
                ->andWhere('publictype.name in (:publictypes)')
                ->setParameter('publictypes', $filters['publicType.source']);
        }

        // FILTRE SESSION
        if (isset($filters['session.id'])) {
            $qb
                ->andWhere('s.id in (:id)')
                ->setParameter('id', $filters['session.id']);
        }

        // TRI DES RESULTATS
        if ((is_array($sorts)) && (array_key_exists('createdat', $sorts)))
            $qb->addOrderBy('i.createdat', $sorts['createdat']);
        elseif ((is_array($sorts)) && (array_key_exists('trainee.fullname', $sorts)))
            $qb->addOrderBy('trainee.lastname', $sorts['trainee.fullname']);
        elseif ((is_array($sorts)) && (array_key_exists('session.datebegin', $sorts)))
            $qb->addOrderBy('s.datebegin', $sorts['session.datebegin']);
        elseif ((is_array($sorts)) && (array_key_exists('trainee.publictype.name', $sorts))){
            $qb->innerJoin('trainee.publictype', 'publictype', 'WITH', 'trainee.publictype = publictype');
            $qb->addOrderBy('publictype.name', $sorts['trainee.publictype.name']);
        } else {
            //FILTRE MODIFICATION DU STATUT D'INSCRIPTION
            if( isset($filters['inscriptionStatusUpdatedAt'])) {
                // Tri par date de modification
                $qb->addOrderBy('i.updatedat', 'DESC');
            } else {
                // TRI DES RESULTATS
                $qb->addOrderBy('i.createdat', 'DESC');
            }
        }

        // PAGINATION
        $offset = ($page-1) * $pageSize;
        $qb->setFirstResult($offset)
            ->setMaxResults($pageSize);

        $query = $qb->getQuery();

        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $c = count($paginator);
        $tabIns = array();
        foreach($paginator as $insc) {
            if ((is_array($fields)) && (in_array("_id", $fields))) {
                $tabIns[]['id'] = $insc->getId();
            } else {
                $tabIns[] = $insc;
            }
        }

        $res = array('total' => $c,
            'pageSize' => $pageSize,
            'items' => $tabIns);

        return $res;
    }

    public function getNbInscriptions($query_filters, $keyword, $aggs, $name)
    {
        $qb = $this->createQueryBuilder('i');
        $qb
            ->select('i')
            ->innerJoin('i.trainee', 'trainee', 'WITH', 'trainee = i.trainee')
            ->innerJoin('i.session', 's', 'WITH', 's = i.session')

            // FILTRE KEYWORD
            ->where('trainee.firstname LIKE :keyword')
            ->orWhere('trainee.lastname LIKE :keyword')
            ->andWhere('i.trainee = trainee.id')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');

        // FILTRE CENTRE
        if(isset( $aggs['session.training.organization.name.source'])) {
            $qb
                ->innerJoin('s.training', 'tr', 'WITH', 's.training = tr')
                ->innerJoin('tr.organization', 'o', 'WITH', 'o = tr.organization')
                ->andWhere('o.name = :center')
                ->setParameter('center', $name);
        } elseif (isset($query_filters['session.training.organization.name.source'])) {
            $qb
                ->innerJoin('s.training', 'tr', 'WITH', 's.training = tr')
                ->innerJoin('tr.organization', 'o', 'WITH', 'o = tr.organization')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $query_filters['session.training.organization.name.source']);
        }

        // FILTRE ANNEE
        if (isset($aggs['session.year'])) {
            $qb
                ->andWhere('YEAR(s.datebegin) = :year')
                ->setParameter('year', $name);
        } elseif (isset($query_filters['session.year'])) {
            $qb
                ->andWhere('YEAR(s.datebegin) in (:years)')
                ->setParameter('years', $query_filters['session.year']);
        }

        // FILTRE SEMESTRE
        if (isset($aggs['session.semester'])) {
            if ($name  == 1) {
                $monthFrom = 1; $monthTo = 6;
            } else {
                $monthFrom = 7; $monthTo = 12;
            }
            $qb
                ->andWhere('MONTH(s.datebegin) BETWEEN :monthFrom and :monthTo')
                ->setParameter('monthFrom', $monthFrom)
                ->setParameter('monthTo', $monthTo);
        } elseif( isset($query_filters['session.semester']) ) {
            if ($query_filters['session.semester']  == 1) {
                $monthFrom = 1; $monthTo = 6;
            } else {
                $monthFrom = 7; $monthTo = 12;
            }
            $qb
                ->andWhere('MONTH(s.datebegin) BETWEEN :monthFrom and :monthTo')
                ->setParameter('monthFrom', $monthFrom)
                ->setParameter('monthTo', $monthTo);
        }

        //FILTRE STATUT D'INSCRIPTION
        if(isset( $aggs['inscriptionStatus.name.source'])) {
            $qb
                ->innerJoin('i.inscriptionstatus', 'istatus', 'WITH', 'istatus = i.inscriptionstatus')
                ->andWhere('istatus.name = :status')
                ->setParameter('status', $name);
        } elseif( isset($query_filters['inscriptionStatus.name.source'])) {
            $qb
                ->innerJoin('i.inscriptionstatus', 'istatus', 'WITH', 'istatus = i.inscriptionstatus')
                ->andWhere('istatus.name = :status')
                ->setParameter('status', $query_filters['inscriptionStatus.name.source']);
        }

        //FILTRE STATUT DE PRESENCE
        if( isset($aggs['presenceStatus.name.source'])) {
            $qb
                ->innerJoin('i.presencestatus', 'pstatus', 'WITH', 'pstatus = i.presencestatus')
                ->andWhere('pstatus.name = :status')
                ->setParameter('status', $name);
        } elseif( isset($query_filters['presenceStatus.name.source'])) {
            $qb
                ->innerJoin('i.presencestatus', 'pstatus', 'WITH', 'pstatus = i.presencestatus')
                ->andWhere('pstatus.name = :status')
                ->setParameter('status', $query_filters['presenceStatus.name.source']);
        }

        //FILTRE ETABLISSEMENT
        if( isset($aggs['institution.name.source'])) {
            $qb
                ->innerJoin('trainee.institution', 'inst', 'WITH', 'trainee.institution = inst')
                ->andWhere('institution.name = :institution')
                ->setParameter('institution',$name);
        }elseif( isset($query_filters['institution.name.source'])) {
            $qb
                ->innerJoin('trainee.institution', 'inst', 'WITH', 'trainee.institution = inst')
                ->andWhere('institution.name = :institution')
                ->setParameter('institution', $query_filters['institution.name.source']);
        }

        //FILTRE CATEGORIE DE PERSONNEL
        if( isset($aggs['publicType.source'])) {
            $qb
                ->innerJoin('trainee.publictype', 'publictype', 'WITH', 'trainee.publictype = publictype')
                ->andWhere('publictype.name = :publictype')
                ->setParameter('publictype', $name);
        }elseif( isset($query_filters['publicType.source'])) {
            $qb
                ->innerJoin('trainee.publictype', 'publictype', 'WITH', 'trainee.publictype = publictype')
                ->andWhere('publictype.name = :publictype')
                ->setParameter('publictype', $query_filters['publicType.source']);
        }

        // On compte le nb de sessions en résultat
        $paginator = new Paginator($qb->getQuery());
        $totalRows = count($paginator);

        return $totalRows;
    }

}

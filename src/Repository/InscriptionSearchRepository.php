<?php

namespace App\Repository;

use App\Entity\Core\Term\Inscriptionstatus;
use App\Entity\Core\Term\Presencestatus;
use App\Entity\Core\Term\Publictype;
use App\Entity\Inscription;
use App\Entity\Institution;
use App\Entity\Session;
use App\Entity\Internship;
use App\Entity\Organization;
use App\Entity\Trainee;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

class InscriptionSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inscription::class);
    }

    public function getInscriptionsList($keyword, $filters,$page, $pageSize)
    {
        $qb = $this->createQueryBuilder('i');
        $qb
            ->select(' i')
            /* Keyword (recherche par mot clé) */
            ->innerJoin('i.session', 's', 'WITH', 's = i.session')
            ->innerJoin('s.training', 'tr', 'WITH', 's.training = tr')
            ->innerJoin('tr.organization', 'o', 'WITH', 'o = tr.organization')
            ->innerJoin('i.inscriptionstatus', 'istatus', 'WITH', 'istatus = i.inscriptionstatus')
            ->innerJoin('i.presencestatus', 'pstatus', 'WITH', 'pstatus = i.presencestatus')
            ->innerJoin('i.trainee', 'trainee', 'WITH', 'trainee = i.trainee')
            ->innerJoin('trainee.institution', 'inst', 'WITH', 'trainee.institution = inst')
            ->innerJoin('trainee.publictype', 'publictype', 'WITH', 'trainee.publictype = publictype')

            // FILTRE KEYWORD
            ->where('trainee.firstname LIKE :keyword')
            ->orWhere('trainee.lastname LIKE :keyword')
            ->andWhere('i.trainee = trainee.id')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');


        // FILTRE CENTRE
        if (isset($filters['session.training.organization.name.source'])) {
            $qb
                ->andWhere('i.session = s.id')
                ->andWhere('s.training = tr.id')
                ->andWhere('tr.organization = o.id')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $filters['session.training.organization.name.source']);
        }

        //FILTRE STATUT D'INSCRIPTION
        if( isset($filters['inscriptionStatus.name.source'])) {
            $qb
                ->andWhere('i.inscriptionstatus = istatus.id')
                ->andWhere('istatus.name = :status')
                ->setParameter('status', $filters['inscriptionStatus.name.source']);
        }

        //FILTRE STATUT DE PRESENCE
        if( isset($filters['presenceStatus.name.source'])) {
            $qb
                ->andWhere('i.presencestatus = pstatus.id')
                ->andWhere('pstatus.name = :status')
                ->setParameter('status', $filters['presenceStatus.name.source']);
        }

        //FILTRE ETABLISSEMENT
        if( isset($filters['institution.name.source'])) {
            $qb
                ->andWhere('i.trainee = trainee.id')
                ->andWhere('trainee.institution = institution.id')
                ->andWhere('institution.name in (:institutions)')
                ->setParameter('institutions', $filters['institution.name.source']);
        }

        //FILTRE CATEGORIE DE PERSONNEL
        if( isset($filters['publicType.source'])) {
            $qb
                ->andWhere('i.trainee = trainee.id')
                ->andWhere('trainee.publictype = publictype.id')
                ->andWhere('publictype.name in (:publictypes)')
                ->setParameter('publictypes', $filters['publicType.source']);
        }

        // FILTRE ANNEE
        if (isset($filters['session.year'])) {
            $qb
                /* On récupère l'année du dateBegin (à l'aide d'une doctrine extension) */
                ->andWhere('i.session = s.id')
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
                ->andWhere('i.session = s.id')
                ->andWhere('MONTH(s.datebegin) BETWEEN :monthFrom and :monthTo')
                ->setParameter('monthFrom', $monthFrom)
                ->setParameter('monthTo', $monthTo);
        }

        //FILTRE DATE
        if( isset($filters['session.dateBegin']) ) {
            /* La date envoyée par le formulaire en JS a un format : "dd/mm/yy - dd/mm/yy" il faut donc séparer les 2 dates */
            $dates = explode('-', $filters["session.dateBegin"]);
            /* on retire les caractères non utiles */
            $from = str_replace('/','-', $dates[0]);
            $to = str_replace('/', '-', $dates[1]);
            /* on convertit au même format qu'en base de données */
            $dateFrom = date('Y/m/d 00:00:00' ,strtotime($from));
            $dateTo = date('Y/m/d 00:00:00',strtotime($to));

            $qb
                /* si la date de début d'une session est entre les 2 dates envoyées dans le formulaire */
                ->andWhere('i.session = s.id')
                ->andWhere("s.datebegin BETWEEN :dateFrom AND :dateTo")
                ->setParameter('dateFrom', $dateFrom)
                ->setParameter('dateTo', $dateTo);
        }

        // TRI DES RESULTATS
        $qb->addOrderBy('i.createdat', 'DESC');

        // PAGINATION
        $offset = ($page-1) * $pageSize;
        $qb->setFirstResult($offset)
            ->setMaxResults($pageSize);

        $query = $qb->getQuery();

        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $c = count($paginator);
        $tabIns = array();
        foreach($paginator as $insc)
            $tabIns[] = $insc;

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
            ->innerJoin('i.session', 's', 'WITH', 's = i.session')
            ->innerJoin('s.training', 'tr', 'WITH', 's.training = tr')
            ->innerJoin('tr.organization', 'o', 'WITH', 'o = tr.organization')
            ->innerJoin('i.inscriptionstatus', 'istatus', 'WITH', 'istatus = i.inscriptionstatus')
            ->innerJoin('i.presencestatus', 'pstatus', 'WITH', 'pstatus = i.presencestatus')
            ->innerJoin('i.trainee', 'trainee', 'WITH', 'trainee = i.trainee')
            ->innerJoin('trainee.institution', 'inst', 'WITH', 'trainee.institution = inst')
            ->innerJoin('trainee.publictype', 'publictype', 'WITH', 'trainee.publictype = publictype')

            // FILTRE KEYWORD
            ->where('trainee.firstname LIKE :keyword')
            ->orWhere('trainee.lastname LIKE :keyword')
            ->andWhere('i.trainee = trainee.id')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');


        // FILTRE CENTRE
        if(isset( $aggs['session.training.organization.name.source'])) {
            $qb
                ->andWhere('i.session = s.id')
                ->andWhere('s.training = tr.id')
                ->andWhere('tr.organization = o.id')
                ->andWhere('o.name = :center')
                ->setParameter('center', $name);
        } elseif (isset($query_filters['session.training.organization.name.source'])) {
            $qb
                ->andWhere('i.session = s.id')
                ->andWhere('s.training = tr.id')
                ->andWhere('tr.organization = o.id')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $query_filters['session.training.organization.name.source']);
        }

        //FILTRE STATUT D'INSCRIPTION
        if(isset( $aggs['inscriptionStatus.name.source'])) {
            $qb
                ->andWhere('i.inscriptionstatus = istatus.id')
                ->andWhere('istatus.name = :status')
                ->setParameter('status', $name);
        } elseif( isset($query_filters['inscriptionStatus.name.source'])) {
            $qb
                ->andWhere('i.inscriptionstatus = istatus.id')
                ->andWhere('istatus.name = :status')
                ->setParameter('status', $query_filters['inscriptionStatus.name.source']);
        }

        //FILTRE STATUT DE PRESENCE
        if( isset($aggs['presenceStatus.name.source'])) {
            $qb
                ->andWhere('i.presencestatus = pstatus.id')
                ->andWhere('pstatus.name = :status')
                ->setParameter('status', $name);
        } elseif( isset($query_filters['presenceStatus.name.source'])) {
            $qb
                ->andWhere('i.presencestatus = pstatus.id')
                ->andWhere('pstatus.name = :status')
                ->setParameter('status', $query_filters['presenceStatus.name.source']);
        }

        //FILTRE ETABLISSEMENT
        if( isset($aggs['institution.name.source'])) {
            $qb
                ->andWhere('i.trainee = trainee.id')
                ->andWhere('trainee.institution = institution.id')
                ->andWhere('institution.name = :institution')
                ->setParameter('institution',$name);
        }elseif( isset($query_filters['institution.name.source'])) {
            $qb
                ->andWhere('i.trainee = trainee.id')
                ->andWhere('trainee.institution = institution.id')
                ->andWhere('institution.name = :institution')
                ->setParameter('institution', $query_filters['institution.name.source']);
        }

        //FILTRE CATEGORIE DE PERSONNEL
        if( isset($aggs['publicType.source'])) {
            $qb
                ->andWhere('i.trainee = trainee.id')
                ->andWhere('trainee.publictype = publictype.id')
                ->andWhere('publictype.name = :publictype')
                ->setParameter('publictype', $name);
        }elseif( isset($query_filters['publicType.source'])) {
            $qb
                ->andWhere('i.trainee = trainee.id')
                ->andWhere('trainee.publictype = publictype.id')
                ->andWhere('publictype.name = :publictype')
                ->setParameter('publictype', $query_filters['publicType.source']);
        }

        // FILTRE ANNEE
        if (isset($aggs['session.year'])) {
            $qb
                ->andWhere('i.session = s.id')
                ->andWhere('YEAR(s.datebegin) = :year')
                ->setParameter('year', $name);
        } elseif (isset($query_filters['session.year'])) {
            $qb
                ->andWhere('i.session = s.id')
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
                ->andWhere('i.session = s.id')
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
                ->andWhere('i.session = s.id')
                ->andWhere('MONTH(s.datebegin) BETWEEN :monthFrom and :monthTo')
                ->setParameter('monthFrom', $monthFrom)
                ->setParameter('monthTo', $monthTo);
        }

        // On compte le nb de sessions en résultat
        $paginator = new Paginator($qb->getQuery());
        $totalRows = count($paginator);

        return $totalRows;
    }

}

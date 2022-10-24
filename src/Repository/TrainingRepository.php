<?php

namespace App\Repository;

use App\Entity\Term\Theme;
use App\Entity\Back\Internship;
use App\Entity\Back\Organization;
use App\Entity\Back\Session;
use App\Entity\Back\Trainer;
use App\Entity\Back\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class TrainingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Internship::class);
    }

    public function getTrainingsList($keyword, $filters, $page, $pageSize)
    {
        $qb = $this->createQueryBuilder('training');
        $qb
            ->select('training')

            // FILTRE KEYWORD
            ->where('training.name LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');


        // FILTRE CENTRE
        if (isset($filters['training.organization.name.source'])) {
            $qb
                ->innerJoin('training.organization', 'o', 'WITH', 'o = training.organization')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $filters['training.organization.name.source']);
        }

        if ((isset($filters['year'])) || ( isset($filters['semester'])) || (isset($filters['nextSession.promote'])) || (isset($filters['trainers.fullName']))) {
            // join sur la session
            $qb->innerJoin(Session::class, 's', 'WITH', 's.training = training');

            // FILTRE ANNEE
            if (isset($filters['year'])) {
                $qb
                    ->andWhere('YEAR(s.datebegin) in (:years)')
                    ->setParameter('years', $filters['year']);
            }

            // FILTRE SEMESTRE
            if( isset($filters['semester']) ) {
                if ($filters['semester']  == 1) {
                    $monthFrom = 1; $monthTo = 6;
                } else {
                    $monthFrom = 7; $monthTo = 12;
                }
                $qb
                    ->andWhere('MONTH(s.datebegin) BETWEEN :monthFrom and :monthTo')
                    ->setParameter('monthFrom', $monthFrom)
                    ->setParameter('monthTo', $monthTo);
            }

            //FILTRE PROMOTION (true,false) = (0,1)
            if (isset($filters['nextSession.promote'])) {
                $qb
                    ->andWhere('s.promote = :promote')
                    ->setParameter('promote', $filters['nextSession.promote']);
            }

            // FILTRE FORMATEUR
            if(isset($filters['trainers.fullName'])) {
                $fullName = explode(" ", $filters['trainers.fullName']);
                $lastName = array_pop($fullName);
                $firstName = array_shift($fullName);
                $qb
                    ->innerJoin(Participation::class, 'p', 'WITH', 'p.session = s')
                    ->innerJoin(Trainer::class, 'trainer', 'WITH', 'trainer = p.trainer')
                    ->andWhere('trainer.lastname = :trainerLastName AND trainer.firstname = :trainerFirstName')
                    ->setParameter('trainerLastName', $lastName)
                    ->setParameter('trainerFirstName', $firstName);
            }
        }

        //FILTRE THEME
        if( isset($filters['theme.name'])) {
            $qb
                ->innerJoin('training.theme', 'th', 'WITH', 'th = training.theme')
                ->andWhere('th.name in (:themes)')
                ->setParameter('themes', $filters['theme.name']);
        }

        //FILTRE CODE numero du stage
        if (isset($filters['training.number'])) {
            $qb
                ->andWhere('training.number = :number')
                ->setParameter('number', $filters['training.number']);
        }

        // TRI DES RESULTATS
        $qb->addOrderBy('training.name');

        // PAGINATION
        $offset = ($page-1) * $pageSize;
        $qb->setFirstResult($offset)
            ->setMaxResults($pageSize);

        $query = $qb->getQuery();

        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $c = count($paginator);
        $tabTrainings = array();

        foreach($paginator as $training) {
            $trainingES = array();
            // On ne garde que les infos du stage dont on a besoin
            $trainingES['sessionscount'] = $training->getSessionscount();
            $trainingES['id'] = $training->getId();
            $trainingES['number'] = $training->getNumber();
            $trainingES['name'] = $training->getName();


            $trainingES['training']['id'] = $training->getId();
            $trainingES['training']['type'] = $training->getType();
            $trainingES['training']['typeLabel'] = $training->getTypeLabel();
            $trainingES['training']['organization'] =$training->getOrganization();
            $trainingES['training']['number'] = $training->getNumber();
            $trainingES['training']['theme'] = $training->getTheme();
            $trainingES['training']['tags'] = $training->getTags();
            $trainingES['training']['name'] = $training->getName();
            $trainingES['training']['program'] = $training->getProgram();
            $trainingES['training']['description'] = $training->getDescription();
            $trainingES['training']['interventionType'] = $training->getInterventionType();
            $trainingES['training']['externalInitiative'] = $training->isExternalInitiative();
            $trainingES['training']['category'] = $training->getCategory();
            $trainingES['training']['comments'] = $training->getComments();
            $trainingES['training']['firstSessionPeriodSemester'] = $training->getFirstSessionPeriodSemester();
            $trainingES['training']['firstSessionPeriodYear'] = $training->getFirstSessionPeriodSemester();
            $trainingES['training']['publictypes'] = $training->getPublicTypes();

            $trainingES['training']['trainers'] = "";
            $i=0;
            foreach ($training->getTrainers() as $trainer) {
                $trainingES['trainers'][]['id'] = $trainer->getId();
                $trainingES['trainers'][]['fullname'] = $trainer->getFullname();
                if($i>0)
                    $trainingES['training']['trainers'] .= ', ' . $trainer->getFullname();
                else
                    $trainingES['training']['trainers'] .= $trainer->getFullname();
                $i++;
            }


            $trainingES['nextsession'] = $training->getNextsession();
            $trainingES['lastsession'] = $training->getLastsession();

            $trainingES['theme'] = $training->getTheme();

            $trainingES['inscriptionsStats'] = array();

            $tabTrainings[] = $trainingES;
        }

        $res = array('total' => $c,
            'pageSize' => $pageSize,
            'items' => $tabTrainings);

        return $res;
    }

    public function getNbTrainings($query_filters, $keyword, $aggs, $name)
    {
        $qb = $this->createQueryBuilder('training');
        $qb
            ->select('training')

            // FILTRE KEYWORD
            ->where('s.name LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');


        // FILTRE CENTRE
        if(isset( $aggs['training.organization.name.source'])) {
            $qb
                ->innerJoin('training.organization', 'o', 'WITH', 'o = training.organization')
                ->andWhere('o.name = :center')
                ->setParameter('center', $name);
        } elseif (isset($query_filters['training.organization.name.source'])) {
            $qb
                ->innerJoin('training.organization', 'o', 'WITH', 'o = training.organization')
                ->andWhere('o.name in (:centers)')
                ->setParameter('centers', $query_filters['training.organization.name.source']);
        }

        if ((isset($aggs['year'])) || (isset($query_filters['year'])) ||
            (isset($aggs['semester'])) || (isset($query_filters['semester'])) ||
            (isset($aggs['nextSession.promote'])) || (isset($query_filters['nextSession.promote'])) ||
            (isset($aggs['trainers.fullName'])) || (isset($query_filters['trainers.fullName']))
        ) {
            // join sur la session
            $qb->innerJoin(Session::class, 's', 'WITH', 's.training = training');

            // FILTRE ANNEE
            if (isset($aggs['year'])) {
                $qb
                    ->andWhere('YEAR(s.datebegin) = :year')
                    ->setParameter('year', $name);
            } elseif (isset($query_filters['year'])) {
                $qb
                    ->andWhere('YEAR(s.datebegin) in (:years)')
                    ->setParameter('years', $query_filters['year']);
            }

            // FILTRE SEMESTRE
            if (isset($aggs['semester'])) {
                if ($name  == 1) {
                    $monthFrom = 1; $monthTo = 6;
                } else {
                    $monthFrom = 7; $monthTo = 12;
                }
                $qb
                    ->andWhere('MONTH(s.datebegin) BETWEEN :monthFrom and :monthTo')
                    ->setParameter('monthFrom', $monthFrom)
                    ->setParameter('monthTo', $monthTo);
            } elseif( isset($query_filters['semester']) ) {
                if ($query_filters['semester']  == 1) {
                    $monthFrom = 1; $monthTo = 6;
                } else {
                    $monthFrom = 7; $monthTo = 12;
                }
                $qb
                    ->andWhere('MONTH(s.datebegin) BETWEEN :monthFrom and :monthTo')
                    ->setParameter('monthFrom', $monthFrom)
                    ->setParameter('monthTo', $monthTo);
            }

            //FILTRE PROMOTION (true,false) = (0,1)
            if(isset( $aggs['nextSession.promote'])) {
                $qb
                    ->andWhere('s.promote = :promote')
                    ->setParameter('promote', $name);
            } elseif( isset($query_filters['nextSession.promote']) ) {
                $qb
                    ->andWhere('s.promote = :promote')
                    ->setParameter('promote', $query_filters['nextSession.promote']);
            }

            // FILTRE FORMATEUR
            if(isset( $aggs['trainers.fullName'])) {
                $qb
                    ->innerJoin(Participation::class, 'p', 'WITH', 'p.session = s')
                    ->innerJoin(Trainer::class, 'trainer', 'WITH', 'trainer = p.trainer')
                    ->andWhere('trainer.id = :id ')
                    ->setParameter('id', $name);
            } elseif( isset($query_filters['trainers.fullName']) ) {
                /* le front envoie un full name (prénom+nom), je le découpe et ne récupère que le nom de famille */
                $fullName = explode(" ", $query_filters['participations.trainer.fullName']);
                $lastName = array_pop($fullName);
                $firstName = array_shift($fullName);
                $qb
                    ->innerJoin(Participation::class, 'p', 'WITH', 'p.session = s')
                    ->innerJoin(Trainer::class, 'trainer', 'WITH', 'trainer = p.trainer')
                    ->andWhere('trainer.lastname = :trainerLastName AND trainer.firstname = :trainerFirstName')
                    ->setParameter('trainerLastName', $lastName)
                    ->setParameter('trainerFirstName', $firstName);
            }
        }

        //FILTRE THEME
        if(isset( $aggs['theme.name'])) {
            $qb
                ->innerJoin('training.theme', 'th', 'WITH', 'th = training.theme')
                ->andWhere('th.name = :theme')
                ->setParameter('theme', $name);
        } elseif (isset($query_filters['theme.name'])) {
            $qb
                ->innerJoin('training.theme', 'th', 'WITH', 'th = training.theme')
                ->andWhere('th.name in (:themes)')
                ->setParameter('themes', $query_filters['theme.name']);
        }

        // On compte le nb de sessions en résultat
        $paginator = new Paginator($qb->getQuery());
        $totalRows = count($paginator);

        return $totalRows;
    }

}

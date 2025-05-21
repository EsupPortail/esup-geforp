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

final class TrainingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Internship::class);
    }

    /**
     * @return array{total: int, pageSize: mixed, items: array<int, array{id: mixed, name: mixed, number: mixed, sessionscount: mixed, trainers?: array{fullname: mixed}[]|array{id: mixed}[]&mixed[], training: array{id: mixed, type: mixed, typeLabel: mixed, organization: mixed, number: mixed, theme: mixed, tags: mixed, name: mixed, program: mixed, description: mixed, interventionType: mixed, externalInitiative: mixed, category: mixed, comments: mixed, firstSessionPeriodSemester: mixed, firstSessionPeriodYear: mixed, publictypes: mixed, trainers: string}, nextsession: mixed, lastsession: mixed, theme: mixed, inscriptionsStats: never[]}>}
     */
    public function getTrainingsList($keyword, $filters, $page, $pageSize, $sorts): array
    {
        /* addcslashes empêchera des manipulations malveillantes éventuelles */
        $keywordPr = '%' . addcslashes((string) $keyword, '%_') . '%';
        $qb = $this->createQueryBuilder('training');
        $qb
            ->select('training')

            // FILTRE KEYWORD
            ->where('training.name LIKE :keywordPr OR training.number = :keyword')
            ->setParameter('keywordPr', $keywordPr)
            ->setParameter('keyword', $keyword);

        // Filtre keyword sur les tags
        $qb
            ->leftJoin('training.tags', 'tag')
            ->orWhere('tag.name LIKE :tagName')
            ->setParameter('tagName', $keywordPr);

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
                $fullName = explode(" ", (string) $filters['trainers.fullName']);
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
        if ((is_array($sorts)) && (array_key_exists('training.name.source', $sorts)))
            $qb->addOrderBy('training.name', $sorts['training.name.source']);
        elseif ((is_array($sorts)) && (array_key_exists('training.number', $sorts)))
            $qb->addOrderBy('training.number', $sorts['training.number']);
        elseif ((is_array($sorts)) && (array_key_exists('training.category.source', $sorts))) {
            if(!isset($filters['training.category.source']))
                $qb->innerJoin('training.category', 'category', 'WITH', 'training.category = category');

            $qb->addOrderBy('category.name', $sorts['training.category.source']);
        } else
            $qb->addOrderBy('training.name');

        // PAGINATION
        $page = (int) $page;
        $pageSize = (int) $pageSize;
        $offset = ($page -1) * $pageSize;
        $qb->setFirstResult($offset)
            ->setMaxResults($pageSize);

        $query = $qb->getQuery();

        $paginator = new Paginator($query, $fetchJoinCollection = true);

        $c = count($paginator);
        $tabTrainings = [];

        foreach($paginator as $training) {
            $trainingES = [];
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

                ++$i;
            }


            $trainingES['nextsession'] = $training->getNextsession();
            $trainingES['lastsession'] = $training->getLastsession();

            $trainingES['theme'] = $training->getTheme();

            $trainingES['inscriptionsStats'] = [];

            $tabTrainings[] = $trainingES;
        }

        return ['total' => $c, 'pageSize' => $pageSize, 'items' => $tabTrainings];
    }

    public function getNbTrainings($query_filters, $keyword, $aggs, $name): int
    {
        $qb = $this->createQueryBuilder('training');
        $qb
            ->select('training')

            // FILTRE KEYWORD
            ->where('training.name LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes((string) $keyword, '%_') . '%');


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
                $fullName = explode(" ", (string) $query_filters['participations.trainer.fullName']);
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

        return count($paginator);
    }

}

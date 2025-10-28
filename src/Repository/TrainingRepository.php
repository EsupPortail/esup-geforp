<?php

namespace App\Repository;

use App\Entity\Core\AbstractTraining;
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
    public function getTrainingsList($keyword,
                                     array $filters = [],
                                     int $page = 1,
                                     int $pageSize = 1,
                                     array $sorts = ['createdat' => 'DESC']): array
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
            if( isset($filters['semester']) && isset($filters['year']) ) {
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

        foreach ($paginator as $training) {
            // Préparation de la liste des formateurs + chaîne de noms concaténés
            $trainerList = [];
            $trainerNames = [];

            foreach ($training->getTrainers() as $trainer) {
                $trainerList[] = [
                    'id' => $trainer->getId(),
                    'fullName' => $trainer->getFullname(),
                ];
                $trainerNames[] = $trainer->getFullname();
            }

            // Tentative de récupération de la prochaine session
            $nextSession = $training->getNextsession();
            $year = null;
            $semester = null;

            if ($nextSession) {
                if (method_exists($nextSession, 'getYear') && $nextSession->getYear()) {
                    $year = $nextSession->getYear();
                }
                if (method_exists($nextSession, 'getSemesterLabel')) {
                    $semester = $nextSession->getSemesterLabel();
                }
            }

            // Si elle existe, on récupère le semestre depuis cette session


            $sessionData = [];
            foreach ($training->getSessions() as $session) {
                $sessionData[] = [
                    'id' => $session->getId(),
                    'datebegin' => $session->getDatebegin(),
                    'dateend' => $session->getDateend(),
                    'year' => $session->getYear(),
                    'semester' => $session->getSemester(),
                    'type' => 'session',
                    'numberofregistrations' => $session->getNumberofregistrations(),
                    'numberofacceptedregistrations' => $session->getNumberofacceptedregistrations(),
                    'maximumnumberofregistrations' => $session->getMaximumnumberofregistrations(),
                    'numberofparticipants' => count($session->getParticipations()),
                ];
            }

            $semester = null;
            $years = null;
            if ($nextSession && method_exists($nextSession, 'getSemesterLabel')) {
                $semester = $nextSession->getSemesterLabel();
            }

            if ($nextSession && method_exists($nextSession, 'getYear') && $nextSession->getYear()) {
                $years = $nextSession->getYear();
            }

            $items[] = [
                'id' => $training->getId(),
                'number' => $training->getNumber(),
                'name' => $training->getName(),
                'theme' => $training->getTheme(),
                'sessionscount' => $training->getSessionscount(),
                'semester' => $semester,
                'nextsession' => $training->getNextsession(),
                'lastsession' => $training->getLastsession(),
                'trainers' => $trainerList,
                'sessions' => $sessionData,
                'inscriptionsStats' => [], // à compléter si nécessaire
                'year' => $years,
                'description' => $training->getDescription(),
                'program' => $training->getProgram(),
                'tags' => $training->getTags(),
                'training' => [
                    'id' => $training->getId(),
                    'type' => $training->getType(),
                    'typeLabel' => $training->getTypeLabel(),
                    'organization' => $training->getOrganization(),
                    'number' => $training->getNumber(),
                    'name' => $training->getName(),
                    'theme' => $training->getTheme(),
                    'tag' => $training->getTags(),
                    'program' => $training->getProgram(),
                    'description' => $training->getDescription(),
                    'interventionType' => $training->getInterventionType(),
                    'externalInitiative' => $training->isExternalInitiative(),
                    'category' => $training->getCategory(),
                    'comments' => $training->getComments(),
                    'firstSessionPeriodSemester' => $training->getFirstSessionPeriodSemester(),
                    'firstSessionPeriodYear' => $training->getFirstSessionPeriodYear(),
                    'publictypes' => $training->getPublicTypes(),
                    'trainers' => implode(', ', $trainerNames),
                ],
            ];


    }


        return ['total' => $c, 'pageSize' => $pageSize, 'items' => $items];
    }

    public function getNbTrainings(array $query_filters = [], ?string $keyword = '', array $aggs = [], mixed $name = "", $facet = null): array
    {
        $qb = $this->createQueryBuilder('training');
        $qb
            ->select('COUNT(DISTINCT training)')
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
            $qb->innerJoin('training.sessions', 's');

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
                $semester = (int) $name;
                if ($semester  == 1) {
                    $monthFrom = 1; $monthTo = 6;
                } else {
                    $monthFrom = 7; $monthTo = 12;
                }

                $qb
                    ->andWhere('MONTH(s.datebegin) BETWEEN :monthFrom and :monthTo')
                    ->setParameter('monthFrom', $monthFrom)
                    ->setParameter('monthTo', $monthTo);
            } elseif( isset($query_filters['session.semester']) ) {
                $semester = (int) $query_filters['session.semester'];
                if ($semester  == 1) {
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

            //FILTRE NUMÉRO
        if (isset($aggs['training.number'])) {
            $qb->andWhere('training.number = :number')
                ->setParameter('number', $name);

        } elseif (!empty($query_filters['training.number'])) {
            $values = (array) $query_filters['training.number'];

            if (count($values) === 1) {
                $qb->andWhere('training.number = :number')
                    ->setParameter('number', reset($values));
            } else {
                $qb->andWhere('training.number IN (:numbers)')
                    ->setParameter('numbers', $values);
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

        // FILTRE TYPE
        if(isset( $aggs['training.typeLabel.source'])) {
            $qb
                ->leftJoin('training.category', 'c')
                ->andWhere('c.name  = :type')
                ->setParameter('type', $name);
        } elseif( isset($query_filters['training.typeLabel.source']) ){
            $qb
                ->leftJoin('training.category', 'c')
                ->andWhere('c.name IN (:types)')
                ->setParameter('types', $query_filters['training.typeLabel.source']);
        }

        //FILTRE CATEGORY
        if ($facet === 'training.category' && $name !== null) {
            $qb->innerJoin('training.category', 'c')
                ->andWhere('c.name = :category')
                ->setParameter('category', $name);
        } elseif (!empty($query_filters['training.category'])) {
            $categories = (array) $query_filters['training.category'];
            $qb->innerJoin('training.category', 'c')
                ->andWhere('c.name IN (:categorys)')
                ->setParameter('categorys', $categories);
        }

        // On compte le nb de sessions en résultat

        $total = (int) $qb->getQuery()->getSingleScalarResult();

        return [
            'total' => $total,
            'items' => [],
        ];
    }

}

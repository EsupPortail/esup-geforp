<?php

namespace App\Repository;

use App\Entity\Core\AbstractSession;
use App\Entity\Back\Session;
use App\Entity\Term\Presencestatus;
use App\Entity\Term\Theme;
use App\Entity\Back\Internship;
use App\Entity\Back\Organization;
use App\Entity\Back\Trainer;
use App\Entity\Back\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

final class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Session::class);
    }

    public function getSessionsProgram($keyword, $filters)
    {
        $qb = $this->createQueryBuilder('s');
        $qb
            ->select(' s')
            ->innerJoin('s.training', 'tr', 'WITH', 'tr = s.training')
            ->innerJoin('tr.organization', 'o', 'WITH', 'o = tr.organization')
            ->innerJoin('tr.theme', 'th', 'WITH', 'th = tr.theme');

        // FILTRE KEYWORD
        if ($keyword != 'NO KEYWORDS') {
            $qb
                ->where('s.name LIKE :keyword')
                /* addcslashes empêchera des manipulations malveillantes éventuelles */
                ->setParameter('keyword', '%' . addcslashes((string) $keyword, '%_') . '%');
        }

        // FILTRE DISPLAYONLINE pour affichage stagiaire
        $qb->andWhere('s.displayonline = 1');

        // FILTRE CENTRE
        if (isset($filters['training.organization.name.source'])) {
            $qb
                ->andWhere('s.training = tr.id')
                ->andWhere('tr.organization = o.id')
                ->andWhere('o.code in (:centers)')
                ->setParameter('centers', $filters['training.organization.name.source']);
        }

        //FILTRE DATE
        if( isset($filters['datebegin']) ) {
            /* La date envoyée par le formulaire en JS a un format : "dd/mm/yy - dd/mm/yy" il faut donc séparer les 2 dates */
            $dates = explode('-', (string) $filters["datebegin"]);
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

        //FILTRE THEME
        if( isset($filters['theme.name'])) {
            $qb
                ->andWhere('s.training = tr.id')
                ->andWhere('tr.theme = th.id')
                ->andWhere('th.name in (:themes)')
                ->setParameter('themes', $filters['theme.name']);
        }

        // TRI DES RESULTATS
        $qb->addOrderBy('th.name')
            ->addOrderBy('s.datebegin')
            ->addOrderBy('s.name');

        $query = $qb->getQuery();

        return $result = $query->getResult();
    }

    /**
     * @return array{total: int, pageSize: mixed, items: array<int, array{availablePlaces?: mixed, datebegin?: mixed, dateend?: mixed, daynumber?: mixed, displayonline?: mixed, hournumber?: mixed, id: mixed, inscriptions?: array<int, array{id: mixed}>&mixed[], inscriptionStats?: array<int, array{id: mixed, name: mixed, status: mixed, count: int}>, limitRegistrationDate?: mixed, maximumnumberofregistrations?: mixed, name?: mixed, numberofacceptedregistrations?: mixed, numberofparticipants?: mixed, numberofregistrations?: mixed, participations?: array<int, array{id: mixed}>&mixed[], promote?: mixed, registrable?: mixed, registration?: mixed, semester?: mixed, semesterLabel?: mixed, sessiontype?: mixed, status?: mixed, theme?: mixed, training?: array{id: mixed, type: mixed, name: mixed, typeLabel: mixed, organization: mixed, number: mixed, theme: mixed, tags: mixed, program: mixed, description: mixed, interventionType: mixed, externalInitiative: mixed, category: mixed, comments: mixed, firstSessionPeriodSemester: mixed, firstSessionPeriodYear: mixed, publictypes: mixed}, year?: mixed}>}
     */
    public function getSessionsList($keyword, $filters, $page, $pageSize, $sorts, $fields): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb
            ->select(' s');

        // FILTRE KEYWORD
        $qb
            ->where('s.name LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes((string)$keyword, '%_') . '%');


        $qb->leftJoin('s.participations', 'p')
            ->leftJoin('p.trainer', 'trainer')
            ->addSelect('p, trainer');

        if ((isset($filters['training.organization.name.source'])) ||
            (isset($filters['theme.name']))
        ) {
            // join sur training
            $qb->innerJoin('s.training', 'tr', 'WITH', 'tr = s.training');

            // FILTRE CENTRE
            if (isset($filters['training.organization.name.source'])) {
                $qb
                    ->innerJoin('tr.organization', 'o', 'WITH', 'o = tr.organization')
                    ->andWhere('o.name in (:centers)')
                    ->setParameter('centers', $filters['training.organization.name.source']);
            }

            //FILTRE THEME
            if (isset($filters['theme.name'])) {
                $qb
                    ->innerJoin('tr.theme', 'th', 'WITH', 'th = tr.theme')
                    ->andWhere('th.name in (:themes)')
                    ->setParameter('themes', $filters['theme.name']);
            }
        }


        // FILTRE ANNEE
        if (isset($filters['year'])) {
            $qb
                /* On récupère l'année du dateBegin (à l'aide d'une doctrine extension) */
                ->andWhere('YEAR(s.datebegin) in (:years)')
                ->setParameter('years', $filters['year']);
        }

        // FILTRE SEMESTRE
        if (isset($filters['semester'])) {
            if ($filters['semester'] == 1) {
                $monthFrom = 1;
                $monthTo = 6;
            } else {
                $monthFrom = 7;
                $monthTo = 12;
            }

            $qb
                ->andWhere('MONTH(s.datebegin) BETWEEN :monthFrom and :monthTo')
                ->setParameter('monthFrom', $monthFrom)
                ->setParameter('monthTo', $monthTo);
        }

        //FILTRE DATE
        if (isset($filters['datebegin'])) {
            /* La date envoyée par le formulaire en JS a un format : "dd/mm/yy - dd/mm/yy" il faut donc séparer les 2 dates */
            $dates = explode('-', (string)$filters["datebegin"]);
            /* on retire les caractères non utiles */
            $from = str_replace('/', '-', $dates[0]);
            $to = str_replace('/', '-', $dates[1]);
            /* on convertit au même format qu'en base de données */
            $dateFrom = date('Y/m/d 00:00:00', strtotime($from));
            $dateTo = date('Y/m/d 00:00:00', strtotime($to));

            $qb
                /* si la date de début d'une session est entre les 2 dates envoyées dans le formulaire */
                ->andWhere("s.datebegin BETWEEN :dateFrom AND :dateTo")
                ->setParameter('dateFrom', $dateFrom)
                ->setParameter('dateTo', $dateTo);
        }

        // FILTRE INSCRIPTION (0,1,2,3)
        if (isset($filters['registration'])) {
            $qb
                ->andWhere('s.registration in (:registrations)')
                ->setParameter('registrations', $filters['registration']);
        }

        // FILTRE STATUT (0,1,2)
        if (isset($filters['status'])) {
            $qb
                ->andWhere('s.status in (:status)')
                ->setParameter('status', $filters['status']);
        }

        // FILTRE DISPLAYONLINE (F,T) ou (0,1) ?
        if (isset($filters['displayOnline'])) {
            $qb
                ->andWhere('s.displayonline = :displayOnline')
                ->setParameter('displayOnline', $filters['displayOnline']);
        }

        // FILTRE FORMATION (nom de la formation)
        if (isset($filters['training.name.source'])) {
            $qb
                ->andWhere('s.name in (:trainings)')
                ->setParameter('trainings', $filters['training.name.source']);
        }

        //FILTRE PROMOTION (true,false) = (0,1)
        if (isset($filters['promote'])) {
            $qb
                ->andWhere('s.promote = :promote')
                ->setParameter('promote', $filters['promote']);
        }

        // FILTRE FORMATEUR
        if (isset($filters['participations.trainer.fullName'])) {
            /* le front envoie un full name (prénom+nom), je le découpe et ne récupère que le nom de famille */
            $fullName = explode(" ", (string)$filters['participations.trainer.fullName']);
            $lastName = array_pop($fullName);
            $firstName = array_shift($fullName);
            $qb
                ->innerJoin(Participation::class, 'p', 'WITH', 'p.session = s')
                ->innerJoin(Trainer::class, 'trainer', 'WITH', 'trainer = p.trainer')
                ->andWhere('trainer.lastname = :trainerLastName AND trainer.firstname = :trainerFirstName')
                ->setParameter('trainerLastName', $lastName)
                ->setParameter('trainerFirstName', $firstName);
        }

        // TRI DES RESULTATS
        if (isset($sorts['training.name.source']))
            $qb->addOrderBy('s.name', $sorts['training.name.source']);
        elseif (isset($sorts['datebegin']))
            $qb->addOrderBy('s.datebegin', $sorts['datebegin']);
        else
            $qb->addOrderBy('s.datebegin')
                ->addOrderBy('s.name');

        // PAGINATION
        $offset = ($page - 1) * $pageSize;
        $qb->setFirstResult($offset)
            ->setMaxResults($pageSize);

        $query = $qb->getQuery();

        $paginator = new Paginator($query, true);

        $c = count($paginator);
        $tabSession = [];

        foreach ($paginator as $session) {
            if (is_array($fields) && in_array("_id", $fields)) {
                // Si on ne veut que les IDs
                $tabSession[] = ['id' => $session->getId()];
            } else {
                // Extraire les infos principales de la session
                $sessionES = [
                    'id' => $session->getId(),
                    'name' => $session->getName(),
                    'datebegin' => $session->getDatebegin(),
                    'dateend' => $session->getDateend(),
                    'hournumber' => $session->getHournumber(),
                    'daynumber' => $session->getDaynumber(),
                    'year' => $session->getYear(),
                    'semester' => $session->getSemester(),
                    'semesterLabel' => $session->getSemesterLabel(),
                    'limitRegistrationDate' => $session->getLimitregistrationdate(),
                    'maximumNumberOfRegistrations' => $session->getMaximumNumberOfRegistrations(),
                    'numberofregistrations' => $session->getMaximumNumberOfRegistrations(),
                    'numberofacceptedregistrations' => $session->getNumberofacceptedregistrations(),
                    'registrable' => $session->isRegistrable(),
                    'registration' => $session->getRegistration(),
                    'status' => $session->getStatus(),
                    'displayonline' => $session->getDisplayonline(),
                    'sessiontype' => $session->getSessiontype(),
                    'availablePlaces' => $session->getAvailablePlaces(),
                    'promote' => $session->getPromote(),
                    'theme' => $session->getTraining()->getTheme(),
                    'numberofparticipants' => $session->getNumberofregistrations(),
                ];

                if (method_exists($session, 'getStatus')) {
                    $sessionES['status'] = $session->getStatus();
                }

                if (method_exists($session, 'getNumberofparticipants')) {
                    $sessionES['numberofparticipants'] = $session->getNumberofregistrations();
                }

                if (method_exists($session, 'getMaximumNumberOfRegistrations')) {
                    $sessionES['maximumNumberOfRegistrations'] = $session->getMaximumNumberOfRegistrations();
                }

                $inscriptions = $session->getInscriptions();
                $sessionES['inscriptions'] = [];

                foreach ($inscriptions as $insc) {
                    $sessionES['inscriptions'][] = [
                        'id' => $insc->getId(),
                        'presencestatus' => $insc->getPresencestatus()?->getStatus() ?? null,
                    ];
                }

                // Infos training
                $training = $session->getTraining();
                $sessionES['training'] = [
                    'id' => $training->getId(),
                    'type' => $training->getType(),
                    'name' => $training->getName(),
                    'typeLabel' => $training->getTypeLabel(),
                    'organization' => $training->getOrganization(),
                    'number' => $training->getNumber(),
                    'theme' => $training->getTheme(),
                    'tags' => $training->getTags(),
                    'program' => $training->getProgram(),
                    'description' => $training->getDescription(),
                    'interventionType' => $training->getInterventionType(),
                    'externalInitiative' => $training->isExternalInitiative(),
                    'category' => $training->getCategory(),
                    'comments' => $training->getComments(),
                    'firstSessionPeriodSemester' => $training->getFirstSessionPeriodSemester(),
                    'firstSessionPeriodYear' => $training->getFirstSessionPeriodYear(),
                    'publictypes' => $training->getPublicTypes(),
                ];

                // Statistiques des inscriptions
                $statsInsc = [];
                $sessionES['inscriptions'] = [];
                foreach ($session->getInscriptions() as $insc) {
                    $sessionES['inscriptions'][] = ['id' => $insc->getId()];
                    $statusId = $insc->getInscriptionStatus()->getId();
                    $found = false;
                    foreach ($statsInsc as &$statInsc) {
                        if ($statInsc['id'] === $statusId) {
                            $statInsc['count']++;
                            $found = true;
                            break;
                        }
                    }
                    unset($statInsc);
                    if (!$found) {
                        $statsInsc[] = [
                            'id' => $statusId,
                            'name' => $insc->getInscriptionStatus()->getName(),
                            'status' => $insc->getInscriptionStatus()->getStatus(),
                            'count' => 1,
                        ];
                    }
                }
                $sessionES['inscriptionStats'] = $statsInsc;

                // Trainers
                $participations = [];
                foreach ($session->getParticipations() as $participation) {
                    $trainer = $participation->getTrainer();
                    $participations[] = [
                        'id' => $participation->getId(),
                        'fullname' => $participation->getTrainer()->getFullname(),

                        'trainer' => [
                            'id' => $trainer->getId(),
                            'firstname' => $trainer->getFirstname(),
                            'lastname' => $trainer->getLastname(),
                            'fullname' => $trainer->getFullName(),
                        ],
                    ];
                }
                $sessionES['participations'] = $participations;
                $tabSession[] = $sessionES;
            }
        }

        return ['total' => $c, 'pageSize' => $pageSize, 'items' => $tabSession];
    }

    public function getNbSessions($query_filters, $keyword, $aggs, $name): int
    {
        $qb = $this->createQueryBuilder('s');
        $qb
            ->select(' s');

            // FILTRE KEYWORD
        $qb
            ->where('s.name LIKE :keyword')
            /* addcslashes empêchera des manipulations malveillantes éventuelles */
            ->setParameter('keyword', '%' . addcslashes((string) $keyword, '%_') . '%');

        if ((isset( $aggs['training.organization.name.source'])) || (isset($query_filters['training.organization.name.source'])) ||
            (isset( $aggs['theme.name'])) || (isset($query_filters['theme.name']))
        ) {
            // join sur training
            $qb->innerJoin('s.training', 'tr', 'WITH', 'tr = s.training');

            // FILTRE CENTRE
            if(isset( $aggs['training.organization.name.source'])) {
                $qb
                    ->innerJoin('tr.organization', 'o', 'WITH', 'o = tr.organization')
                    ->andWhere('o.name = :center')
                    ->setParameter('center', $name);
            } elseif (isset($query_filters['training.organization.name.source'])) {
                $qb
                    ->innerJoin('tr.organization', 'o', 'WITH', 'o = tr.organization')
                    ->andWhere('o.name in (:centers)')
                    ->setParameter('centers', $query_filters['training.organization.name.source']);
            }

            //FILTRE THEME
            if(isset( $aggs['theme.name'])) {
                $qb
                    ->innerJoin('tr.theme', 'th', 'WITH', 'th = tr.theme')
                    ->andWhere('th.name = :theme')
                    ->setParameter('theme', $name);
            } elseif (isset($query_filters['theme.name'])) {
                $qb
                    ->innerJoin('tr.theme', 'th', 'WITH', 'th = tr.theme')
                    ->andWhere('th.name in (:themes)')
                    ->setParameter('themes', $query_filters['theme.name']);
            }
        }

        // FILTRE ANNEE
        if (isset($aggs['year'])) {
            $qb
                ->andWhere('YEAR(s.datebegin) = :year')
                ->setParameter('year', $name);
        } elseif (isset($query_filters['year'])) {
            $qb
                /* On récupère l'année du dateBegin (à l'aide d'une doctrine extension) */
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

        //FILTRE DATE
        if( isset($query_filters['datebegin']) ) {
            /* La date envoyée par le formulaire en JS a un format : "dd/mm/yy - dd/mm/yy" il faut donc séparer les 2 dates */
            $dates = explode('-', (string) $query_filters["datebegin"]);
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

        // FILTRE INSCRIPTION (0,1,2,3)
        if(isset( $aggs['registration'])) {
            $qb
                ->andWhere('s.registration = :registration')
                ->setParameter('registration', $name);
        } elseif( isset($query_filters['registration']) ) {
            $qb
                ->andWhere('s.registration in (:registrations)')
                ->setParameter('registrations', $query_filters['registration']);
        }

        // FILTRE STATUT (0,1,2)
        if(isset( $aggs['status'])) {
            $qb
                ->andWhere('s.status = :status')
                ->setParameter('status', $name);
        } elseif( isset($query_filters['status']) ){
            $qb
                ->andWhere('s.status in (:status)')
                ->setParameter('status', $query_filters['status']);
        }

        // FILTRE DISPLAYONLINE (F,T) ou (0,1) ?
        if(isset( $aggs['displayOnline'])) {
            $qb
                ->andWhere('s.displayonline = :displayOnline')
                ->setParameter('displayOnline', $name);
        } elseif( isset($query_filters['displayOnline']) ){
            $qb
                ->andWhere('s.displayonline = :displayOnline')
                ->setParameter('displayOnline', $query_filters['displayOnline']);
        }

        // FILTRE FORMATION (nom de la formation)
        if(isset( $aggs['training.name.source'])) {
            $qb
                ->andWhere('s.name = :training')
                ->setParameter('training', $name);
        } elseif( isset($query_filters['training.name.source']) ) {
            $qb
                ->andWhere('s.name in (:trainings)')
                ->setParameter('trainings', $query_filters['training.name.source']);
        }

        //FILTRE PROMOTION (true,false) = (0,1)
        if(isset( $aggs['promote'])) {
            $qb
                ->andWhere('s.promote = :promote')
                ->setParameter('promote', $name);
        } elseif( isset($query_filters['promote']) ) {
            $qb
                ->andWhere('s.promote = :promote')
                ->setParameter('promote', $query_filters['promote']);
        }

        // FILTRE FORMATEUR
        if(isset( $aggs['participations.trainer.fullName'])) {
            $qb
                ->innerJoin(Participation::class, 'p', 'WITH', 'p.session = s')
                ->innerJoin(Trainer::class, 'trainer', 'WITH', 'trainer = p.trainer')
                ->andWhere('trainer.id = :id ')
                ->setParameter('id', $name);
        } elseif( isset($query_filters['participations.trainer.fullName']) ) {
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

        // On compte le nb de sessions en résultat
        $paginator = new Paginator($qb->getQuery());

        return count($paginator);
    }

}

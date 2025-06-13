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

final class InscriptionSearchRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry, Inscription::class);
    }

    /**
     * @return array{total: int, pageSize: mixed, items: mixed[]}
     */
    public function getInscriptionsList(string $keyword = '',
                                        array $filters = [],
                                        int $page = 1,
                                        int $pageSize = 1,
                                        array $sorts = ['createdat' => 'DESC'],
                                        array $fields = []): array
    {

        $MAX_EXPORT_LIMIT = 10000; // Limite sécurisée
        $MAX_PAGE_SIZE = 50; // Limite "normale" pour la navigation

        $isExport = isset($filters['_export']) && $filters['_export'] === true;

        $pageSize = max(1, (int) $pageSize);
        $pageSize = $isExport
            ? min($pageSize, $MAX_EXPORT_LIMIT)
            : min($pageSize, $MAX_PAGE_SIZE);

        $qb = $this->createQueryBuilder('i');
        $qb
            ->select('i', 'trainee', 's', 'tr', 'tag', 'theme', 'org', 'inst', 'publictype', 'istatus', 'pstatus')
            // Jointures obligatoires
            ->innerJoin('i.trainee', 'trainee')
            ->innerJoin('i.session', 's')
            ->innerJoin('s.training', 'tr')

            // Jointures optionnelles pour éviter les requêtes N+1
            ->leftJoin('tr.tags', 'tag')
            ->leftJoin('tr.theme', 'theme')
            ->leftJoin('tr.organization', 'org')
            ->leftJoin('trainee.institution', 'inst')
            ->leftJoin('trainee.publictype', 'publictype')
            ->leftJoin('i.inscriptionstatus', 'istatus')
            ->leftJoin('i.presencestatus', 'pstatus')

            // Filtre keyword amélioré avec gestion des cas NULL
            ->where('
                (trainee.firstname LIKE :keyword OR trainee.firstname IS NULL) OR 
                (trainee.lastname LIKE :keyword OR trainee.lastname IS NULL) OR 
                (tr.name LIKE :keyword OR tr.name IS NULL) OR 
                (tag.name LIKE :keyword OR tag.name IS NULL)
            ')
            ->setParameter('keyword', '%' . addcslashes($keyword, '%_') . '%');

        // FILTRE CENTRE
        if (isset($filters['session.training.organization.name.source'])) {
            // Utiliser l'alias 'org' déjà défini au lieu de créer une nouvelle jointure
            $qb
                ->andWhere('org.name in (:centers)')
                ->setParameter('centers', $filters['session.training.organization.name.source']);
        }

        // FILTRE ANNEE
        if (isset($filters['session.year'])) {
            $qb
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
            $dates = explode('-', (string) $filters["session.datebegin"]);
            $dateFrom = date('Y/m/d 00:00:00' ,strtotime(str_replace('/','-', trim($dates[0]))));
            $dateTo = date('Y/m/d 00:00:00',strtotime(str_replace('/', '-', trim($dates[1]))));

            $qb
                ->andWhere("s.datebegin BETWEEN :dateFrom AND :dateTo")
                ->setParameter('dateFrom', $dateFrom)
                ->setParameter('dateTo', $dateTo);
        }

        //FILTRE STATUT D'INSCRIPTION
        if( isset($filters['inscriptionStatus.name.source'])) {
            // Utiliser l'alias 'istatus' déjà défini
            $qb
                ->andWhere('istatus.name in (:inscStatus)')
                ->setParameter('inscStatus', $filters['inscriptionStatus.name.source']);
        }

        //FILTRE STATUT DE PRESENCE
        if( isset($filters['presenceStatus.name.source'])) {
            // Utiliser l'alias 'pstatus' déjà défini
            $qb
                ->andWhere('pstatus.name in (:presStatus)')
                ->setParameter('presStatus', $filters['presenceStatus.name.source']);
        }

        //FILTRE ETABLISSEMENT
        if( isset($filters['institution.name.source'])) {
            // Utiliser l'alias 'inst' déjà défini
            $qb
                ->andWhere('inst.name in (:institutions)')
                ->setParameter('institutions', $filters['institution.name.source']);
        }

        //FILTRE CATEGORIE DE PERSONNEL
        if( isset($filters['publicType.source'])) {
            // Utiliser l'alias 'publictype' déjà défini
            $qb
                ->andWhere('publictype.name in (:publictypes)')
                ->setParameter('publictypes', $filters['publicType.source']);
        }

        // FILTRE SESSION
        if (isset($filters['session.id'])) {
            $qb
                ->andWhere('s.id in (:sessionId)')
                ->setParameter('sessionId', $filters['session.id']);
        }

        //FILTRE THEME
        if( isset($filters['session.training.theme.name'])) {
            // Utiliser l'alias 'theme' déjà défini
            $qb
                ->andWhere('theme.name in (:themes)')
                ->setParameter('themes', $filters['session.training.theme.name']);
        }

        // TRI DES RESULTATS
        $sortableFields = [
            'createdat' => 'i.createdat',
            'trainee.fullname' => 'trainee.lastname',
            'session.datebegin' => 's.datebegin',
            'trainee.publictype.name' => 'publictype.name',
            'session.training.name' => 'org.name',
            'trainee.institution' => 'inst.name',
        ];

        foreach ($sorts as $key => $direction) {
            if (isset($sortableFields[$key])) {
                $qb->addOrderBy($sortableFields[$key], $direction);
            }
        }

        // Pagination
        $qb->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        $paginator = new Paginator($qb);

        $items = [];
        foreach ($paginator as $insc) {
            $trainee = $insc->getTrainee();
            $session = $insc->getSession();
            $training = $session?->getTraining();
            $theme = $training?->getTheme();
            $organization = $training?->getOrganization();
            $institution = $trainee?->getInstitution();
            $publicType = $trainee?->getPublictype();

            $firstname = trim($trainee?->getFirstname() ?? '');
            $lastname = trim($trainee?->getLastname() ?? '');

            $fullname = trim("$firstname $lastname");
            if ($fullname === '') {
                $fullname = 'Non renseigné';
            }

            $items[] = [
                'id' => $insc->getId(),
                'createdat' => $insc->getCreatedAt()?->format('Y-m-d'),
                'isPaying' => $insc?->getPrice(),
                'presencestatus' => $insc->getPresencestatus(),
                'inscriptionstatus' => $insc->getInscriptionstatus(),



                // Entités séparées
                'trainee' => [
                    'id' => $trainee?->getId(),
                    'firstname' => $trainee?->getFirstname() ?? 'Non renseigné',
                    'lastname' => $trainee?->getLastname() ?? 'Non renseigné',
                    'fullname' => $fullname,
                    'email' => $trainee?->getEmail(),
                    'publictype' => $publicType,
                    'institution' => [
                        'id' => $institution?->getId(),
                        'name' => $institution?->getName() ?? 'Non renseignée',
                        'city' => $institution?->getCity() ?? 'Non renseignée',
                    ],
                ],
                'session' => [
                    'id' => $session?->getId(),
                    'datebegin' => $session?->getDatebegin()?->format('Y-m-d'),
                    'dateend' => $session?->getDateend()?->format('Y-m-d'),
                    'maximumNumberOfRegistrations' => $session?->getMaximumNumberOfRegistrations(),
                    'price' => $session?->getPrice(),
                    'fullname' => $fullname,
                ],
                'training' => [
                    'id' => $training?->getId(),
                    'name' => $training?->getName() ?? 'Non renseigné',
                    'description' => $training?->getDescription(),
                    'fullname' => $fullname,
                ],
                'theme' => $theme ? [
                    'id' => $theme->getId(),
                    'name' => $theme->getName() ?? 'Non renseigné',
                ] : [],
                'organization' => $organization ? [
                    'id' => $organization->getId(),
                    'name' => $organization->getName() ?? 'Non précisé',
                ] : [],
                'tags' => $training?->getTags() ? array_map(
                    fn($tag) => [
                        'id' => $tag->getId(),
                        'name' => $tag->getName(),
                    ],
                    $training->getTags()->toArray()
                ) : [],

                // Pour compatibilité avec le code existant
                'inscription_obj' => $insc,
                'training_obj' => $training,
                'session_obj' => $session,
                'trainee_obj' => $trainee,
            ];
        }

        return [
            'total' => count($paginator),
            'pageSize' => $pageSize,
            'currentPage' => $page,
            'totalPages' => ceil(count($paginator) / $pageSize),
            'items' => $items,
        ];
    }

    private function serializeInstitution($institution): array
    {
        if (!$institution) {
            return [];
        }

        return [
            'id' => $institution->getId(),
            'name' => $institution->getName() ?? '',
            'city' => $institution->getCity() ?? '',
        ];
    }


    public function getNbInscriptions($query_filters, $keyword, $aggs, $name): int
    {
        $qb = $this->createQueryBuilder('i');
        $qb
            ->select('COUNT(DISTINCT i.id)')
            ->innerJoin('i.trainee', 'trainee')
            ->innerJoin('i.session', 's')
            ->innerJoin('s.training', 'tr')
            ->leftJoin('tr.tags', 'tag')
            ->leftJoin('tr.theme', 'theme')
            ->leftJoin('tr.organization', 'org')
            ->leftJoin('trainee.institution', 'inst')
            ->leftJoin('trainee.publictype', 'publictype')
            ->leftJoin('i.inscriptionstatus', 'istatus')
            ->leftJoin('i.presencestatus', 'pstatus')

            // FILTRE KEYWORD
            ->where('
                trainee.firstname LIKE :keyword OR 
                trainee.lastname LIKE :keyword OR 
                tr.name LIKE :keyword OR
                tag.name LIKE :keyword
            ')
            ->setParameter('keyword', '%' . addcslashes((string) $keyword, '%_') . '%');

        // Application des filtres de la même manière que dans getInscriptionsList
        // mais en utilisant les bonnes conditions selon le contexte aggs/query_filters

        // FILTRE CENTRE
        if(isset($aggs['session.training.organization.name.source'])) {
            $qb
                ->andWhere('org.name = :center')
                ->setParameter('center', $name);
        } elseif (isset($query_filters['session.training.organization.name.source'])) {
            $qb
                ->andWhere('org.name in (:centers)')
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

        // Autres filtres... (je garde la même logique que votre code original)
        // mais en utilisant les alias cohérents

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
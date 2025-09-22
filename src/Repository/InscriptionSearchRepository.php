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
                                        string $formatCreatedAt = 'd/m/y H:i',
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
            ->where('trainee.firstname LIKE :keyword OR trainee.lastname LIKE :keyword OR tr.name LIKE :keyword')
            ->andWhere('i.trainee = trainee.id')
            ->andWhere('s.training = tr.id')
            ->andWhere('i.session = s.id')

            /* addcslashes empêchera des manipulations malveillantes éventuelles */
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
            $dateFrom = date('d/m/y 00:00:00' ,strtotime(trim($dates[0])));
            $dateTo = date('d/m/y 00:00:00',strtotime(trim($dates[1])));

            $qb
                ->andWhere("s.datebegin BETWEEN :dateFrom AND :dateTo")
                ->addOrderBy('i.createdat', 'DESC')
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

        $qb->addOrderBy('i.createdat', 'DESC');
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

        $paginator = new Paginator($qb, true);

        $items = [];
        foreach ($paginator as $insc) {
            $trainee = $insc->getTrainee();
            $session = $insc->getSession();
            $training = $session?->getTraining();
            $theme = $training?->getTheme();
            $trainingOrg = $training?->getOrganization();
            $institution = $trainee?->getInstitution();
            $publicType = $trainee?->getPublictype();
            $firstname = trim($trainee?->getFirstname() ?? '');
            $lastname = trim($trainee?->getLastname() ?? '');

            $fullname = trim(" $firstname $lastname");
            if ($fullname === '') {
                $fullname = 'Non renseigné';
            }

            $items[] = [
                'id' => $insc->getId(),
                'createdat' => $insc->getCreatedAt()?->format($formatCreatedAt ),
                'isPaying' => $insc?->getPrice(),
                'presencestatus' => $insc->getPresencestatus(),
                'inscriptionstatus' => $insc->getInscriptionstatus(),
                'type' => $insc->getType(),
                'organization' => $trainingOrg  ? [
                    'id' => $trainingOrg?->getId(),
                    'name' => $trainingOrg?->getName() ?? 'Non précisée',
                ] : null,

                //inscription.trainee.organization.name

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
                    'datebegin' => $session?->getDatebegin()?->format('d/m/y'),
                    'dateend' => $session?->getDateend()?->format('d/m/y'),
                    'maximumnumberofregistrations' => $session?->getMaximumNumberOfRegistrations(),
                    'price' => $session?->getPrice(),
                    'fullname' => $fullname,
                    'training' => [
                        'id' => $training?->getId(),
                        'name' => $training?->getName() ?? 'Non renseigné',
                    ],
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


    public function getNbInscriptions($query_filters, $keyword, $aggs, $name, ?string $facet = null): array
    {
        $qb = $this->createQueryBuilder('i');
        $qb
            ->select('COUNT(DISTINCT i.id)')
            ->innerJoin('i.trainee', 'trainee')
            ->innerJoin('i.session', 's')
            ->innerJoin('s.training', 'tr')
            ->leftJoin('tr.tags', 'tag')
            ->leftJoin('tr.theme', 'theme')
            ->leftJoin('tr.category', 'c')
            ->leftJoin('tr.organization', 'org')
            ->leftJoin('trainee.institution', 'inst')
            ->leftJoin('trainee.publictype', 'publictype')
            ->leftJoin('i.inscriptionstatus', 'istatus')
            ->leftJoin('i.presencestatus', 'pstatus')

            // FILTRE KEYWORD
            ->where('
                (trainee.firstname LIKE :keyword OR trainee.firstname IS NULL) OR 
                (trainee.lastname LIKE :keyword OR trainee.lastname IS NULL) OR 
                 (tr.name LIKE :keyword OR tr.name IS NULL) OR 
                 (tag.name LIKE :keyword OR tag.name IS NULL)
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

        // FILTRE Stagiaire
        if(isset($aggs['trainee.fullName.source'])) {
            $qb->andWhere("CONCAT(trainee.firstname, ' ', trainee.lastname) = :fullname")
                ->setParameter('fullname', $name);
        } elseif (!empty($query_filters['trainee.fullName.source']) && $facet !== 'trainee.fullName.source') {
            $qb->andWhere("CONCAT(trainee.firstname, ' ', trainee.lastname) IN (:fullNames)")
                ->setParameter('fullNames', (array)$query_filters['trainee.fullName.source']);
        }

        // FILTRE ÉTABLISSEMENT
        if(isset($aggs['institution.name.source'])) {
            $qb->andWhere('inst.name = :status')
                ->setParameter('status', $name);
        }

        // FILTRE ÉTABLISSEMENT ACTUEL


        if ($facet === 'trainee.institution.name.source' && $name !== null) {
            $qb->andWhere('inst.name LIKE :instName')
                ->setParameter('instName', "%$name%");
        } elseif (!empty($query_filters['trainee.institution.name.source']) && $facet !== 'institution.name') {
            $qb->andWhere('inst.name IN (:instNames)')
                ->setParameter('instNames', (array)$query_filters['trainee.institution.name.source']);
        }

        // FILTRE CATÉGORIE PERSONNEL
        if(isset($aggs['publicType.source'])) {
            $qb->andWhere('publictype.name = :status')
                ->setParameter('status', $name);
        }

        // FILTRE STATUT INSCRIPTION
        if(isset($aggs['inscriptionStatus.name.source'])) {
            $qb->andWhere('istatus.name = :status')
                ->setParameter('status', $name);
        }

// FILTRE TYPE DE FORMATION
        if ($facet === 'session.training.typeLabel' && $name !== null) {
            $qb->andWhere('c.name = :typeLabel')
                ->setParameter('typeLabel', $name);
        } elseif(!empty($query_filters['session.training.typeLabel.source'])) {
        $qb->andWhere('c.name IN (:typeLabels)')
            ->setParameter('typeLabels', $query_filters['session.training.typeLabel.source']);
    }

        // FILTRE STATUT DE PRÉSENCE
        if(isset($aggs['presenceStatus.name.source'])) {
            $qb->andWhere('pstatus.name = :status')
                ->setParameter('status', $name);
        }

        // FILTRE DOMAINE DE FORMATION
        if (isset($query_filters['session.training.name.source'])) {
            $qb->andWhere('tr.name IN (:trainingNames)')
                ->setParameter('trainingNames', (array) $query_filters['session.training.name.source']);
        }

        if ($facet === 'session.training.name' && $name !== null) {
            $qb->andWhere('tr.name = :trainingName')
                ->setParameter('trainingName', $name);
        }


        // FILTRE DOMAINE DE FORMATION
        if(isset($aggs['session.training.theme.name'])) {
            $qb->andWhere('theme.name = :themeName')
                ->setParameter('themeName', $name);
        } elseif (isset($query_filters['session.training.theme.name.source'])) {
            $qb->andWhere('theme.name IN (:themes)')
                ->setParameter('themes', (array) $query_filters['session.training.theme.name.source']);
        }


        // Autres filtres... (je garde la même logique que votre code original)
        // mais en utilisant les alias cohérents

        $total = (int) $qb->getQuery()->getSingleScalarResult();

        return [
            'total' => $total,
            'items' => [],
        ];
    }
}
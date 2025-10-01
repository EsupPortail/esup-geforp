<?php

namespace App\Controller\Core;

use App\AccessRight\AccessRightRegistry;
use App\Entity\Back\Presence;
use App\Entity\Back\Trainee;
use App\Entity\Core\AbstractInstitution;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Core\AbstractTraining;
use App\Entity\PersonTrait\PersonTrait;
use App\Entity\Term\Presencestatus;
use App\Entity\Term\Publictype;
use App\Entity\Back\Inscription;
use App\Entity\Back\Institution;
use App\Entity\Back\Organization;
use App\Entity\Term\Theme;
use App\Entity\Term\Trainingcategory;
use App\Form\Type\InscriptionType;
use App\Form\Type\BaseInscriptionType;
use App\Repository\InscriptionSearchRepository;
use Doctrine\Persistence\ManagerRegistry;
use JMS\Serializer\Serializer;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractInscription;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Term\Inscriptionstatus;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class InscriptionController.
 *
 */
#[Route(path: '/inscription')]
abstract class AbstractInscriptionController extends AbstractController
{
    protected string $inscriptionClass = AbstractInscription::class;
    /**
     * @var int[]
     */
    private const array ALL_SEMESTERS = [1, 2];

    /**
     * @Rest\View(serializerGroups={"Default", "inscription"}, serializerEnableMaxDepthChecks=true)
     * @return array
     */
    #[Route(path: '/search', name: 'inscription.search', options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[Groups(['Default', 'inscription'])]
    #[Rest\View(serializerGroups: ["Default", "inscription"] ,serializerEnableMaxDepthChecks: true)]
    public function search(SerializerInterface $serializer, Request $request, ManagerRegistry $managerRegistry, InscriptionSearchRepository $inscriptionSearchRepository, AccessRightRegistry $accessRightRegistry): array
    {
        $keywords = (string)$request->request->get('keywords') ?? "";
        $filters = $request->request->all('filters') ?? [];
        $query_filters = $request->request->all('query_filters') ?: [];
        $aggs = $request->request->all('aggs') ?: [];
        $page = $request->request->get('page', 1);
        $size = $request->request->get('size', 10);
        $sorts = $request->request->all('sorts')?: [];
        $fields = $request->request->all('fields')?: [];

        // security check : inscirption : 'sygefor_inscription.rights.inscription.all.view' -> id=25
        if(!$accessRightRegistry->hasAccessRight(25)) {
            // restriction to user's organization
            $filters['session.training.organization.name.source'] = $this->getUser()->getOrganization()->getName();
        }

        // Recherche avec les filtres
        $ret = $inscriptionSearchRepository->getInscriptionsList(keyword: $keywords, filters: $filters, formatCreatedAt: 'd/m/Y', page: (int)$page, pageSize: (int)$size, sorts: $sorts, fields: $fields);
        $tabAggs = $this->constructAggs($aggs, $keywords, $query_filters, $managerRegistry, $inscriptionSearchRepository);

        // Concatenation des resultats
        $ret['aggs'] = $tabAggs;
        return $ret;
    }

    #[Rest\View(serializerGroups: ["Default", "inscription"] ,serializerEnableMaxDepthChecks: true)]
    #[Route(path: '/create/{session}', name: 'inscription.create', options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[Groups(['Default', 'inscription'])]
    public function create(Request $request, AbstractSession $session, ManagerRegistry $managerRegistry): array
    {
        $sessions = $managerRegistry->getRepository(AbstractSession::class)->find($session);
        if (!$sessions) {
            throw new NotFoundHttpException();
        }
        if (!$this->isGranted('EDIT', $session->getTraining())) {
            throw new AccessDeniedException('Action non autorisée');
        }

        /** @var AbstractInscription $inscription */
        $inscription = $this->createInscription($session, $managerRegistry);
        /** @var BaseInscriptionType $inscriptionClass */
        $inscriptionClass = '';
        $inscriptionClass = BaseInscriptionType::class;

        $form = $this->createForm($inscriptionClass , $inscription,
            ['attr' => ['organization' => $session->getTraining()->getOrganization()]]
        );
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $inscription->setCreatedAt(new \DateTime('now'));
                $inscription->setUpdatedAt(new \DateTime('now'));
                $objectManager = $managerRegistry->getManager();
                $objectManager->persist($inscription);
                $objectManager->flush();
                $objectManager->refresh($session);
            }
        }

        return ['form' => $form->createView(), 'sessions' => $sessions, 'inscription' => $inscription, ];
    }


    #[Route(path: '/{id}/view', name: 'inscription.view', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[IsGranted('VIEW', subject: 'inscription')]
    #[Rest\view(serializerGroups: ['inscription', 'Default'], serializerEnableMaxDepthChecks: true)]
    public function view(AbstractInscription $inscription, Request $request, ManagerRegistry $managerRegistry, int $id): array
    {
        $inscription = $managerRegistry->getRepository(AbstractInscription::class)->find($id);
        if (!$inscription) {
            throw new NotFoundHttpException();
        }
        if (!$this->isGranted('EDIT', $inscription)) {
            if ($this->isGranted('VIEW', $inscription)) {
                return ['inscription' => $inscription];
            }

            throw new AccessDeniedException('Action non autorisée');
        }

        $inscriptionClass = InscriptionType::class;

        $form = $this->createForm($inscriptionClass, $inscription,
            ['attr' => ['organization' => $inscription->getOrganization()]]);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $inscription->setUpdatedAt(new \DateTime('now'));
                $objectManager = $managerRegistry->getManager();
                $objectManager->flush();
            }
        }

        return ['form' => $form->createView(), 'inscription' => $inscription,  'fullname' => $inscription->getTrainee()?->getFullname()];
    }

    #[Route(path: '/{id}/remove', name: 'inscription.delete', options: ['expose' => true], defaults: ['_format' => 'json'], methods: 'POST')]
    #[IsGranted('DELETE', subject: 'inscription')]
    #[Groups(['Default', 'inscription'])]
    #[Rest\View(true)]
    public function delete(AbstractInscription $inscription, ManagerRegistry $managerRegistry, int $id): array
    {
        if (!$inscription) {
            throw new NotFoundHttpException();
        }
        $objectManager = $managerRegistry->getManager();
        $objectManager->remove($inscription);
        $objectManager->flush();

        return [];
    }

    protected function createInscription(AbstractSession $session, $doctrine): AbstractInscription
    {
        $em = $doctrine->getManager();
        $inscription = new $this->inscriptionClass();
        $inscription->setSession($session);

		$defaultInscriptionStatus = $em->getRepository(Inscriptionstatus::class)->findOneBy(['machinename' => 'waiting']);
        $inscription->setInscriptionstatus($defaultInscriptionStatus);

        return $inscription;
    }

    private function constructAggs($aggs, $keyword, $query_filters, \Doctrine\Persistence\ManagerRegistry $managerRegistry, \App\Repository\InscriptionSearchRepository $inscriptionSearchRepository): array
    {
        if ($aggs === null) {
            return [];
        }

        $tabAggs = [];

        // CONSTRUCTION CENTRES
        if(isset( $aggs['session.training.organization.name.source'])){
            $allOrganizations = $managerRegistry->getRepository(Organization::class)->findAll();

            $i = 0; $tabOrg = [];
            //Pour chaque centre on teste la requête
            foreach($allOrganizations as $allOrganization){
                $nbInscriptionsOrg = $inscriptionSearchRepository->getNbInscriptions($query_filters, $keyword, $aggs, $allOrganization->getName());
                if ($nbInscriptionsOrg['total'] > 0) {
                    //dump($nbInscriptionsOrg);
                    $tabOrg[$i] = [ 'key' => $allOrganization->getName(), 'doc_count' => (int) $nbInscriptionsOrg['total']];
                    ++$i;
                }
            }

            $tabAggs['session.training.organization.name.source']['buckets'] = $tabOrg;
        }

        // CONSTRUCTION STATUT D'INSCRIPTION
        if(isset( $aggs['inscriptionStatus.name.source'])){
            $allInscStatus = $managerRegistry->getRepository(Inscriptionstatus::class)->findAll();

            $i = 0; $tabStatInsc = [];
            //Pour chaque statut d'inscription on teste la requête
            foreach($allInscStatus as $allInscStatut){
                $nbInscriptionsStat = $inscriptionSearchRepository->getNbInscriptions(
                    $query_filters,
                    $keyword,
                    $aggs,
                    $allInscStatut->getName(),
                );

                if ($nbInscriptionsStat['total'] > 0) {
                    $tabStatInsc[] = [
                        'key' => $allInscStatut->getName(),
                        'doc_count' =>  $nbInscriptionsStat['total']
                    ];
                }
            }

            $tabAggs['inscriptionStatus.name.source']['buckets'] = $tabStatInsc;
        }

        // CONSTRUCTION STATUT DE PRESENCE
        if(isset( $aggs['presenceStatus.name.source'])){
            $allPresStatus = $managerRegistry->getRepository(Presencestatus::class)->findAll();

            $i = 0; $tabStatPres = [];
            //Pour chaque statut de présence on teste la requête
            foreach($allPresStatus as $allPreRectorPrefix202304Status){
                $nbPresStat = $inscriptionSearchRepository->getNbInscriptions($query_filters, $keyword, $aggs, $allPreRectorPrefix202304Status->getName());
                if ($nbPresStat['total'] > 0) {
                    $tabStatPres[$i] = [ 'key' => $allPreRectorPrefix202304Status->getName(), 'doc_count' => $nbPresStat['total']];
                    ++$i;
                }
            }

            $tabAggs['presenceStatus.name.source']['buckets'] = $tabStatPres;
        }

        // CONSTRUCTION ETABLISSEMENT
        if(isset( $aggs['institution.name.source'])){
            $allInstitutions = $managerRegistry->getRepository(Institution::class)->findAll();
            $i = 0; $tabInst = [];
            //Pour chaque établissement on teste la requête
            foreach($allInstitutions as $allInstitution){
                $nbInscriptionsInst= $inscriptionSearchRepository->getNbInscriptions($query_filters, $keyword, $aggs, $allInstitution->getName());
                if ($nbInscriptionsInst['total'] > 0) {
                    $tabInst[$i] = [ 'key' => $allInstitution->getName(), 'doc_count' => $nbInscriptionsInst['total']];
                    ++$i;
                }
            }

            $tabAggs['institution.name.source']['buckets'] = $tabInst;
        }

        // CONSTRUCTION TYPE DE PERSONNEL
        $tabAggs['publicType.source'] = ['buckets' => []];

        if (isset($aggs['publicType.source'])) {
            $allPublicTypes = $managerRegistry->getRepository(Publictype::class)->findAll();
            $i = 0; $tabPub = [];

            foreach ($allPublicTypes as $allPublicType) {
                $nbInscriptionsPub = $inscriptionSearchRepository->getNbInscriptions($query_filters, $keyword, $aggs, $allPublicType->getName());
                if ($nbInscriptionsPub['total'] > 0) {
                    $tabPub[$i++] = [
                        'key' => $allPublicType->getName(),
                        'doc_count' => $nbInscriptionsPub['total']
                    ];
                }
            }

            $tabAggs['publicType.source']['buckets'] = $tabPub;
        }

        // CONSTRUCTION ÉTABLISSEMENT ACTUEL
        $tabAggs['trainee.institution.name.source'] = ['buckets' => []];

        if (isset($aggs['trainee.institution.name.source'])) {
            $qb = $managerRegistry->getRepository(Inscription::class)->createQueryBuilder('i');

            // Jointure vers trainee puis institution
            $qb->leftJoin('i.trainee', 't')
                ->leftJoin('t.institution', 'inst')
                ->select('inst.name AS institutionName, COUNT(i.id) AS total')
                ->groupBy('inst.id')
                ->orderBy('total', 'DESC');

            if (!empty($query_filters)) {
            }

            $results = $qb->getQuery()->getResult();

            $buckets = [];
            foreach ($results as $row) {
                if (!empty($row['institutionName'])) {
                    $buckets[] = [
                        'key' => $row['institutionName'],
                        'doc_count' => $row['total'],
                    ];
                }
            }

            $tabAggs['trainee.institution.name.source']['buckets'] = $buckets;
        }



        // CONSTRUCTION STAGIAIRE
        $tabAggs['trainee.fullName.source'] = ['buckets' => []];

        if (isset($aggs['trainee.fullName.source'])) {
            $qb = $managerRegistry->getRepository(Inscription::class)->createQueryBuilder('i');

            $qb->leftJoin('i.trainee', 't')
                ->select("CONCAT(t.firstname, ' ', t.lastname) AS fullName, COUNT(i.id) AS total")
                ->groupBy('t.id')
                ->orderBy('total', 'DESC');

            // réapplique ici tes filtres globaux si nécessaire
            $results = $qb->getQuery()->getResult();

            $buckets = [];
            foreach ($results as $row) {
                if (!empty($row['fullName'])) {
                    $buckets[] = [
                        'key' => $row['fullName'],
                        'doc_count' => $row['total'],
                    ];
                }
            }

            $tabAggs['trainee.fullName.source']['buckets'] = $buckets;
        }

        // CONSTRUCTION ANNEE
        if(isset( $aggs['session.year'])){
            $curYear = date('Y');
            $allYears = [];
            for($i=2017; $i<=$curYear; ++$i){
                $allYears[] = $i;
            }

            $i = 0; $tabYear = [];
            //Pour chaque établissement on teste la requête
            foreach($allYears as $allYear){
                $nbInscriptionsYear= $inscriptionSearchRepository->getNbInscriptions($query_filters, $keyword, $aggs, $allYear);
                if ($nbInscriptionsYear['total'] > 0) {
                    $tabYear[$i] = [ 'key' => $allYear, 'doc_count' => $nbInscriptionsYear['total']];
                    ++$i;
                }
            }

            $tabAggs['session.year']['buckets'] = $tabYear;
        }

        // CONSTRUCTION SEMESTRE
        if (isset($aggs['session.semester'])) {
            $i = 0; $tabSemesters = [];
            //Pour chaque semestre on teste la requête
            foreach(self::ALL_SEMESTERS as $semester){
                $nbInsSem = $inscriptionSearchRepository->getNbInscriptions($query_filters, $keyword, $aggs, $semester);
                if ($nbInsSem['total'] > 0) {
                    $tabSemesters[$i] = [ 'key' => $semester, 'doc_count' => $nbInsSem['total']];
                    ++$i;
                }
            }

            $tabAggs['session.semester']['buckets'] = $tabSemesters;
        }

// CONSTRUCTION TYPE DE FORMATION
        $tabAggs['session.training.typeLabel.source'] = ['buckets' => []];

        if (isset($aggs['session.training.typeLabel.source'])) {
            $allCategories = $managerRegistry->getRepository(Trainingcategory::class)->findAll();
            $i = 0;
            $tabTypes = [];

            foreach ($allCategories as $category) {
                $typeLabel = $category->getName();
                $nbType = $inscriptionSearchRepository->getNbInscriptions($query_filters, $keyword, $aggs, $typeLabel, 'session.training.typeLabel');
                if ($nbType['total'] > 0) {
                    $tabTypes[] = [
                        'key'       => $typeLabel,
                        'doc_count' => $nbType['total'],
                    ];
                }
            }

            $tabAggs['session.training.typeLabel.source']['buckets'] = $tabTypes;
        }

        // CONSTRUCTION FORMATION
        $tabAggs['session.training.name.source'] = ['buckets' => []];

        if (isset($aggs['session.training.name.source'])) {
            $allTraining = $managerRegistry->getRepository(AbstractTraining::class)->findAll();
            $i = 0; $tabTr = [];
            //Pour chaque Formation on teste la requête
            foreach($allTraining as $Training){
                $nbInscTraining = $inscriptionSearchRepository->getNbInscriptions($query_filters, $keyword, $aggs, $Training->getName(), 'session.training.name' );
                if ($nbInscTraining['total'] > 0) {
                    $tabTr[$i++] = [ 'key' => $Training->getName(), 'doc_count' => (int)$nbInscTraining['total']];
                }
            }
            $tabAggs['session.training.name.source']['buckets'] = $tabTr;
        }

        // CONSTRUCTION DOMAINES DE FORMATION
        $tabAggs['session.training.theme.name'] = ['buckets' => []];

        if (isset($aggs['session.training.theme.name'])) {
            $allThemes = $managerRegistry->getRepository(Theme::class)->findAll();
            $i = 0; $tabTh = [];
            //Pour chaque thème on teste la requête
            foreach($allThemes as $allTheme){
                $nbInscThemes = $inscriptionSearchRepository->getNbInscriptions($query_filters, $keyword, $aggs, $allTheme->getName());
                if ($nbInscThemes['total'] > 0) {
                    $tabTh[$i++] = [ 'key' => $allTheme->getName(), 'doc_count' => (int)$nbInscThemes['total']];
                }
            }

            $tabAggs['session.training.theme.name']['buckets'] = $tabTh;
        }

        if (empty($aggs)) {
            return [
                // Renvoie quand même une structure vide attendue par le front
                'session.training.organization.name.source' => ['buckets' => []],
                'inscriptionStatus.name.source' => ['buckets' => []],
                'presenceStatus.name.source' => ['buckets' => []],
                'institution.name.source' => ['buckets' => []],
                'publicType.source' => ['buckets' => []],
                'trainee.fullName.source' => ['buckets' => []],
                'session.year' => ['buckets' => []],
                'session.semester' => ['buckets' => []],
                'session.training.typeLabel.source' => ['buckets' => []],
                'session.training.name.source' => ['buckets' => []],
                'session.training.theme.name' => ['buckets' => []],
            ];
        }
        //dump($aggs);
        return $tabAggs;
    }
}

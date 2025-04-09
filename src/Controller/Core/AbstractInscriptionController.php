<?php

namespace App\Controller\Core;

use App\AccessRight\AccessRightRegistry;
use App\Entity\Back\Presence;
use App\Entity\Term\Presencestatus;
use App\Entity\Term\Publictype;
use App\Entity\Back\Inscription;
use App\Entity\Back\Institution;
use App\Entity\Back\Organization;
use App\Entity\Term\Theme;
use App\Form\Type\InscriptionType;
use App\Form\Type\BaseInscriptionType;
use App\Repository\InscriptionSearchRepository;
use Doctrine\Persistence\ManagerRegistry;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractInscription;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Term\Inscriptionstatus;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class InscriptionController.
 *
 */
#[Route(path: '/inscription')]
abstract class AbstractInscriptionController extends AbstractController
{
    protected $inscriptionClass = AbstractInscription::class;
    /**
     * @var int[]
     */
    private const ALL_SEMESTERS = [1, 2];

    /**
     * @Rest\View(serializerGroups={"Default", "inscription"}, serializerEnableMaxDepthChecks=true)
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    #[Route(path: '/search', name: 'inscription.search', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function search(Request $request, ManagerRegistry $managerRegistry, InscriptionSearchRepository $inscriptionSearchRepository, AccessRightRegistry $accessRightRegistry): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $keywords = $request->request->get('keywords', 'NO KEYWORDS');
        $filters = $request->request->all('filters');
        $query_filters = $request->request->all('query_filters', 'NO QUERY FILTERS');
        $aggs = $request->request->all('aggs', 'NO AGGS');
        $page = $request->request->get('page', 'NO PAGE');
        $size = $request->request->get('size', 'NO SIZE');
        $sorts = $request->request->all('sorts', 'NO SORTS');
        $fields = $request->request->all('fields', 'NO FIELDS');

        // security check : inscirption : 'sygefor_inscription.rights.inscription.all.view' -> id=25
        if(!$accessRightRegistry->hasAccessRight(25)) {
            // restriction to user's organization
            $filters['session.training.organization.name.source'] = $this->getUser()->getOrganization()->getName();
        }

        // Recherche avec les filtres
        $ret = $inscriptionSearchRepository->getInscriptionsList($keywords, $filters, $page, $size, $sorts, $fields);
        $tabAggs = $this->constructAggs($aggs, $keywords, $query_filters, $managerRegistry, $inscriptionSearchRepository);

        // Concatenation des resultats
        $ret['aggs'] = $tabAggs;

        return $this->json($ret, 200, [], [
            'circular_reference_handler' => function ($object) {
                return $object->getId();
            }
        ]);
    }

    #[Route(path: '/create/{session}', name: 'inscription.create', options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[Groups(['Default', 'inscription'])]
    public function create(Request $request, AbstractSession $session, ManagerRegistry $managerRegistry, int $id): array
    {
        $sessions = $managerRegistry->getRepository(AbstractSession::class, $id);
        if (!$sessions) {
            throw new NotFoundHttpException();
        }
        if (!$this->isGranted('EDIT', $session->getTraining())) {
            throw new AccessDeniedException('Action non autorisée');
        }

        /** @var AbstractInscription $inscription */
        $inscription = $this->createInscription($session, $managerRegistry);
        /** @var BaseInscriptionType $inscriptionClass */
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
            }
        }

        return ['form' => $form->createView(), 'inscription' => $inscription];
    }

    /**
     * @Rest\View(serializerGroups={"Default", "inscription"}, serializerEnableMaxDepthChecks=true)
     */
    #[Route(path: '/{id}/view', name: 'inscription.view', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[IsGranted('VIEW', subject: 'inscription')]
    public function view(AbstractInscription $inscription, Request $request, ManagerRegistry $managerRegistry, int $id): array
    {
        $inscriptions = $managerRegistry->getRepository(AbstractSession::class, $id);
        if (!$inscriptions) {
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

        return ['form' => $form->createView(), 'inscription' => $inscription];
    }

    #[Route(path: '/{id}/remove', name: 'inscription.delete', options: ['expose' => true], defaults: ['_format' => 'json'], methods: 'POST')]
    #[IsGranted('DELETE', subject: 'inscription')]
    #[Groups(['Default', 'inscription'])]
    #[Rest\View(true)]
    public function delete(AbstractInscription $inscription, ManagerRegistry $managerRegistry, int $id): array
    {
        $inscription = $managerRegistry->getRepository(AbstractInscription::class, $id);
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
        $tabAggs = [];

        // CONSTRUCTION CENTRES
        if(isset( $aggs['session.training.organization.name.source'])){
            $allOrganizations = $managerRegistry->getRepository(Organization::class)->findAll();

            $i = 0; $tabOrg = [];
            //Pour chaque centre on teste la requête
            foreach($allOrganizations as $allOrganization){
                $nbInscriptionsOrg = $inscriptionSearchRepository->getNbInscriptions($query_filters, $keyword, $aggs, $allOrganization->getName());
                if ($nbInscriptionsOrg > 0) {
                    $tabOrg[$i] = [ 'key' => $allOrganization->getName(), 'doc_count' => $nbInscriptionsOrg];
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
            foreach($allInscStatus as $allInscRectorPrefix202304Status){
                $nbInscriptionsStat = $inscriptionSearchRepository->getNbInscriptions($query_filters, $keyword, $aggs, $allInscRectorPrefix202304Status->getName());
                if ($nbInscriptionsStat > 0) {
                    $tabStatInsc[$i] = [ 'key' => $allInscRectorPrefix202304Status->getName(), 'doc_count' => $nbInscriptionsStat];
                    ++$i;
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
                if ($nbPresStat > 0) {
                    $tabStatPres[$i] = [ 'key' => $allPreRectorPrefix202304Status->getName(), 'doc_count' => $nbPresStat];
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
                if ($nbInscriptionsInst > 0) {
                    $tabInst[$i] = [ 'key' => $allInstitution->getName(), 'doc_count' => $nbInscriptionsInst];
                    ++$i;
                }
            }

            $tabAggs['institution.name.source']['buckets'] = $tabInst;
        }

        // CONSTRUCTION TYPE DE PERSONNEL
        if(isset( $aggs['publicType.source'])){
            $allPublicTypes = $managerRegistry->getRepository(Publictype::class)->findAll();

            $i = 0; $tabPub = [];
            //Pour chaque établissement on teste la requête
            foreach($allPublicTypes as $allPublicType){
                $nbInscriptionsPub= $inscriptionSearchRepository->getNbInscriptions($query_filters, $keyword, $aggs, $allPublicType->getName());
                if ($nbInscriptionsPub > 0) {
                    $tabPub[$i] = [ 'key' => $allPublicType->getName(), 'doc_count' => $nbInscriptionsPub];
                    ++$i;
                }
            }

            $tabAggs['publicType.source']['buckets'] = $tabPub;
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
                if ($nbInscriptionsYear > 0) {
                    $tabYear[$i] = [ 'key' => $allYear, 'doc_count' => $nbInscriptionsYear];
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
                if ($nbInsSem > 0) {
                    $tabSemesters[$i] = [ 'key' => $semester, 'doc_count' => $nbInsSem];
                    ++$i;
                }
            }

            $tabAggs['session.semester']['buckets'] = $tabSemesters;
        }

        // CONSTRUCTION DOMAINES DE FORMATION
        if (isset($aggs['session.training.theme.name'])) {
            $allThemes = $managerRegistry->getRepository(Theme::class)->findAll();
            $i = 0; $tabTh = [];
            //Pour chaque thème on teste la requête
            foreach($allThemes as $allTheme){
                $nbInscThemes = $inscriptionSearchRepository->getNbInscriptions($query_filters, $keyword, $aggs, $allTheme->getName());
                if ($nbInscThemes > 0) {
                    $tabTh[$i] = [ 'key' => $allTheme->getName(), 'doc_count' => $nbInscThemes];
                    ++$i;
                }
            }

            $tabAggs['session.training.theme.name']['buckets'] = $tabTh;
        }



        return $tabAggs;
    }
}

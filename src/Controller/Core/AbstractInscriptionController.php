<?php

namespace App\Controller\Core;

use App\AccessRight\AccessRightRegistry;
use App\Entity\Term\Presencestatus;
use App\Entity\Term\Publictype;
use App\Entity\Back\Inscription;
use App\Entity\Back\Institution;
use App\Entity\Back\Organization;
use App\Form\Type\InscriptionType;
use App\Repository\InscriptionSearchRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractInscription;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Term\Inscriptionstatus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class InscriptionController.
 *
 * @Route("/inscription")
 */
abstract class AbstractInscriptionController extends AbstractController
{
    protected $inscriptionClass = AbstractInscription::class;

    /**
     * @Route("/search", name="inscription.search", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "inscription"}, serializerEnableMaxDepthChecks=true)
     */
    public function searchAction(Request $request, ManagerRegistry $doctrine, InscriptionSearchRepository $inscriptionSearchRepository, AccessRightRegistry $accessRightRegistry)
    {
        $keywords = $request->request->get('keywords', 'NO KEYWORDS');
        $filters = $request->request->get('filters', array());
        $query_filters = $request->request->get('query_filters', 'NO QUERY FILTERS');
        $aggs = $request->request->get('aggs', 'NO AGGS');
        $page = $request->request->get('page', 'NO PAGE');
        $size = $request->request->get('size', 'NO SIZE');
        $fields = $request->request->get('fields', 'NO FIELDS');

        // security check : inscirption : 'sygefor_inscription.rights.inscription.all.view' -> id=25
        if(!$accessRightRegistry->hasAccessRight(25)) {
            // restriction to user's organization
            $filters['session.training.organization.name.source'] = $this->getUser()->getOrganization()->getName();
        }

        // Recherche avec les filtres
        $ret = $inscriptionSearchRepository->getInscriptionsList($keywords, $filters, $page, $size, $fields);

        // Recherche pour aggs et query_filters
        $tabAggs = array();
        $tabAggs = $this->constructAggs($aggs, $keywords, $query_filters, $doctrine, $inscriptionSearchRepository);

        // Concatenation des resultats
        $ret['aggs'] = $tabAggs;

        return $ret;
    }

    /**
     * @Route("/create/{session}", name="inscription.create", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("session", class="App\Entity\Core\AbstractSession", options={"id" = "session"})
     * @Rest\View(serializerGroups={"Default", "inscription"}, serializerEnableMaxDepthChecks=true)
     */
    public function createAction(Request $request, AbstractSession $session, ManagerRegistry $doctrine)
    {
        if (!$this->isGranted('EDIT',$session->getTraining())) {
            throw new AccessDeniedException('Action non autorisée');
        }
        /** @var AbstractInscription $inscription */
        $inscription = $this->createInscription($session, $doctrine);
        /** @var BaseInscriptionType $inscriptionClass */
        $inscriptionClass = BaseInscriptionType::class;

        $form = $this->createForm($inscriptionClass, $inscription,
            array('attr' => array(
                'organization' => $session->getTraining()->getOrganization())
            )
        );
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $inscription->setCreatedAt(new \DateTime('now'));
                $inscription->setUpdatedAt(new \DateTime('now'));
                $em = $doctrine->getManager();
                $em->persist($inscription);
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'inscription' => $inscription);
    }

    /**
     * @Route("/{id}/view", requirements={"id" = "\d+"}, name="inscription.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @IsGranted("VIEW", subject="inscription")
     * @ParamConverter("inscription", class="App\Entity\Core\AbstractInscription", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "inscription"}, serializerEnableMaxDepthChecks=true)
     */
    public function viewAction(AbstractInscription $inscription, Request $request, ManagerRegistry $doctrine)
    {
        if (!$this->isGranted('EDIT', $inscription)) {
            if ($this->isGranted('VIEW', $inscription)) {
                return array('inscription' => $inscription);
            }

            throw new AccessDeniedException('Action non autorisée');
        }

        $inscriptionClass = InscriptionType::class;

        $form = $this->createForm($inscriptionClass, $inscription,
            array('attr' => array(
                'organization' => $inscription->getOrganization())
            ));
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $inscription->setUpdatedAt(new \DateTime('now'));
                $em = $doctrine->getManager();
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'inscription' => $inscription);
    }

    /**
     * @Route("/{id}/remove", name="inscription.delete", options={"expose"=true}, defaults={"_format" = "json"})
     * @Method("POST")
     * @IsGranted("DELETE", subject="inscription")
     * @ParamConverter("inscription", class="App\Entity\Core\AbstractInscription", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "inscription"}, serializerEnableMaxDepthChecks=true)
     */
    public function deleteAction(AbstractInscription $inscription, ManagerRegistry $doctrine)
    {
        $em = $doctrine->getManager();
        $em->remove($inscription);
        $em->flush();

        return array();
    }

    /**
     * @param AbstractSession $session
     *
     * @return AbstractInscription
     */
    protected function createInscription($session, $doctrine)
    {
        $em = $doctrine->getManager();
        $inscription = new $this->inscriptionClass();
        $inscription->setSession($session);
		$defaultInscriptionStatus = $em->getRepository(Inscriptionstatus::class)->findOneBy(['machinename' => 'waiting']);
        $inscription->setInscriptionstatus($defaultInscriptionStatus);

        return $inscription;
    }

    private function constructAggs($aggs, $keyword, $query_filters, $doctrine, $inscriptionRepository)
    {
        $tabAggs = array();

        // CONSTRUCTION CENTRES
        if(isset( $aggs['session.training.organization.name.source'])){
            $allOrganizations = $doctrine->getRepository(Organization::class)->findAll();

            $i = 0; $tabOrg = array();
            //Pour chaque centre on teste la requête
            foreach($allOrganizations as $organization){
                $nbInscriptionsOrg = $inscriptionRepository->getNbInscriptions($query_filters, $keyword, $aggs, $organization->getName());
                if ($nbInscriptionsOrg > 0) {
                    $tabOrg[$i] = [ 'key' => $organization->getName(), 'doc_count' => $nbInscriptionsOrg];
                    $i++;
                }
            }
            $tabAggs['session.training.organization.name.source']['buckets'] = $tabOrg;
        }

        // CONSTRUCTION STATUT D'INSCRIPTION
        if(isset( $aggs['inscriptionStatus.name.source'])){
            $allInscStatus = $doctrine->getRepository(Inscriptionstatus::class)->findAll();

            $i = 0; $tabStatInsc = array();
            //Pour chaque statut d'inscription on teste la requête
            foreach($allInscStatus as $status){
                $nbInscriptionsStat = $inscriptionRepository->getNbInscriptions($query_filters, $keyword, $aggs, $status->getName());
                if ($nbInscriptionsStat > 0) {
                    $tabStatInsc[$i] = [ 'key' => $status->getName(), 'doc_count' => $nbInscriptionsStat];
                    $i++;
                }
            }
            $tabAggs['inscriptionStatus.name.source']['buckets'] = $tabStatInsc;
        }

        // CONSTRUCTION STATUT DE PRESENCE
        if(isset( $aggs['presenceStatus.name.source'])){
            $allPresStatus = $doctrine->getRepository(Presencestatus::class)->findAll();

            $i = 0; $tabStatPres = array();
            //Pour chaque statut de présence on teste la requête
            foreach($allPresStatus as $status){
                $nbPresStat = $inscriptionRepository->getNbInscriptions($query_filters, $keyword, $aggs, $status->getName());
                if ($nbPresStat > 0) {
                    $tabStatPres[$i] = [ 'key' => $status->getName(), 'doc_count' => $nbPresStat];
                    $i++;
                }
            }
            $tabAggs['presenceStatus.name.source']['buckets'] = $tabStatPres;
        }

        // CONSTRUCTION ETABLISSEMENT
        if(isset( $aggs['institution.name.source'])){
            $allInstitutions = $doctrine->getRepository(Institution::class)->findAll();

            $i = 0; $tabInst = array();
            //Pour chaque établissement on teste la requête
            foreach($allInstitutions as $institution){
                $nbInscriptionsInst= $inscriptionRepository->getNbInscriptions($query_filters, $keyword, $aggs, $institution->getName());
                if ($nbInscriptionsInst > 0) {
                    $tabInst[$i] = [ 'key' => $institution->getName(), 'doc_count' => $nbInscriptionsInst];
                    $i++;
                }
            }
            $tabAggs['institution.name.source']['buckets'] = $tabInst;
        }

        // CONSTRUCTION TYPE DE PERSONNEL
        if(isset( $aggs['publicType.source'])){
            $allPublicTypes = $doctrine->getRepository(Publictype::class)->findAll();

            $i = 0; $tabPub = array();
            //Pour chaque établissement on teste la requête
            foreach($allPublicTypes as $publictype){
                $nbInscriptionsPub= $inscriptionRepository->getNbInscriptions($query_filters, $keyword, $aggs, $publictype->getName());
                if ($nbInscriptionsPub > 0) {
                    $tabPub[$i] = [ 'key' => $publictype->getName(), 'doc_count' => $nbInscriptionsPub];
                    $i++;
                }
            }
            $tabAggs['publicType.source']['buckets'] = $tabPub;
        }

        // CONSTRUCTION ANNEE
        if(isset( $aggs['session.year'])){
            $curYear = date('Y');
            $allYears = array();
            for($i=2017; $i<=$curYear; $i++){
                $allYears[] = $i;
            }

            $i = 0; $tabYear = array();
            //Pour chaque établissement on teste la requête
            foreach($allYears as $year){
                $nbInscriptionsYear= $inscriptionRepository->getNbInscriptions($query_filters, $keyword, $aggs, $year);
                if ($nbInscriptionsYear > 0) {
                    $tabYear[$i] = [ 'key' => $year, 'doc_count' => $nbInscriptionsYear];
                    $i++;
                }
            }
            $tabAggs['session.year']['buckets'] = $tabYear;
        }

        // CONSTRUCTION SEMESTRE
        if (isset($aggs['session.semester'])) {
            $allSemesters = array(1, 2);
            $i = 0; $tabSemesters = array();
            //Pour chaque semestre on teste la requête
            foreach($allSemesters as $semester){
                $nbInsSem = $inscriptionRepository->getNbInscriptions($query_filters, $keyword, $aggs, $semester);
                if ($nbInsSem > 0) {
                    $tabSemesters[$i] = [ 'key' => $semester, 'doc_count' => $nbInsSem];
                    $i++;
                }
            }
            $tabAggs['session.semester']['buckets'] = $tabSemesters;
        }

        return $tabAggs;
    }
}

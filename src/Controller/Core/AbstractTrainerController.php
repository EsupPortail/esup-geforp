<?php

namespace App\Controller\Core;

use App\Entity\Back\Institution;
use App\Entity\Back\Organization;
use App\Entity\Back\Trainer;
use App\Repository\TrainerRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Form\Type\ChangeOrganizationType;
use App\Entity\Core\AbstractTrainer;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class TrainerController.
 *
 * @Route("/trainer")
 */
abstract class AbstractTrainerController extends AbstractController
{
    protected $trainerClass = AbstractTrainer::class;

    /**
     * @Route("/search", name="trainer.search", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "trainer"}, serializerEnableMaxDepthChecks=true)
     */
    public function searchAction(Request $request, ManagerRegistry $doctrine, TrainerRepository $trainerRepository)
    {
        $keywords = $request->request->get('keywords', 'NO KEYWORDS');
        $filters = $request->request->get('filters', 'NO FILTERS');
        $query_filters = $request->request->get('query_filters', 'NO QUERY FILTERS');
        $aggs = $request->request->get('aggs', 'NO AGGS');
        $query = $request->request->get('query', 'NO QUERY');
        $page = $request->request->get('page', 'NO PAGE');
        $size = $request->request->get('size', 'NO SIZE');

        // Recherche avec les filtres
        $ret = $trainerRepository->getTrainersList($keywords, $filters, $page, $size);

        // Recherche pour aggs et query_filters
        $tabAggs = array();
        $tabAggs = $this->constructAggs($aggs, $keywords, $query_filters, $doctrine, $trainerRepository);

        // Recherche avec query (pour autocompletion)
        if (isset($query)) {
            // on transforme le champ 'query' en 'keywords'
            if (isset($query['filtered']['query']['match']['fullName.autocomplete']['query'])) {
                $keywords = $query['filtered']['query']['match']['fullName.autocomplete']['query'];
                $ret = $trainerRepository->getTrainersList($keywords, $filters, $page, $size);
            }
        }

        // Concatenation des resultats
        $ret['aggs'] = $tabAggs;

        return $ret;


    }

    /**
     * @Route("/create", name="trainer.create", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "trainer"}, serializerEnableMaxDepthChecks=true)
     */
    public function createAction(Request $request, ManagerRegistry $doctrine)
    {
        /** @var AbstractTrainer $trainer */
        $trainer = new $this->trainerClass();
        $trainer->setOrganization($this->getUser()->getOrganization());

        //trainer can't be created if user has no rights for it
        if (!$this->isGranted('CREATE', $trainer)) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm($trainer::getFormType(), $trainer);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $trainer->setCreatedAt(new \DateTime('now'));
                $trainer->setUpdatedAt(new \DateTime('now'));
                $em = $doctrine->getManager();
                $em->persist($trainer);
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'trainer' => $trainer);
    }

    /**
     * @Route("/{id}/view", requirements={"id" = "\d+"}, name="trainer.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @IsGranted("VIEW", subject="trainer")
     * @ParamConverter("trainer", class="App\Entity\Core\AbstractTrainer", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "trainer"}, serializerEnableMaxDepthChecks=true)
     */
    public function viewAction(AbstractTrainer $trainer, Request $request, ManagerRegistry $doctrine)
    {
        if (!$this->isGranted('EDIT', $trainer)) {
            if ($this->isGranted('VIEW', $trainer)) {
                return array('trainer' => $trainer);
            }

            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm($trainer::getFormType(), $trainer);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $doctrine->getManager()->flush();
            }
        }

        return array('form' => $form->createView(), 'trainer' => $trainer);
    }

    /**
     * @Route("/{id}/changeorg", name="trainer.changeorg", options={"expose"=true}, defaults={"_format" = "json"})
     * @IsGranted("EDIT", subject="trainer")
     * @ParamConverter("trainer", class="App\Entity\Core\AbstractTrainer", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "trainer"}, serializerEnableMaxDepthChecks=true)
     */
    public function changeOrganizationAction(Request $request, AbstractTrainer $trainer, ManagerRegistry $doctrine)
    {
        // security check
/*        if (!$this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_core.access_right.trainer.all.update')) {
            throw new AccessDeniedException();
        } */

        $form = $this->createForm(ChangeOrganizationType::class, $trainer);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $doctrine->getManager()->flush();
            }
        }

        return array('form' => $form->createView(), 'trainer' => $trainer);
    }

    /**
     * @Route("/{id}/remove", name="trainer.delete", options={"expose"=true}, defaults={"_format" = "json"})
     * @IsGranted("DELETE", subject="trainer")
     * @Method("POST")
     * @ParamConverter("trainer", class="App\Entity\Core\AbstractTrainer", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "trainer"}, serializerEnableMaxDepthChecks=true)
     */
    public function deleteAction(AbstractTrainer $trainer, ManagerRegistry $doctrine)
    {
        $em = $doctrine->getManager();
        $em->remove($trainer);
        $em->flush();

    }

    private function constructAggs($aggs, $keyword, $query_filters, $doctrine, $trainerRepository)
    {
        $tabAggs = array();

        // CONSTRUCTION CENTRES
        if(isset( $aggs['organization.name.source'])){
            $allOrganizations = $doctrine->getRepository(Organization::class)->findAll();

            $i = 0; $tabOrg = array();
            //Pour chaque centre on teste la requête
            foreach($allOrganizations as $organization){
                $nbTrOrg = $trainerRepository->getNbTrainers($query_filters, $keyword, $aggs, $organization->getName());
                if ($nbTrOrg > 0) {
                    $tabOrg[$i] = [ 'key' => $organization->getName(), 'doc_count' => $nbTrOrg];
                    $i++;
                }
            }
            $tabAggs['organization.name.source']['buckets'] = $tabOrg;
        }

        // CONSTRUCTION ETABLISSEMENT
        if(isset( $aggs['institution.name.source'])){
            $allInstitutions = $doctrine->getRepository(Institution::class)->findAll();

            $i = 0; $tabInst = array();
            //Pour chaque etablissement on teste la requête
            foreach($allInstitutions as $institution){
                $nbTrInst = $trainerRepository->getNbTrainers($query_filters, $keyword, $aggs, $institution->getName());
                if ($nbTrInst > 0) {
                    $tabInst[$i] = [ 'key' => $institution->getName(), 'doc_count' => $nbTrInst];
                    $i++;
                }
            }
            $tabAggs['institution.name.source']['buckets'] = $tabInst;
        }

        // CONSTRUCTION STATUT
        if(isset( $aggs['isOrganization'])){
            $allStatus = array(0, 1);

            $i = 0; $tabSta = array();
            foreach($allStatus as $status){
                $nbTrSt= $trainerRepository->getNbTrainers($query_filters, $keyword, $aggs, $status);
                if ($nbTrSt > 0) {
                    $tabSta[$i] = [ 'key' => $status, 'doc_count' => $nbTrSt];
                    $i++;
                }
            }
            $tabAggs['isOrganization']['buckets'] = $tabSta;
        }

        // CONSTRUCTION PUBLIE
        if(isset( $aggs['isPublic'])){
            $allPub = array(0, 1);

            $i = 0; $tabPub = array();
            foreach($allPub as $pub){
                $nbTrPub= $trainerRepository->getNbTrainers($query_filters, $keyword, $aggs, $pub);
                if ($nbTrPub > 0) {
                    $tabPub[$i] = [ 'key' => $pub, 'doc_count' => $nbTrPub];
                    $i++;
                }
            }
            $tabAggs['isPublic']['buckets'] = $tabPub;
        }

        // CONSTRUCTION ARCHIVE
        if(isset( $aggs['isArchived'])){
            $allArch = array(0, 1);

            $i = 0; $tabArch = array();
            foreach($allArch as $arch){
                $nbTrArch= $trainerRepository->getNbTrainers($query_filters, $keyword, $aggs, $arch);
                if ($nbTrArch > 0) {
                    $tabArch[$i] = [ 'key' => $arch, 'doc_count' => $nbTrArch];
                    $i++;
                }
            }
            $tabAggs['isArchived']['buckets'] = $tabArch;
        }

        return $tabAggs;
    }
}

<?php

namespace App\Controller\Core;

use App\AccessRight\AccessRightRegistry;
use App\Entity\Term\Theme;
use App\Entity\Back\Internship;
use App\Entity\Back\Organization;
use App\Entity\Back\Trainer;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\Security\Core\Security;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTraining;
use App\Repository\TrainingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/training")
 */
abstract class AbstractTrainingController extends AbstractController
{
    protected $sessionClass = AbstractSession::class;

    /**
     * @Route("/search", name="training.search", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "training"}, serializerEnableMaxDepthChecks=true)
     */
    public function searchAction(Request $request, ManagerRegistry $doctrine, TrainingRepository $trainingRepository, AccessRightRegistry $accessRightRegistry)
    {
        $keywords = $request->request->get('keywords', 'NO KEYWORDS');
        $filters = $request->request->get('filters', array());
        $query_filters = $request->request->get('query_filters', 'NO QUERY FILTERS');
        $aggs = $request->request->get('aggs', 'NO AGGS');
        $page = $request->request->get('page', 'NO PAGE');
        $size = $request->request->get('size', 'NO SIZE');
        $sorts = $request->request->get('sorts', 'NO SORTS');


        // security check : training : 'sygefor_training.rights.inscription.all.view' -> id=9
        if(!$accessRightRegistry->hasAccessRight(9)) {
            // restriction to user's organization
            $filters['training.organization.name.source'] = $this->getUser()->getOrganization()->getName();
        }

        // Recherche avec les filtres
        $ret = $trainingRepository->getTrainingsList($keywords, $filters, $page, $size, $sorts);

        // Recherche pour aggs et query_filters
        $tabAggs = array();
        $tabAggs = $this->constructAggs($aggs, $keywords, $query_filters, $doctrine, $trainingRepository);

        // Concatenation des resultats
        $ret['aggs'] = $tabAggs;

        return $ret;
    }

    /**
     * @Route("/create/{type}", name="training.create", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "training"}, serializerEnableMaxDepthChecks=true)
     */
    public function createAction(Request $request, ManagerRegistry $doctrine, $type)
    {
        $class = Internship::class;
        /** @var AbstractTraining $training */
        $training = new $class();
        try {
            $training->setOrganization($this->getUser()->getOrganization());
            $training->setInstitution($this->getUser()->getOrganization()->getInstitution());
        }
        catch (\Exception $e) {
            return array($e->getMessage());
        }

        //training can't be created if user has no rights for it
        if (!$this->isGranted('CREATE', $training)) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm($training::getFormType(), $training);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $training->setCreatedAt(new \DateTime('now'));
                $training->setUpdatedAt(new \DateTime('now'));
                $em = $doctrine->getManager();
                $em->persist($training);
                $em->flush();
            }
        }

        return array('training' => $training, 'form' => $form->createView());
        //return new Response(json_encode(array('training' => $training, 'form' => $form->createView())));

    }

    /**
     * This action attach a form to the return array when the user has the permission to edit the training.
     *
     * @Route("/{id}/view", requirements={"id" = "\d+"}, name="training.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @IsGranted("VIEW", subject="training")
     * @ParamConverter("training", class="App\Entity\Core\AbstractTraining", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "training"}, serializerEnableMaxDepthChecks=true)
     */
    public function viewAction(Request $request,ManagerRegistry $doctrine, AbstractTraining $training)
    {
        if (!$this->isGranted('EDIT', $training)) {
            if ($this->isGranted('VIEW', $training)) {
                return array('training' => $training);
            }

            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm($training::getFormType(), $training);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $training->setUpdatedAt(new \DateTime('now'));
                $em = $doctrine->getManager();
                $em->flush();
            }
        }
        $return = array('form' => $form->createView(), 'training' => $training);

        // if the training is single session, add 'session' to the serialization groups
        if ($training instanceof SingleSessionTraining) {
            $view = new View($return);
            $view->setSerializationContext(SerializationContext::create()->setGroups(array('Default', 'training', 'session')));

            return $view;
        }

        return $return;
    }

    /**
     * @Route("/{id}/remove", requirements={"id" = "\d+"}, name="training.remove", options={"expose"=true}, defaults={"_format" = "json"})
     * @Method("POST")
     * @IsGranted("DELETE", subject="training")
     * @ParamConverter("training", class="App\Entity\Core\AbstractTraining", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "training"}, serializerEnableMaxDepthChecks=true)
     */
    public function removeAction(ManagerRegistry $doctrine, AbstractTraining $training)
    {
        $em = $doctrine->getManager();
        $em->remove($training);
        $em->flush();
//        $this->get('fos_elastica.index')->refresh();

        return $this->redirect($this->generateUrl('training.search'));
    }

    /**
     * @Route("/choosetypeduplicate", name="training.choosetypeduplicate", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "training"}, serializerEnableMaxDepthChecks=true)
     */
    public function chooseTypeDuplicateAction(Request $request)
    {
        $typeChoices = array();
        foreach ($this->get('sygefor_training.type.registry')->getTypes() as $type => $entity) {
            $typeChoices[$type] = $entity['label'];
        }
        $form = $this->createFormBuilder()
            ->add('duplicatedType', 'choice', array(
                'label'    => 'Type de stage',
                'choices'  => $typeChoices,
                'required' => true,
                'attr'     => array(
                    'title' => 'Type de la formation ciblée',
                ),
            ))->getForm();

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                return array('type' => $form->get('duplicatedType')->getData());
            }
        }

        return array('form' => $form->createView());
    }

    /**
     * @Route("/duplicate/{id}/{type}", name="training.duplicate", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("training", class="App\Entity\Core\AbstractTraining")
     * @Rest\View(serializerGroups={"Default", "training"}, serializerEnableMaxDepthChecks=true)
     */
    public function duplicateAction(Request $request, AbstractTraining $training, $type)
    {
        //training can't be created if user has no rights for it
        if ( ! $this->isGranted('CREATE', $training)) {
            throw new AccessDeniedException('Action non autorisée');
        }

        /** @var AbstractTraining $cloned */
        $cloned = null;
        // get targetted training type
        $typeClass = $this->get('sygefor_training.type.registry')->getType($type);
        if ($type === $training->getType()) {
            $cloned = clone $training;
        }
        else {
            $cloned = new $typeClass['class']();
            $cloned->copyProperties($training);
        }

        // special operations for meeting session duplicate
        $session = null;
        if ($typeClass['label'] === 'Rencontre scientifique') {
            if ($training->getType() === 'meeting') {
                $session = clone $cloned->getSession();
            }
            else {
                if ($training->getSessions() && $training->getSessions()->count() > 0) {
                    $session = clone $training->getSessions()->last();
                }
                else {
                    $session = new $this->sessionClass;
                }
            }
            $session->setNumberOfRegistrations(0);
            $session->setTraining($cloned);
            $cloned->setSession($session);
        }

        // verify if training category matches with new type
        /** @var RepositoryFactory $repository */
        $repository = $this->getDoctrine()->getRepository('SygeforTrainingBundle:Training\Term\Trainingcategory');
        /** @var QueryBuilder $qb */
        $qb = $repository->createQueryBuilder('t')
            ->where('t.trainingType = :trainingType')
            ->orWhere('t.trainingType IS NULL')
            ->setParameter('trainingType', $training->getType());
        $trainingTypes = $qb->getQuery()->execute();

        $found = false;
        if ($cloned->getCategory()) {
            foreach ($trainingTypes as $trainingType) {
                if ($trainingType->getId() === $cloned->getCategory()->getId()) {
                    $found = TRUE;
                    break;
                }
            }
        }
        if (!$found) {
            $cloned->setCategory(null);
        }

        $form = $this->createForm($typeClass['class']::getFormType(), $cloned);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                // if meeting assign cloned training to the session
                if ($cloned->getType() === 'meeting') {
                    $cloned->getSession()->setTraining($cloned);
                }
                $this->mergeArrayCollectionsAndFlush($cloned, $training);

                return array('form' => $form->createView(), 'training' => $cloned);
            }
        }

        return array('form' => $form->createView());
    }

    /**
     * @param AbstractTraining $dest
     * @param AbstractTraining $source
     */
    protected function mergeArrayCollectionsAndFlush($dest, $source)
    {
        $em = $this->getDoctrine()->getManager();

        // clone common arrayCollections
        if (method_exists($source, 'getTags')) {
            $dest->duplicateArrayCollection('addTag', $source->getTags());
        }

        // clone duplicate materials
        $tmpMaterials = $source->getMaterials();
        if ( ! empty($tmpMaterials)) {
            foreach ($tmpMaterials as $material) {
                $newMat = clone $material;
                $dest->addMaterial($newMat);
            }
        }

        $em->persist($dest);
        $em->flush();
    }

    /**
     * @Route("/{id}/bilan.{_format}", requirements={"id" = "\d+"}, name="training.balancesheet", options={"expose"=true}, defaults={"_format" = "xls"}, requirements={"_format"="csv|xls|xlsx"})
     * @Method("GET")
     * @ParamConverter("training", class="SygeforTrainingBundle:Training\AbstractTraining", options={"id" = "id"})
     */
    public function balanceSheetAction(AbstractTraining $training)
    {
        $bs = new TrainingBalanceSheet($training, $this->get('phpexcel'), $this->container);

        return $bs->getResponse();
    }

    private function constructAggs($aggs, $keyword, $query_filters, $doctrine, $trainingRepository)
    {
        $tabAggs = array();

        // CONSTRUCTION CENTRES
        if(isset( $aggs['training.organization.name.source'])){
            $allOrganizations = $doctrine->getRepository(Organization::class)->findAll();

            $i = 0; $tabOrg = array();
            //Pour chaque centre on teste la requête
            foreach($allOrganizations as $organization){
                $nbTrainingsOrg = $trainingRepository->getNbTrainings($query_filters, $keyword, $aggs, $organization->getName());
                if ($nbTrainingsOrg > 0) {
                    $tabOrg[$i] = [ 'key' => $organization->getName(), 'doc_count' => $nbTrainingsOrg];
                    $i++;
                }
            }
            $tabAggs['training.organization.name.source']['buckets'] = $tabOrg;
        }

        // CONSTRUCTION ANNEES
        if (isset($aggs['year'])) {
            $curYear = date('Y');
            $allYears = array();
            for($i=2017; $i<=$curYear; $i++){
                $allYears[] = $i;
            }
            $i = 0; $tabYears = array();
            //Pour chaque année on teste la requête
            foreach($allYears as $year){
                $nbTrainingsYear = $trainingRepository->getNbTrainings($query_filters, $keyword, $aggs, $year);
                if ($nbTrainingsYear > 0) {
                    $tabYears[$i] = [ 'key' => $year, 'doc_count' => $nbTrainingsYear];
                    $i++;
                }
            }
            $tabAggs['year']['buckets'] = $tabYears;
        }

        // CONSTRUCTION SEMESTRE
        if (isset($aggs['semester'])) {
            $allSemesters = array(1, 2);
            $i = 0; $tabSemesters = array();
            //Pour chaque semestre on teste la requête
            foreach($allSemesters as $semester){
                $nbTrainingsSem = $trainingRepository->getNbTrainings($query_filters, $keyword, $aggs, $semester);
                if ($nbTrainingsSem > 0) {
                    $tabSemesters[$i] = [ 'key' => $semester, 'doc_count' => $nbTrainingsSem];
                    $i++;
                }
            }
            $tabAggs['semester']['buckets'] = $tabSemesters;
        }

        // CONSTRUCTION THEMES
        if(isset( $aggs['theme.name'])){
            $allThemes = $doctrine->getRepository(Theme::class)->findAll();

            $i = 0; $tabThemes = array();
            //Pour chaque thème on teste la requête
            foreach($allThemes as $theme){
                $nbTrainingsThemes = $trainingRepository->getNbTrainings($query_filters, $keyword, $aggs, $theme->getName());
                if ($nbTrainingsThemes > 0) {
                    $tabThemes[$i] = [ 'key' => $theme->getName(), 'doc_count' => $nbTrainingsThemes];
                    $i++;
                }
            }
            $tabAggs['theme.name']['buckets'] = $tabThemes;
        }

        // CONSTRUCTION PROMOTION (true,false) = (0,1)
        if( isset($aggs['nextSession.promote']) ) {
            $allPromote = array(0, 1);
            $i = 0; $tabPro = array();
            //Pour chaque promote on teste la requête
            foreach($allPromote as $promote){
                $nbTrainingsPro = $trainingRepository->getNbTrainings($query_filters, $keyword, $aggs, $promote);
                if ($nbTrainingsPro > 0) {
                    $tabPro[$i] = [ 'key' => $promote, 'doc_count' => $nbTrainingsPro];
                    $i++;
                }
            }
            $tabAggs['nextSession.promote']['buckets'] = $tabPro;
        }

        // CONSTRUCTION FORMATEUR
        if( isset($aggs['trainers.fullName']) ) {
            $allTrainers = $doctrine->getRepository(Trainer::class)->findAll();
            $i = 0; $tabTra = array();
            //Pour chaque trainer on teste la requête
            foreach($allTrainers as $trainer){
                $nbTrainingsTra = $trainingRepository->getNbTrainings($query_filters, $keyword, $aggs, $trainer->getId());
                if ($nbTrainingsTra > 0) {
                    $tabTra[$i] = [ 'key' => $trainer->getFullname(), 'doc_count' => $nbTrainingsTra];
                    $i++;
                }
            }
            $tabAggs['trainers.fullName']['buckets'] = $tabTra;
        }


        return $tabAggs;
    }

}

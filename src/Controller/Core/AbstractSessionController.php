<?php

namespace App\Controller\Core;

use App\Entity\Term\Theme;
use App\Entity\Back\Internship;
use App\Entity\Back\Organization;
use App\Entity\Back\Session;
use App\Entity\Back\Trainer;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Core\AbstractInscription;
use App\Entity\Core\AbstractParticipation;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTraining;
use App\Form\Type\AbstractSessionType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

/**
 * @Route("/training/session")
 */
abstract class AbstractSessionController extends AbstractController
{
    protected $sessionClass = AbstractSession::class;
    protected $participationClass = AbstractParticipation::class;

    /**
     * @Route("/search", name="session.search", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function searchAction(Request $request, ManagerRegistry $doctrine, SessionRepository $sessionRepository)
    {
        $keywords = $request->request->get('keywords', 'NO KEYWORDS');
        $filters = $request->request->get('filters', 'NO FILTERS');
        $query_filters = $request->request->get('query_filters', 'NO QUERY FILTERS');
        $aggs = $request->request->get('aggs', 'NO AGGS');
        $page = $request->request->get('page', 'NO PAGE');
        $size = $request->request->get('size', 'NO SIZE');
        $fields = $request->request->get('fields', 'NO FIELDS');

        // Recherche avec les filtres
        $ret = $sessionRepository->getSessionsList($keywords, $filters, $page, $size, $fields);

        // Recherche pour aggs et query_filters
        $tabAggs = $this->constructAggs($aggs, $keywords, $query_filters, $doctrine, $sessionRepository);

        // Concatenation des resultats
        $ret['aggs'] = $tabAggs;

        return $ret;
    }

    /**
     * @Route("/create/{training}", requirements={"id" = "\d+"}, name="session.create", options={"expose"=true}, defaults={"_format" = "json"})
     * @IsGranted("EDIT", subject="training")
     * @ParamConverter("training", class="App\Entity\Core\AbstractTraining", options={"id" = "training"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function createAction(Request $request, ManagerRegistry $doctrine, AbstractTraining $training)
    {
        /** @var AbstractSession $session */
        $session = new $this->sessionClass();
        $session->setTraining($training);
        $session->setName($training->getName());
        $form = $this->createForm($session::getFormType(), $session);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $session->setCreatedAt(new \DateTime('now'));
                $session->setUpdatedAt(new \DateTime('now'));
                $em = $doctrine->getManager();
                $em->persist($session);
//                $training->updateTimestamps();
                $em->flush();
            }
        }

        if (!$this->isGranted('EDIT', $session->getTraining())) {
            if ($this->isGranted('VIEW', $session->getTraining())) {
                return array('session' => $session);
            }

            throw new AccessDeniedException('Action non autorisée');
        }

        return array('form' => $form->createView(), 'training' => $session->getTraining(), 'session' => $session);
    }

    /**
     * This action attach a form to the return array when the user has the permission to edit the training.
     *
     * @Route("/{id}/view", requirements={"id" = "\d+"}, name="session.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("session", class="App\Entity\Core\AbstractSession", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function viewAction(Request $request, ManagerRegistry $doctrine, AbstractSession $session)
    {
        if (!$this->isGranted('EDIT', $session->getTraining())) {
            if ($this->isGranted('VIEW', $session->getTraining())) {
                return array('session' => $session);
            }

            throw new AccessDeniedException('Action non autorisée');
        }

        $sessionRegistration = $session->getRegistration();
        $form = $this->createForm($session::getFormType(), $session);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em = $doctrine->getManager();
                $em->flush();

                return $this->redirectToRoute('session.view', array('id' => $session->getId()));
            }
        }

        return array('form' => $form->createView(), 'session' => $session);
    }

    /**
     * @param Request              $request
     * @param AbstractSession|null $session
     * @param mixed                $inscriptionIds
     *
     * @Route("/duplicate/{id}/{inscriptionIds}", requirements={"id" = "\d+"}, name="session.duplicate", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("session", class="App\Entity\Core\AbstractSession", isOptional="true")
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     *
     * @return array
     */
    public function duplicateAction(Request $request, ManagerRegistry $doctrine, AbstractSession $session = null, $inscriptionIds = null)
    {
        // we need at least one of both arguments
        if (!$session && empty($inscriptionIds)) {
            throw new MissingOptionsException('You have to pass a session id or an inscription array of ids');
        }

        // get inscriptions and session
        $inscriptions = array();
        $this->retrieveInscriptions($inscriptionIds, $inscriptions);
        if (!$session) {
            // get session
            $session = $inscriptions[0]->getSession();
        }

        // new session can't be created if user has no rights for it
        if (!$this->isGranted('EDIT', $session->getTraining())) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $cloned = clone $session;
        $form = $this->createFormBuilder($cloned)
            ->add('name', null, array(
                'required' => true,
                'label' => 'Intitulé de la session',
            ))
            ->add('datebegin', DateType::class, array(
                'label' => 'Date de début',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => true,
            ))
            ->add('dateend', DateType::class, array(
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => false,
            ));

        if (!empty($inscriptions)) {
            $form
                ->add('inscriptionManagement', ChoiceType::class, array(
                    'label' => 'Choisir la méthode d\'importation des inscriptions',
                    'mapped' => false,
                    'choices' => array(
                        'Ne pas importer les inscriptions' => 'none',
                        'Copier les inscriptions' => 'copy',
                        'Déplacer les inscriptions' => 'move',
                    ),
                    'empty_data' => 'none',
                    'required' => true,
                ));
        }

        $form = $form->getForm();
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em = $doctrine->getManager();
                $this->cloneSessionArrayCollections($session, $cloned, $inscriptions, $form->has('inscriptionManagement') ? $form->get('inscriptionManagement')->getData() : null);
                $em->persist($cloned);
                $em->flush();

                return array('session' => $cloned);
            }
        }

        return array('form' => $form->createView(), 'session' => $session, 'inscriptions' => $inscriptionIds);
    }

    /**
     * @Route("/{id}/remove", requirements={"id" = "\d+"}, name="session.remove", options={"expose"=true}, defaults={"_format" = "json"})
     * @Method("POST")
     * @ParamConverter("session", class="App\Entity\Core\AbstractSession", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function removeAction(AbstractSession $session, ManagerRegistry $doctrine)
    {
        if (!$this->isGranted('DELETE', $session->getTraining())) {
            throw new AccessDeniedException('Action non autorisée');
        }
        $training = $session->getTraining();
        $em = $doctrine->getManager();
        $em->remove($session);
//        $training->updateTimestamps();
        $em->flush();
//        $this->get('fos_elastica.index')->refresh();

        return $this->redirect($this->generateUrl('training.view', array('id' => $training->getId())));
    }

    /**
     * Get inscription from json id inscription list.
     *
     * @param $inscriptionIds
     * @param $inscriptions
     */
    protected function retrieveInscriptions(&$inscriptionIds, &$inscriptions)
    {
        if ($inscriptionIds && is_string($inscriptionIds)) {
            $inscriptionIds = json_decode($inscriptionIds, true);
        }

        // retrieve inscriptions and session
        if ($inscriptionIds) {
            $inscriptions = $this->getDoctrine()->getManager()
                ->getRepository(AbstractInscription::class)
                ->findById($inscriptionIds);

            if (empty($inscriptions)) {
                throw new MissingOptionsException('You have to pass a session id or an inscription array of ids');
            }

            // check if all inscription come from a unique session
            $arraySessionIds = array();
            /** @var AbstractInscription $inscription */
            foreach ($inscriptions as $inscription) {
                $arraySessionIds[] = $inscription->getSession()->getId();
            }
            $arraySessionIds = array_unique($arraySessionIds);
            if (count($arraySessionIds) > 1) {
                throw new InvalidOptionException('The inscriptions come from several sessions');
            }
        }
    }

    /**
     * Clone participations, inscriptions and materials.
     *
     * @param AbstractSession $session
     * @param AbstractSession $cloned
     * @param $inscriptions
     * @param mixed $inscriptionManagement
     */
    protected function cloneSessionArrayCollections($session, $cloned, $inscriptions, $inscriptionManagement)
    {
        $em = $this->getDoctrine()->getManager();

        // clone participations
        /** @var AbstractParticipation $participation */
        foreach ($session->getParticipations() as $participation) {
            /** @var AbstractParticipation $newParticipation */
            $newParticipation = new $this->participationClass();
            $newParticipation->setSession($cloned);
            $newParticipation->setTrainer($participation->getTrainer());
            $cloned->addParticipation($newParticipation);
            $em->persist($newParticipation);
        }

        // clone inscriptions
        switch ($inscriptionManagement) {
            case 'copy':
                /** @var AbstractInscription $inscription */
                foreach ($inscriptions as $inscription) {
                    $newInscription = clone $inscription;
                    $newInscription->setSession($cloned);
                    $newInscription->setPresencestatus(null);
                    $cloned->addInscription($newInscription);
                    $em->persist($newInscription);
                }
                break;
            case 'move':
                /** @var AbstractInscription $inscription */
                foreach ($inscriptions as $inscription) {
                    $session->removeInscription($inscription);
                    $inscription->setSession($cloned);
                    $cloned->addInscription($inscription);
                }
                break;
            default:
                break;
        }

        // clone duplicate materials
        $tmpMaterials = $session->getMaterials();
        if (!empty($tmpMaterials)) {
            foreach ($tmpMaterials as $material) {
                $newMat = clone $material;
                $cloned->addMaterial($newMat);
            }
        }
    }

    private function constructAggs($aggs, $keyword, $query_filters, $doctrine, $sessionRepository)
    {
        $tabAggs = array();

        // CONSTRUCTION CENTRES
        if(isset( $aggs['training.organization.name.source'])){
            $allOrganizations = $doctrine->getRepository(Organization::class)->findAll();

            $i = 0; $tabOrg = array();
            //Pour chaque centre on teste la requête
            foreach($allOrganizations as $organization){
                $nbSessionsOrg = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $organization->getName());
                if ($nbSessionsOrg > 0) {
                    $tabOrg[$i] = [ 'key' => $organization->getName(), 'doc_count' => $nbSessionsOrg];
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
                $nbSessionsYear = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $year);
                if ($nbSessionsYear > 0) {
                    $tabYears[$i] = [ 'key' => $year, 'doc_count' => $nbSessionsYear];
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
                $nbSessionsSem = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $semester);
                if ($nbSessionsSem > 0) {
                    $tabSemesters[$i] = [ 'key' => $semester, 'doc_count' => $nbSessionsSem];
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
                $nbSessionsThemes = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $theme->getName());
                if ($nbSessionsThemes > 0) {
                    $tabThemes[$i] = [ 'key' => $theme->getName(), 'doc_count' => $nbSessionsThemes];
                    $i++;
                }
            }
            $tabAggs['theme.name']['buckets'] = $tabThemes;
        }

        // CONSTRUCTION INSCRIPTION (0,1,2,3)
        if( isset($aggs['registration']) ) {
            $allRegistrations = array(0, 1, 2, 3);
            $i = 0; $tabReg = array();
            //Pour chaque inscription on teste la requête
            foreach($allRegistrations as $registration){
                $nbSessionsReg = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $registration);
                if ($nbSessionsReg > 0) {
                    $tabReg[$i] = [ 'key' => $registration, 'doc_count' => $nbSessionsReg];
                    $i++;
                }
            }
            $tabAggs['registration']['buckets'] = $tabReg;
        }

        // CONSTRUCTION STATUT (0,1,2)
        if( isset($aggs['status']) ) {
            $allStatus = array(0, 1, 2);
            $i = 0; $tabStatus = array();
            //Pour chaque status on teste la requête
            foreach($allStatus as $status){
                $nbSessionsStatus = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $status);
                if ($nbSessionsStatus > 0) {
                    $tabStatus[$i] = [ 'key' => $status, 'doc_count' => $nbSessionsStatus];
                    $i++;
                }
            }
            $tabAggs['status']['buckets'] = $tabStatus;
        }

        // CONSTRUCTION DISPLAYONLINE (0,1)
        if( isset($aggs['displayOnline']) ) {
            $allDisplay = array(0 => 'F', 1 => 'T');
            $i = 0; $tabDis = array();
            //Pour chaque status on teste la requête
            foreach($allDisplay as $key => $display){
                $nbSessionsDis = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $key);
                if ($nbSessionsDis > 0) {
                    $tabDis[$i] = [ 'key' => $display, 'doc_count' => $nbSessionsDis];
                    $i++;
                }
            }
            $tabAggs['displayOnline']['buckets'] = $tabDis;
        }

        // CONSTRUCTION PROMOTION (true,false) = (0,1)
        if( isset($aggs['promote']) ) {
            $allPromote = array(0, 1);
            $i = 0; $tabPro = array();
            //Pour chaque promote on teste la requête
            foreach($allPromote as $promote){
                $nbSessionsPro = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $promote);
                if ($nbSessionsPro > 0) {
                    $tabPro[$i] = [ 'key' => $promote, 'doc_count' => $nbSessionsPro];
                    $i++;
                }
            }
            $tabAggs['promote']['buckets'] = $tabPro;
        }

        // CONSTRUCTION FORMATION (nom de la formation)
        if( isset($aggs['training.name.source']) ) {
            $allTraining = $doctrine->getRepository(Internship::class)->findAll();
            $i = 0; $tabTra = array();
            //Pour chaque promote on teste la requête
            foreach($allTraining as $training){
                $nbSessionsTra = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $training->getName());
                if ($nbSessionsTra > 0) {
                    $tabTra[$i] = [ 'key' => $training->getName(), 'doc_count' => $nbSessionsTra];
                    $i++;
                }
            }
            $tabAggs['training.name.source']['buckets'] = $tabTra;
        }

        // CONSTRUCTION FORMATEUR
        if( isset($aggs['participations.trainer.fullName']) ) {
            $allTrainers = $doctrine->getRepository(Trainer::class)->findAll();
            $i = 0; $tabTra = array();
            //Pour chaque trainer on teste la requête
            foreach($allTrainers as $trainer){
                $nbSessionsTra = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $trainer->getId());
                if ($nbSessionsTra > 0) {
                    $tabTra[$i] = [ 'key' => $trainer->getFullname(), 'doc_count' => $nbSessionsTra];
                    $i++;
                }
            }
            $tabAggs['participations.trainer.fullName']['buckets'] = $tabTra;
        }


        return $tabAggs;
    }

    public function computeInscriptionsStats(ManagerRegistry $doctrine, $session)
    {
        /** @var EntityManager $em */
        $em = $doctrine->getManager();
        $stats = array();
        if ($session->getRegistration() > AbstractSession::REGISTRATION_DEACTIVATED) {
            $query = $em
                ->createQuery('SELECT s, count(i) FROM App\Entity\Term\Inscriptionstatus s
                        JOIN App\Entity\Core\AbstractInscription i WITH i.inscriptionstatus = s
                        WHERE i.session = :session
                        GROUP BY s.id')
                ->setParameter('session', $this);

            $result = $query->getResult();
            foreach ($result as $status) {
                $stats[] = array(
                    'id' => $status[0]->getId(),
                    'name' => $status[0]->getName(),
                    'status' => $status[0]->getStatus(),
                    'count' => (int)$status[1],
                );
            }
        }
        return $stats;
    }

    public function computePresencesStats(ManagerRegistry $doctrine, $session)
    {
        /** @var EntityManager $em */
        $em = $doctrine->getManager();
        $statsPres = array();
        $queryPres = $em
            ->createQuery('SELECT s, count(i) FROM App\Entity\Term\Presencestatus s
                            JOIN App\Entity\Core\AbstractInscription i WITH i.presencestatus = s
                            WHERE i.session = :session
                            GROUP BY s.id')
            ->setParameter('session', $this);

        $resultPres = $queryPres->getResult();
        foreach ($resultPres as $status) {
            $statsPres[] = array(
                'id' => $status[0]->getId(),
                'name' => $status[0]->getName(),
                'status' => $status[0]->getStatus(),
                'count' => (int)$status[1],
            );
        }

        return $statsPres;
    }
}

<?php

namespace App\Controller\Core;

use App\AccessRight\AccessRightRegistry;
use App\Entity\Term\Theme;
use App\Entity\Back\Internship;
use App\Entity\Back\Organization;
use App\Entity\Back\Session;
use App\Entity\Back\Trainer;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Routing\Attribute\Route;
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

#[Route(path: '/training/session')]
abstract class AbstractSessionController extends AbstractController
{
    protected string $sessionClass = AbstractSession::class;

    protected string $participationClass = AbstractParticipation::class;

    public function __construct(private readonly \Doctrine\Persistence\ManagerRegistry $managerRegistry)
    {
    }

    /**
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     * @return array{total: int, pageSize: mixed, items: array<int, array{availablePlaces?: mixed, datebegin?: mixed, dateend?: mixed, daynumber?: mixed, displayonline?: mixed, hournumber?: mixed, id: mixed, inscriptions?: array<int, array{id: mixed}>, inscriptionStats?: array<int, array{id: mixed, name: mixed, status: mixed, count: int}>, limitRegistrationDate?: mixed, maximumnumberofregistrations?: mixed, name?: mixed, numberofacceptedregistrations?: mixed, numberofparticipants?: mixed, numberofregistrations?: mixed, participations?: array<int, array{id: mixed}>, promote?: mixed, registrable?: mixed, registration?: mixed, semester?: mixed, semesterLabel?: mixed, sessiontype?: mixed, status?: mixed, theme?: mixed, training?: array{id: mixed, type: mixed, name: mixed, typeLabel: mixed, organization: mixed, number: mixed, theme: mixed, tags: mixed, program: mixed, description: mixed, interventionType: mixed, externalInitiative: mixed, category: mixed, comments: mixed, firstSessionPeriodSemester: mixed, firstSessionPeriodYear: mixed, publictypes: mixed}, year?: mixed}>, aggs: mixed}
     */
    #[Route(path: '/search', name: 'session.search', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function search(Request $request, ManagerRegistry $managerRegistry, SessionRepository $sessionRepository, AccessRightRegistry $accessRightRegistry): array
    {
        $keywords = $request->request->get('keywords', 'NO KEYWORDS');
        $filters = $request->request->all('filters');
        $query_filters = $request->request->all('query_filters', 'NO QUERY FILTERS');
        $aggs = $request->request->all('aggs', 'NO AGGS');
        $page = $request->request->get('page', 'NO PAGE');
        $size = $request->request->get('size', 'NO SIZE');
        $sorts = $request->request->all('sorts', 'NO SORTS');
        $fields = $request->request->all('fields', 'NO FIELDS');

        // security check : session : 'sygefor_training.rights.inscription.all.view' -> id=9
        if(!$accessRightRegistry->hasAccessRight(9)) {
            // restriction to user's organization
            $filters['training.organization.name.source'] = $this->getUser()->getOrganization()->getName();
        }

        // Recherche avec les filtres
        $ret = $sessionRepository->getSessionsList($keywords, $filters, $page, $size, $sorts, $fields);

        // Recherche pour aggs et query_filters
        $tabAggs = $this->constructAggs($aggs, $keywords, $query_filters, $managerRegistry, $sessionRepository);

        // Concatenation des resultats
        $ret['aggs'] = $tabAggs;

        return $ret;
    }

    /**
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    #[Route(path: '/create/{training}', name: 'session.create', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[IsGranted('EDIT', subject: 'training')]
    public function create(Request $request, ManagerRegistry $managerRegistry, AbstractTraining $training, int $id): array
    {
        $training = $managerRegistry->getRepository(AbstractTraining::class)->find($id);
        if(!$training) {
            throw new NotFoundHttpException();
        }
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
                $objectManager = $managerRegistry->getManager();
                $objectManager->persist($session);
//                $training->updateTimestamps();
                $objectManager->flush();
            }
        }

        if (!$this->isGranted('EDIT', $session->getTraining())) {
            if ($this->isGranted('VIEW', $session->getTraining())) {
                return ['session' => $session];
            }

            throw new AccessDeniedException('Action non autorisée');
        }

        return ['form' => $form->createView(), 'training' => $session->getTraining(), 'session' => $session];
    }

    /**
     * This action attach a form to the return array when the user has the permission to edit the training.
     *
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    #[Route(path: '/{id}/view', name: 'session.view', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function view(Request $request, ManagerRegistry $managerRegistry, AbstractSession $session, int $id): \Symfony\Component\HttpFoundation\RedirectResponse|array
    {
        $session = $managerRegistry->getRepository(AbstractSession::class)->find($id);
        if(!$session){
            throw new NotFoundHttpException();
        }
        if (!$this->isGranted('EDIT', $session->getTraining())) {
            if ($this->isGranted('VIEW', $session->getTraining())) {
                return ['session' => $session];
            }

            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm($session::getFormType(), $session);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $objectManager = $managerRegistry->getManager();
                $objectManager->flush();

                return $this->redirectToRoute('session.view', ['id' => $session->getId()]);
            }
        }

        $url = 'https://' . $_ENV['front_host'] . '/program/training/' . $session->getTraining()->getId() . '/' . $session->getId();
        return ['form' => $form->createView(), 'session' => $session, 'front_url' => $url];
    }

    /**
     * @param AbstractSession|null $session
     *
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     *
     * @return array
     */
    #[Route(path: '/duplicate/{id}/{inscriptionIds}', name: 'session.duplicate', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function duplicate(Request $request, ManagerRegistry $managerRegistry,  int $id, mixed $inscriptionIds = null, AbstractSession $session = null): array
    {
        $session = $managerRegistry->getRepository(AbstractSession::class)->find($id);
        if (!$session) {
            throw new NotFoundHttpException();
        }
        // we need at least one of both arguments
        if (!$session && empty($inscriptionIds)) {
            throw new MissingOptionsException('You have to pass a session id or an inscription array of ids');
        }

        // get inscriptions and session
        $inscriptions = [];
        $this->retrieveInscriptions($inscriptionIds, $inscriptions);
        if (!$session instanceof \App\Entity\Core\AbstractSession) {
            // get session
            $session = $inscriptions[0]->getSession();
        }

        // new session can't be created if user has no rights for it
        if (!$this->isGranted('EDIT', $session->getTraining())) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $cloned = clone $session;
        $form = $this->createFormBuilder($cloned)
            ->add('name', null, ['required' => true, 'label' => 'Intitulé de la session'])
            ->add('datebegin', DateType::class, ['label' => 'Date de début', 'widget' => 'single_text', 'format' => 'dd/MM/yyyy', 'html5' => false, 'required' => true])
            ->add('dateend', DateType::class, ['label' => 'Date de fin', 'widget' => 'single_text', 'format' => 'dd/MM/yyyy', 'html5' => false, 'required' => false]);

        if (!empty($inscriptions)) {
            $form
                ->add('inscriptionManagement', ChoiceType::class, ['label' => 'Choisir la méthode d\'importation des inscriptions', 'mapped' => false, 'choices' => ['Ne pas importer les inscriptions' => 'none', 'Copier les inscriptions' => 'copy', 'Déplacer les inscriptions' => 'move'], 'empty_data' => 'none', 'required' => true]);
        }

        $form = $form->getForm();
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $objectManager = $managerRegistry->getManager();
                $this->cloneSessionArrayCollections($session, $cloned, $inscriptions, $form->has('inscriptionManagement') ? $form->get('inscriptionManagement')->getData() : null);
                $objectManager->persist($cloned);
                $objectManager->flush();

                return ['session' => $cloned];
            }
        }

        return ['form' => $form->createView(), 'session' => $session, 'inscriptions' => $inscriptionIds];
    }

    #[Groups(['Default', 'session'])]
    #[Route(path: '/{id}/remove', name: 'session.remove', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'], methods: 'POST')]
    public function remove(AbstractSession $session, ManagerRegistry $managerRegistry, int $id): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $session = $managerRegistry->getRepository(AbstractSession::class)->find($id);
        if (!$session) {
            throw new NotFoundHttpException();
        }
        if (!$this->isGranted('DELETE', $session->getTraining())) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $training = $session->getTraining();
        $objectManager = $managerRegistry->getManager();
        $objectManager->remove($session);
//        $training->updateTimestamps();
        $objectManager->flush();
//        $this->get('fos_elastica.index')->refresh();

        return $this->redirectToRoute('training.view', ['id' => $training->getId()]);
    }

    protected function retrieveInscriptions(array &$inscriptionIds, array &$inscriptions): void
    {

        // retrieve inscriptions and session
        if ($inscriptionIds) {
            $inscriptions = $this->managerRegistry->getManager()
                ->getRepository(AbstractInscription::class)
                ->find($inscriptionIds);

            if (empty($inscriptions)) {
                throw new MissingOptionsException('You have to pass a session id or an inscription array of ids');
            }

            // check if all inscription come from a unique session
            $arraySessionIds = [];
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

    protected function cloneSessionArrayCollections(AbstractSession $session, AbstractSession $cloned, mixed $inscriptions, mixed $inscriptionManagement): void
    {
        $em = $this->managerRegistry->getManager();

        // clone participations
        /** @var AbstractParticipation $participation */
        foreach ($session->getParticipations() as $participation) {
            /** @var AbstractParticipation $newParticipation */
            $newParticipation = new $this->participationClass();
            $newParticipation->setSession($cloned);
            $newParticipation->setTrainer($participation->getTrainer());
            $newParticipation->setOrganization($cloned->getTraining()->getOrganization());
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
        foreach ($tmpMaterials as $tmpMaterial) {
            $newMat = clone $tmpMaterial;
            $cloned->addMaterial($newMat);
        }
    }

    private function constructAggs($aggs, $keyword, $query_filters, \Doctrine\Persistence\ManagerRegistry $managerRegistry, \App\Repository\SessionRepository $sessionRepository): array
    {
        $tabAggs = [];

        // CONSTRUCTION CENTRES
        if(isset( $aggs['training.organization.name.source'])){
            $allOrganizations = $managerRegistry->getRepository(Organization::class)->findAll();

            $i = 0; $tabOrg = [];
            //Pour chaque centre on teste la requête
            foreach($allOrganizations as $allOrganization){
                $nbSessionsOrg = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $allOrganization->getName());
                if ($nbSessionsOrg > 0) {
                    $tabOrg[$i] = [ 'key' => $allOrganization->getName(), 'doc_count' => $nbSessionsOrg];
                    ++$i;
                }
            }

            $tabAggs['training.organization.name.source']['buckets'] = $tabOrg;
        }

        // CONSTRUCTION ANNEES
        if (isset($aggs['year'])) {
            $curYear = date('Y');
            $allYears = [];
            for($i=2017; $i<=$curYear; ++$i){
                $allYears[] = $i;
            }

            $i = 0; $tabYears = [];
            //Pour chaque année on teste la requête
            foreach($allYears as $allYear){
                $nbSessionsYear = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $allYear);
                if ($nbSessionsYear > 0) {
                    $tabYears[$i] = [ 'key' => $allYear, 'doc_count' => $nbSessionsYear];
                    ++$i;
                }
            }

            $tabAggs['year']['buckets'] = $tabYears;
        }

        // CONSTRUCTION SEMESTRE
        if (isset($aggs['semester'])) {
            $allSemesters = [1, 2];
            $i = 0; $tabSemesters = [];
            //Pour chaque semestre on teste la requête
            foreach($allSemesters as $allSemester){
                $nbSessionsSem = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $allSemester);
                if ($nbSessionsSem > 0) {
                    $tabSemesters[$i] = [ 'key' => $allSemester, 'doc_count' => $nbSessionsSem];
                    ++$i;
                }
            }

            $tabAggs['semester']['buckets'] = $tabSemesters;
        }

        // CONSTRUCTION THEMES
        if(isset( $aggs['theme.name'])){
            $allThemes = $managerRegistry->getRepository(Theme::class)->findAll();

            $i = 0; $tabThemes = [];
            //Pour chaque thème on teste la requête
            foreach($allThemes as $allTheme){
                $nbSessionsThemes = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $allTheme->getName());
                if ($nbSessionsThemes > 0) {
                    $tabThemes[$i] = [ 'key' => $allTheme->getName(), 'doc_count' => $nbSessionsThemes];
                    ++$i;
                }
            }

            $tabAggs['theme.name']['buckets'] = $tabThemes;
        }

        // CONSTRUCTION INSCRIPTION (0,1,2,3)
        if( isset($aggs['registration']) ) {
            $allRegistrations = [0, 1, 2, 3];
            $i = 0; $tabReg = [];
            //Pour chaque inscription on teste la requête
            foreach($allRegistrations as $allRegistration){
                $nbSessionsReg = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $allRegistration);
                if ($nbSessionsReg > 0) {
                    $tabReg[$i] = [ 'key' => $allRegistration, 'doc_count' => $nbSessionsReg];
                    ++$i;
                }
            }

            $tabAggs['registration']['buckets'] = $tabReg;
        }

        // CONSTRUCTION STATUT (0,1,2)
        if( isset($aggs['status']) ) {
            $allStatus = [0, 1, 2];
            $i = 0; $tabStatus = [];
            //Pour chaque status on teste la requête
            foreach($allStatus as $allRectorPrefix202304Status){
                $nbSessionsStatus = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $allRectorPrefix202304Status);
                if ($nbSessionsStatus > 0) {
                    $tabStatus[$i] = [ 'key' => $allRectorPrefix202304Status, 'doc_count' => $nbSessionsStatus];
                    ++$i;
                }
            }

            $tabAggs['status']['buckets'] = $tabStatus;
        }

        // CONSTRUCTION DISPLAYONLINE (0,1)
        if( isset($aggs['displayOnline']) ) {
            $allDisplay = [0 => 'F', 1 => 'T'];
            $i = 0; $tabDis = [];
            //Pour chaque status on teste la requête
            foreach($allDisplay as $key => $display){
                $nbSessionsDis = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $key);
                if ($nbSessionsDis > 0) {
                    $tabDis[$i] = [ 'key' => $display, 'doc_count' => $nbSessionsDis];
                    ++$i;
                }
            }

            $tabAggs['displayOnline']['buckets'] = $tabDis;
        }

        // CONSTRUCTION PROMOTION (true,false) = (0,1)
        if( isset($aggs['promote']) ) {
            $allPromote = [0, 1];
            $i = 0; $tabPro = [];
            //Pour chaque promote on teste la requête
            foreach($allPromote as $promote){
                $nbSessionsPro = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $promote);
                if ($nbSessionsPro > 0) {
                    $tabPro[$i] = [ 'key' => $promote, 'doc_count' => $nbSessionsPro];
                    ++$i;
                }
            }

            $tabAggs['promote']['buckets'] = $tabPro;
        }

        // CONSTRUCTION FORMATION (nom de la formation)
        if( isset($aggs['training.name.source']) ) {
            $allTraining = $managerRegistry->getRepository(Internship::class)->findAll();
            $i = 0; $tabTra = [];
            //Pour chaque promote on teste la requête
            foreach($allTraining as $training){
                $nbSessionsTra = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $training->getName());
                if ($nbSessionsTra > 0) {
                    $tabTra[$i] = [ 'key' => $training->getName(), 'doc_count' => $nbSessionsTra];
                    ++$i;
                }
            }

            $tabAggs['training.name.source']['buckets'] = $tabTra;
        }

        // CONSTRUCTION FORMATEUR
        if( isset($aggs['participations.trainer.fullName']) ) {
            $allTrainers = $managerRegistry->getRepository(Trainer::class)->findAll();
            $i = 0; $tabTra = [];
            //Pour chaque trainer on teste la requête
            foreach($allTrainers as $allTrainer){
                $nbSessionsTra = $sessionRepository->getNbSessions($query_filters, $keyword, $aggs, $allTrainer->getId());
                if ($nbSessionsTra > 0) {
                    $tabTra[$i] = [ 'key' => $allTrainer->getFullname(), 'doc_count' => $nbSessionsTra];
                    ++$i;
                }
            }

            $tabAggs['participations.trainer.fullName']['buckets'] = $tabTra;
        }


        return $tabAggs;
    }

    public function computeInscriptionsStats(ManagerRegistry $managerRegistry, $session): array
    {
        /** @var EntityManager $objectManager */
        $objectManager = $managerRegistry->getManager();
        $stats = [];
        if ($session->getRegistration() > AbstractSession::REGISTRATION_DEACTIVATED) {
            $query = $objectManager
                ->createQuery('SELECT s, count(i) FROM App\Entity\Term\Inscriptionstatus s
                        JOIN App\Entity\Core\AbstractInscription i WITH i.inscriptionstatus = s
                        WHERE i.session = :session
                        GROUP BY s.id')
                ->setParameter('session', $this);

            $result = $query->getResult();
            foreach ($result as $status) {
                $stats[] = ['id' => $status[0]->getId(), 'name' => $status[0]->getName(), 'status' => $status[0]->getStatus(), 'count' => (int)$status[1]];
            }
        }

        return $stats;
    }

    public function computePresencesStats(ManagerRegistry $managerRegistry, $session): array
    {
        /** @var EntityManager $objectManager */
        $objectManager = $managerRegistry->getManager();
        $statsPres = [];
        $queryPres = $objectManager
            ->createQuery('SELECT s, count(i) FROM App\Entity\Term\Presencestatus s
                            JOIN App\Entity\Core\AbstractInscription i WITH i.presencestatus = s
                            WHERE i.session = :session
                            GROUP BY s.id')
            ->setParameter('session', $this);

        $resultPres = $queryPres->getResult();
        foreach ($resultPres as $resultPre) {
            $statsPres[] = ['id' => $resultPre[0]->getId(), 'name' => $resultPre[0]->getName(), 'status' => $resultPre[0]->getStatus(), 'count' => (int)$resultPre[1]];
        }

        return $statsPres;
    }
}

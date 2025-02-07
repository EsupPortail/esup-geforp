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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\SecurityBundle\Security;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTraining;
use App\Repository\TrainingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route(path: '/training')]
abstract class AbstractTrainingController extends AbstractController
{
    protected $sessionClass = AbstractSession::class;

    /**
     * @var int[]
     */
    private const ALL_SEMESTERS = [1, 2];
    /**
     * @var int[]
     */
    private const ALL_PROMOTE = [0, 1];
    public function __construct(private readonly \Doctrine\Persistence\ManagerRegistry $managerRegistry)
    {
    }

    #[Rest\View(serializerGroups: ['Default', 'training'], serializerEnableMaxDepthChecks: true)]
    #[Route(path: '/search', name: 'training.search', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function search(Request $request, ManagerRegistry $managerRegistry, TrainingRepository $trainingRepository, AccessRightRegistry $accessRightRegistry)
    {
        $keywords = $request->request->get('keywords', 'NO KEYWORDS');
        $filters = $request->request->get('filters', []);
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
        $tabAggs = $this->constructAggs($aggs, $keywords, $query_filters, $managerRegistry, $trainingRepository);

        // Concatenation des resultats
        $ret['aggs'] = $tabAggs;

        return $ret;
    }

    #[Rest\View(serializerGroups: ['Default', 'training'], serializerEnableMaxDepthChecks: true)]
    #[Route(path: '/create/{type}', name: 'training.create', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function create(Request $request, ManagerRegistry $managerRegistry, $type): array
    {
        $class = Internship::class;
        /** @var AbstractTraining $training */
        $training = new $class();
        try {
            $training->setOrganization($this->getUser()->getOrganization());
            $training->setInstitution($this->getUser()->getOrganization()->getInstitution());
        }
        catch (\Exception $exception) {
            return [$exception->getMessage()];
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
                $objectManager = $managerRegistry->getManager();
                $objectManager->persist($training);
                $objectManager->flush();
            }
        }

        return ['training' => $training, 'form' => $form->createView()];
        //return new Response(json_encode(array('training' => $training, 'form' => $form->createView())));

    }

    #[Route(path: '/{id}/view', name: 'training.view', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[IsGranted('VIEW', subject: 'training')]
    #[Rest\View(serializerGroups: ['Default', 'training'], serializerEnableMaxDepthChecks: true)]
    public function view(Request $request,ManagerRegistry $managerRegistry, AbstractTraining $training, int $id): array|View
    {
        $training = $managerRegistry->getRepository(AbstractTraining::class)->find($id);
        if (!$training) {
            throw new NotFoundHttpException();
        }
        if (!$this->isGranted('EDIT', $training)) {
            if ($this->isGranted('VIEW', $training)) {
                return ['training' => $training];
            }

            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm($training::getFormType(), $training);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $training->setUpdatedAt(new \DateTime('now'));
                $objectManager = $managerRegistry->getManager();
                $objectManager->flush();
            }
        }

        $return = ['form' => $form->createView(), 'training' => $training];

        // if the training is single session, add 'session' to the serialization groups
        if ($training instanceof SingleSessionTraining) {
            $view = new View($return);
            $view->setSerializationContext(SerializationContext::create()->setGroups(['Default', 'training', 'session']));

            return $view;
        }

        return $return;
    }


    #[Route(path: '/{id}/remove', name: 'training.remove', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'], methods: ['POST'])]
    #[IsGranted('DELETE', subject: 'training')]
    #[Rest\View(serializerGroups: ['Default', 'training'], serializerEnableMaxDepthChecks: true)]
    public function remove(ManagerRegistry $managerRegistry, AbstractTraining $training, int $id): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $training = $managerRegistry->getRepository(AbstractTraining::class)->find($id);
        if (!$training) {
            throw new NotFoundHttpException();
        }
        $objectManager = $managerRegistry->getManager();
        $objectManager->remove($training);
        $objectManager->flush();
//        $this->get('fos_elastica.index')->refresh();

        return $this->redirectToRoute('training.search');
    }

    #[Rest\View(serializerGroups: ['Default', 'training'], serializerEnableMaxDepthChecks: true)]
    #[Route(path: '/choosetypeduplicate', name: 'training.choosetypeduplicate', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function chooseTypeDuplicate(Request $request): array
    {
        $typeChoices = [];
        foreach ($this->get('sygefor_training.type.registry')->getTypes() as $type => $entity) {
            $typeChoices[$type] = $entity['label'];
        }

        $form = $this->createFormBuilder()
            ->add('duplicatedType', 'choice', ['label'    => 'Type de stage', 'choices'  => $typeChoices, 'required' => true, 'attr'     => ['title' => 'Type de la formation ciblée']])->getForm();

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                return ['type' => $form->get('duplicatedType')->getData()];
            }
        }

        return ['form' => $form->createView()];
    }


    #[Route(path: '/duplicate/{id}/{type}', name: 'training.duplicate', options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[Rest\View(serializerGroups: ['Default', 'training'], serializerEnableMaxDepthChecks: true)]
    public function duplicate(Request $request,ManagerRegistry $managerRegistry, AbstractTraining $training, $type, int $id): array
    {
        $training = $managerRegistry->getRepository(AbstractTraining::class)->find($id);
        if (!$training) {
            throw new NotFoundHttpException();
        }
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
            } elseif ($training->getSessions() instanceof \Doctrine\Common\Collections\ArrayCollection && $training->getSessions()->count() > 0) {
                $session = clone $training->getSessions()->last();
            } else {
                $session = new $this->sessionClass;
            }

            $session->setNumberOfRegistrations(0);
            $session->setTraining($cloned);
            $cloned->setSession($session);
        }

        // verify if training category matches with new type
        /** @var RepositoryFactory $objectRepository */
        $objectRepository = $this->managerRegistry->getRepository('SygeforTrainingBundle:Training\Term\Trainingcategory');
        /** @var QueryBuilder $qb */
        $qb = $objectRepository->createQueryBuilder('t')
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

                return ['form' => $form->createView(), 'training' => $cloned];
            }
        }

        return ['form' => $form->createView()];
    }

    protected function mergeArrayCollectionsAndFlush(AbstractTraining $dest, AbstractTraining $source): void
    {
        $objectManager = $this->managerRegistry->getManager();

        // clone common arrayCollections
        if (method_exists($source, 'getTags')) {
            $dest->duplicateArrayCollection('addTag', $source->getTags());
        }

        // clone duplicate materials
        $tmpMaterials = $source->getMaterials();
        if ( ! empty($tmpMaterials)) {
            foreach ($tmpMaterials as $tmpMaterial) {
                $newMat = clone $tmpMaterial;
                $dest->addMaterial($newMat);
            }
        }

        $objectManager->persist($dest);
        $objectManager->flush();
    }

    #[Route(path: '/{id}/bilan.{_format}', name: 'training.balancesheet', requirements: ['_format' => 'csv|xls|xlsx'], options: ['expose' => true], defaults: ['_format' => 'xls'], methods: 'GET')]
    public function balanceSheet(AbstractTraining $training, ManagerRegistry $managerRegistry, int $id)
    {
        $training = $managerRegistry->getRepository(AbstractTraining::class)->find($id);
        if (!$training) {
            throw new NotFoundHttpException();
        }
        $trainingBalanceSheet = new TrainingBalanceSheet($training, $this->get('phpexcel'), $this->container);

        return $trainingBalanceSheet->getResponse();
    }

    private function constructAggs($aggs, $keyword, $query_filters, \Doctrine\Persistence\ManagerRegistry $managerRegistry, \App\Repository\TrainingRepository $trainingRepository)
    {
        $tabAggs = [];

        // CONSTRUCTION CENTRES
        if(isset( $aggs['training.organization.name.source'])){
            $allOrganizations = $managerRegistry->getRepository(Organization::class)->findAll();

            $i = 0; $tabOrg = [];
            //Pour chaque centre on teste la requête
            foreach($allOrganizations as $allOrganization){
                $nbTrainingsOrg = $trainingRepository->getNbTrainings($query_filters, $keyword, $aggs, $allOrganization->getName());
                if ($nbTrainingsOrg > 0) {
                    $tabOrg[$i] = [ 'key' => $allOrganization->getName(), 'doc_count' => $nbTrainingsOrg];
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
                $nbTrainingsYear = $trainingRepository->getNbTrainings($query_filters, $keyword, $aggs, $allYear);
                if ($nbTrainingsYear > 0) {
                    $tabYears[$i] = [ 'key' => $allYear, 'doc_count' => $nbTrainingsYear];
                    ++$i;
                }
            }

            $tabAggs['year']['buckets'] = $tabYears;
        }

        // CONSTRUCTION SEMESTRE
        if (isset($aggs['semester'])) {
            $i = 0; $tabSemesters = [];
            //Pour chaque semestre on teste la requête
            foreach(self::ALL_SEMESTERS as $semester){
                $nbTrainingsSem = $trainingRepository->getNbTrainings($query_filters, $keyword, $aggs, $semester);
                if ($nbTrainingsSem > 0) {
                    $tabSemesters[$i] = [ 'key' => $semester, 'doc_count' => $nbTrainingsSem];
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
                $nbTrainingsThemes = $trainingRepository->getNbTrainings($query_filters, $keyword, $aggs, $allTheme->getName());
                if ($nbTrainingsThemes > 0) {
                    $tabThemes[$i] = [ 'key' => $allTheme->getName(), 'doc_count' => $nbTrainingsThemes];
                    ++$i;
                }
            }

            $tabAggs['theme.name']['buckets'] = $tabThemes;
        }

        // CONSTRUCTION PROMOTION (true,false) = (0,1)
        if( isset($aggs['nextSession.promote']) ) {
            $i = 0; $tabPro = [];
            //Pour chaque promote on teste la requête
            foreach(self::ALL_PROMOTE as $promote){
                $nbTrainingsPro = $trainingRepository->getNbTrainings($query_filters, $keyword, $aggs, $promote);
                if ($nbTrainingsPro > 0) {
                    $tabPro[$i] = [ 'key' => $promote, 'doc_count' => $nbTrainingsPro];
                    ++$i;
                }
            }

            $tabAggs['nextSession.promote']['buckets'] = $tabPro;
        }

        // CONSTRUCTION FORMATEUR
        if( isset($aggs['trainers.fullName']) ) {
            $allTrainers = $managerRegistry->getRepository(Trainer::class)->findAll();
            $i = 0; $tabTra = [];
            //Pour chaque trainer on teste la requête
            foreach($allTrainers as $allTrainer){
                $nbTrainingsTra = $trainingRepository->getNbTrainings($query_filters, $keyword, $aggs, $allTrainer->getId());
                if ($nbTrainingsTra > 0) {
                    $tabTra[$i] = [ 'key' => $allTrainer->getFullname(), 'doc_count' => $nbTrainingsTra];
                    ++$i;
                }
            }

            $tabAggs['trainers.fullName']['buckets'] = $tabTra;
        }


        return $tabAggs;
    }

}

<?php

namespace App\Controller\Core;

use App\AccessRight\AccessRightRegistry;
use App\Entity\Back\Internship;
use App\Entity\Back\Session;
use App\Entity\Term\Theme;
use App\Entity\Back\Organization;
use App\Entity\Back\Trainer;
use App\Form\Type\AbstractCareerType;
use App\Form\Type\CareerSearchType;
use App\Form\Type\CareerType;
use App\Repository\CareerRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Bundle\SecurityBundle\Security;
use JMS\Serializer\SerializationContext;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Core\AbstractCareer;
use App\Entity\Core\AbstractTraining;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\SerializerInterface;

#[Route(path: '/career')]
abstract class AbstractCareerController extends AbstractController
{
    protected $careerClass = AbstractCareer::class;
    protected $trainingClass = Internship::class;

    public function __construct(private readonly \Doctrine\Persistence\ManagerRegistry $managerRegistry)
    {
    }
    #[Groups(['Default', 'career'])]
    #[Rest\View(serializerGroups: ['Default', 'career'], serializerEnableMaxDepthChecks: true)]
    #[Route(path: '/search', name: 'career.search', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function search(SerializerInterface $serializer, Request $request, ManagerRegistry $managerRegistry, CareerRepository $careerRepository, AccessRightRegistry $accessRightRegistry): array
    {
        $keywords = (string)$request->request->get('keywords', '');
        $filters = $request->request->all('filters') ?: [];
        $query_filters = $request->request->all('query_filters') ?: [];
        $aggs = $request->request->all('aggs') ?: [];
        $query = $request->request->all('query') ?: [];
        $page = $request->request->get('page', 1);
        $size = $request->request->get('size', 10);
        $sorts = $request->request->all('sorts') ?: [];


        // security check : career : 'sygefor_training.rights.training.all.view' -> id=9
        if(!$accessRightRegistry->hasAccessRight(9)) {
            // restriction to user's organization
            $filters['career.organization.name.source'] = $this->getUser()->getOrganization()->getName();
        }

        // Recherche avec les filtres
        $ret = $careerRepository->getCareersList(keyword: $keywords, filters: $filters, page: (int)$page, pageSize: (int)$size, sorts: $sorts);
        $tabAggs = $this->constructAggs($aggs, $keywords, $query_filters, $managerRegistry, $careerRepository);

        // Recherche avec query (pour autocompletion)
        // on transforme le champ 'query' en 'keywords'
        if (isset($query['match']['name.autocomplete']['query'])) {
            $keywords = $query['match']['name.autocomplete']['query'];
            $ret = $careerRepository->getCareersList($keywords, $filters, $page, $size, $sorts);
        }

        // Concatenation des resultats
        $ret['aggs'] = $tabAggs;

        return $ret;
    }

    #[Groups(['Default', 'career'])]
    #[Rest\View(serializerGroups: ['Default', 'career'], serializerEnableMaxDepthChecks: true)]
    #[Route(path: '/create', name: 'career.create', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function create(Request $request, ManagerRegistry $managerRegistry): array
    {
        /** @var AbstractCareer $career */
        $career = new $this->careerClass();
        try {
            $career->setOrganization($this->getUser()->getOrganization());
        } catch (\Exception $exception) {
            return [$exception->getMessage()];
        }

        $training = new $this->trainingClass();
        try {
            $training->setOrganization($this->getUser()->getOrganization());
        } catch (\Exception $exception) {
            return [$exception->getMessage()];
        }

        //career can't be created if user has no rights for it
        if (!$this->isGranted('CREATE', $training)) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm($career::getFormType(), $career);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $career->setCreatedAt(new \DateTime('now'));
                $career->setUpdatedAt(new \DateTime('now'));
                $objectManager = $managerRegistry->getManager();
                $objectManager->persist($career);
                $objectManager->flush();
            }
        }

        return ['career' => $career, 'form' => $form->createView()];
        //return new Response(json_encode(array('training' => $training, 'form' => $form->createView())));

    }
    #[Groups(['Default', 'career'])]
    #[Route(path: '/{id}/view', name: 'career.view', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[Rest\View(serializerGroups: ["Default", "career"] ,serializerEnableMaxDepthChecks: true)]
    public function view(SerializerInterface $serializer, Request $request,ManagerRegistry $managerRegistry, AbstractCareer $career,int $id): view|array
    {
        $career = $managerRegistry->getRepository(AbstractCareer::class)->find($id);
        if (!$career) {
            throw new NotFoundHttpException();
        }

        $trainingRight = new $this->trainingClass();
        try {
            $trainingRight->setOrganization($this->getUser()->getOrganization());
        } catch (\Exception $exception) {
            return [$exception->getMessage()];
        }

        if (!$this->isGranted('EDIT', $trainingRight)) {
            if ($this->isGranted('VIEW', $trainingRight)) {
                return ['career' => $career];
            }

            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm($career::getFormType(), $career);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $career->setUpdatedAt(new \DateTime('now'));
                $objectManager = $managerRegistry->getManager();
                $objectManager->flush();
            }
        }

        $return = ['form' => $form->createView(), 'career' => $career];
        return $return;
    }

    #[Groups(['Default', 'career'])]
    #[Route(path: '/attach/{training}', name: 'career.attach', requirements: ['training' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[IsGranted('VIEW', subject: 'training')]
    #[Rest\View(serializerGroups: ["Default", "career"] ,serializerEnableMaxDepthChecks: true)]
    public function attach(SerializerInterface $serializer, Request $request,ManagerRegistry $managerRegistry, AbstractTraining $training): array
    {
        /** @var AbstractCareer $career */
        $career = new $this->careerClass();

        if (!$this->isGranted('EDIT', $training)) {
            if ($this->isGranted('VIEW', $training)) {
                return ['career' => $career];
            }
            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm(CareerSearchType::class);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $data = $form->getData();

                $career = $data['career'];
                /** @var AbstractCareer $existingCareer */
                foreach ($career->getTrainings() as $existingtraining) {
                    if ($existingtraining === $training) {
                        $form->get('career')->addError(new FormError('Ce parcours est déjà associé à ce stage.'));
                        break;
                    }
                }

                $career->addTraining($training);
                $career->setUpdatedAt(New \DateTime('now'));
                $training->setCareer($career);
                $objectManager = $managerRegistry->getManager();
                $objectManager->persist($career);
                $objectManager->persist($training);
                $objectManager->flush();
            }
        }

        return ['form' => $form->createView(), 'career' => $career];

    }

    #[Groups(['Default', 'career', 'training'])]
    #[Route(path: '/detach/{training}', name: 'career.detach', requirements: ['training' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[IsGranted('VIEW', subject: 'training')]
    #[Rest\View(serializerGroups: ["Default", "career"] ,serializerEnableMaxDepthChecks: true)]
    public function detach(SerializerInterface $serializer, Request $request,ManagerRegistry $managerRegistry, AbstractTraining $training): void
    {
        $career = $training->getCareer();
        if (!$career) {
            throw new NotFoundHttpException();
        }

        $career->removeTraining($training);
        $training->setCareer(null);
        $objectManager = $managerRegistry->getManager();
        $objectManager->flush();
    }

    #[Route(path: '/delete/{career}', name: 'career.delete', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'], methods: ['POST'])]
    public function delete(ManagerRegistry $managerRegistry, AbstractCareer $career): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        if (!$career) {
            throw new NotFoundHttpException();
        }

        // Droits
        $trainingRight = new $this->trainingClass();
        try {
            $trainingRight->setOrganization($this->getUser()->getOrganization());
        } catch (\Exception $exception) {
            return [$exception->getMessage()];
        }

        if (!$this->isGranted('DELETE', $trainingRight)) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $objectManager = $managerRegistry->getManager();
        $objectManager->remove($career);
        $objectManager->flush();

        return $this->redirectToRoute('career.search');
    }



    private function constructAggs($aggs, $keyword, $query_filters, \Doctrine\Persistence\ManagerRegistry $managerRegistry, \App\Repository\CareerRepository $careerRepository): array
    {
        $tabAggs = [];

        // CONSTRUCTION CENTRES
        if(isset( $aggs['training.organization.name.source'])){
            $allOrganizations = $managerRegistry->getRepository(Organization::class)->findAll();

            $i = 0; $tabOrg = [];
            //Pour chaque centre on teste la requête
            foreach($allOrganizations as $allOrganization){
                $nbTrainingsOrg = $careerRepository->getNbTrainings($query_filters, $keyword, $aggs, $allOrganization->getName());
                if ($nbTrainingsOrg['total'] > 0) {
                    $tabOrg[$i] = [ 'key' => $allOrganization->getName(), 'doc_count' => $nbTrainingsOrg['total']];
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
                $nbTrainingsYear = $careerRepository->getNbTrainings($query_filters, $keyword, $aggs, $allYear);
                if ($nbTrainingsYear['total'] > 0) {
                    $tabYears[$i] = [ 'key' => $allYear, 'doc_count' => $nbTrainingsYear['total']];
                    ++$i;
                }
            }

            $tabAggs['year']['buckets'] = $tabYears;
        }

        // CONSTRUCTION SEMESTRE
        if (isset($aggs['semester'])) {

            $tabSemesters = [];
            $i = 0;
            //Pour chaque semestre on teste la requête
            foreach(self::ALL_SEMESTERS as $semester){
                $nbTrainingsSem = $careerRepository->getNbTrainings($query_filters, $keyword, $aggs, $semester, 'semester');
                if ($nbTrainingsSem['total'] > 0) {
                    $tabSemesters[$i] = [ 'key' => $semester, 'doc_count' => $nbTrainingsSem['total']];
                    ++$i;
                }
            }

            $tabAggs['semester']['buckets'] = $tabSemesters;
        }

        // CONSTRUCTION NUMÉRO
        if (isset($aggs['training.number'])) {
            $allNumbers = $managerRegistry->getRepository(AbstractTraining::class)->findAll();

            $i = 0; $tabNumbers = [];
            //Pour chaque semestre on teste la requête
            foreach($allNumbers as $allNumber){
                $trainingNumber = $allNumber->getNumber();
                $nbTrainingsNum = $careerRepository->getNbTrainings($query_filters, $keyword, $aggs, $trainingNumber, 'training.number');

                if ($nbTrainingsNum['total'] > 0) {
                    $tabNumbers[$i] = [ 'key' => $allNumber, 'doc_count' => $nbTrainingsNum['total']];
                    ++$i;
                }
            }

            $tabAggs['training.number']['buckets'] = $tabNumbers;
        }

        // CONSTRUCTION THEMES
        if(isset( $aggs['theme.name'])){
            $allThemes = $managerRegistry->getRepository(Theme::class)->findAll();

            $i = 0; $tabThemes = [];
            //Pour chaque thème on teste la requête
            foreach($allThemes as $allTheme){
                $nbTrainingsThemes = $careerRepository->getNbTrainings($query_filters, $keyword, $aggs, $allTheme->getName());
                if ($nbTrainingsThemes['total'] > 0) {
                    $tabThemes[$i] = [ 'key' => $allTheme->getName(), 'doc_count' => $nbTrainingsThemes['total']];
                    ++$i;
                }
            }

            $tabAggs['theme.name']['buckets'] = $tabThemes;
        }

        $tabAggs['training.typeLabel.source'] = ['buckets' => []];
        // CONSTRUCTION TYPE
        if(isset( $aggs['training.typeLabel.source'])){
            $allTypes = $managerRegistry->getRepository(Session::class)
                ->createQueryBuilder('s')
                ->select('DISTINCT tc.trainingType')
                ->join('s.training', 't')
                ->join('t.category', 'tc')
                ->getQuery()
                ->getSingleColumnResult();

            $tabTypes = [];
            //Pour chaque type on teste la requête
            foreach($allTypes as $allType){
                $nbTrainingsTypes = $careerRepository->getNbTrainings($query_filters, $keyword, $aggs, $allType);
                 //dump($allType, $nbTrainingsTypes);
                if ($nbTrainingsTypes['total'] > 0) {
                    $tabTypes[] = [ 'key' => $allType, 'doc_count' => $nbTrainingsTypes['total']];
                }
            }

            $tabAggs['training.typeLabel.source']['buckets'] = $tabTypes;
        }

        // CONSTRUCTION CATÉGORIE
        if (isset($aggs['training.category.source'])) {
            $allCategories = $managerRegistry->getRepository(\App\Entity\Term\Trainingcategory::class)->findAll();

            $tabCategory = [];
            foreach ($allCategories as $category) {
                $nbCategory = $careerRepository->getNbTrainings($query_filters, $keyword, $aggs, $category->getName(), 'training.category');

                if ($nbCategory['total'] > 0) {
                    $tabCategory[] = [
                        'key'       => $category->getName(),
                        'filter'    => $category->getName(),
                        'doc_count' => $nbCategory['total']
                    ];
                }
            }
            $tabAggs['training.category.source']['buckets'] = $tabCategory;
        }


        // CONSTRUCTION PROMOTION (true,false) = (0,1)
        if( isset($aggs['nextSession.promote']) ) {
            $i = 0; $tabPro = [];
            //Pour chaque promote on teste la requête
            foreach(self::ALL_PROMOTE as $promote){
                $nbTrainingsPro = $careerRepository->getNbTrainings($query_filters, $keyword, $aggs, $promote);
                if ($nbTrainingsPro['total'] > 0) {
                    $tabPro[$i] = [ 'key' => $promote, 'doc_count' => $nbTrainingsPro['total']];
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
                $nbTrainingsTra = $careerRepository->getNbTrainings($query_filters, $keyword, $aggs, $allTrainer->getId());
                if ($nbTrainingsTra['total'] > 0) {
                    $tabTra[$i] = [ 'key' => $allTrainer->getFullname(), 'doc_count' => $nbTrainingsTra['total']];
                    ++$i;
                }
            }

            $tabAggs['trainers.fullName']['buckets'] = $tabTra;
        }


        return $tabAggs;
    }

}

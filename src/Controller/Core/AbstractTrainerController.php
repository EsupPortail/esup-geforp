<?php

namespace App\Controller\Core;

use App\AccessRight\AccessRightRegistry;
use App\Entity\Back\Inscription;
use App\Entity\Back\Institution;
use App\Entity\Back\Organization;
use App\Entity\Back\Trainer;
use App\Entity\Core\AbstractInstitution;
use App\Entity\Core\AbstractTraining;
use App\Entity\Term\Trainertype;
use App\Form\Type\AbstractTrainerType;
use App\Repository\TrainerRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\Type\ChangeOrganizationType;
use App\Entity\Core\AbstractTrainer;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class TrainerController.
 *
 */
#[Route(path: '/trainer')]
abstract class AbstractTrainerController extends AbstractController
{
    protected $trainerClass = AbstractTrainer::class;
    /**
     * @var int[]
     */
    private const array ALL_STATUS = [0, 1];
    /**
     * @var int[]
     */
    private const array ALL_PUB = [0, 1];
    /**
     * @var int[]
     */
    private const array ALL_ARCH = [0, 1];

    #[Route(path: '/search', name: 'trainer.search', options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[Groups(["Default", "trainer"])]
    #[Rest\View(serializerGroups: ['Default', 'trainer'], serializerEnableMaxDepthChecks: true)]
    public function search(Request $request, SerializerInterface $serializer, ManagerRegistry $managerRegistry, TrainerRepository $trainerRepository, AccessRightRegistry $accessRightRegistry): array
    {
        $keywords = $request->request->get('keywords', '');
        $filters = $request->request->all('filters') ?: [];
        $query_filters = $request->request->all('query_filters') ?: [];
        $aggs = $request->request->all('aggs') ?: [];
        $query = $request->request->all('query') ?: [];
        $page = $request->request->get('page', 1);
        $size = $request->request->get('size', 10);
        $sorts = $request->request->all('sorts') ?? [];
        $fields = $request->request->all('fields') ?? [];

        // security check : trainer : 'sygefor_trainer.rights.inscription.all.view' -> id=33
       if (!$accessRightRegistry->hasAccessRight(33)) {
            // restriction to user's organization
            $filters['organization.name.source'] = $this->getUser()->getOrganization()->getName();
       }

        // Recherche avec les filtres
        $ret = $trainerRepository->getTrainersList($keywords, $filters, $page, $size, $sorts, $fields);
        $tabAggs = $this->constructAggs($aggs, $keywords, $query_filters, $managerRegistry, $trainerRepository);

        // Recherche avec query (pour autocompletion)
        // on transforme le champ 'query' en 'keywords'
        if (isset($query['filtered']['query']['match']['fullName.autocomplete']['query'])) {
            $keywords = $query['filtered']['query']['match']['fullName.autocomplete']['query'];
            $ret = $trainerRepository->getTrainersList($keywords, $filters, $page, $size, $sorts, $fields);
        }

        // Concatenation des resultats
        $ret['aggs'] = $tabAggs;


        return $ret;
    }

    #[Route(path: '/create', name: 'trainer.create', options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[Groups(["Default", "trainee"])]
    #[Rest\View(serializerGroups: ['Default', 'trainee'], serializerEnableMaxDepthChecks: true)]
    public function create(Request $request, ManagerRegistry $managerRegistry, SerializerInterface $serializer): array
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
                $objectManager = $managerRegistry->getManager();
                $objectManager->persist($trainer);
                $objectManager->flush();
            }
        }

        return ['form' => $form->createView(), 'trainer' => $trainer];
    }


    #[Route(path: '/{id}/view', name: 'trainer.view', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[IsGranted('VIEW', subject: 'trainer')]
    #[Rest\View(serializerGroups: ['Default', 'trainer'], serializerEnableMaxDepthChecks: true)]
    #[Groups(["Default", "trainer"])]
    public function view(AbstractTrainer $trainer, Request $request, ManagerRegistry $managerRegistry, SerializerInterface $serializer, int $id): array
    {
        $trainer = $managerRegistry->getRepository(AbstractTrainer::class)->find($id);

        if (!$trainer) {
            throw new NotFoundHttpException();
        }
        if (!$this->isGranted('EDIT', $trainer)) {
            if ($this->isGranted('VIEW', $trainer)) {
                return ['trainer' => $trainer];
            }

            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm(AbstractTrainerType::class, $trainer);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() &&$form->isValid()) {
                $managerRegistry->getManager()->flush();
            }
        }

        return ['form' => $form->createView(), 'trainer' => $trainer];
    }

    #[Route(path: '/{id}/changeorg', name: 'trainer.changeorg', options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[IsGranted('EDIT', subject: 'trainer')]
    #[Rest\View(serializerGroups: ['Default', 'trainer'], serializerEnableMaxDepthChecks: true)]
    public function changeOrganization(SerializerInterface $serializer, Request $request, AbstractTrainer $trainer, ManagerRegistry $managerRegistry, int $id): array
    {
        $trainer = $managerRegistry->getRepository(\App\Entity\Core\AbstractTrainer::class)->find($id);
        if (!$trainer) {
            throw new NotFoundHttpException();
        }
        // security check
        /*        if (!$this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_core.access_right.trainer.all.update')) {
                    throw new AccessDeniedException();
                } */

        $form = $this->createForm(ChangeOrganizationType::class, $trainer);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $managerRegistry->getManager()->flush();
            }
        }

        return ['form' => $form->createView(), 'trainer' => $trainer];
    }

    #[Route(path: '/{id}/remove', name: 'trainer.delete', options: ['expose' => true], defaults: ['_format' => 'json'], methods: 'POST')]
    #[IsGranted('DELETE', subject: 'trainer')]
    #[Rest\View(serializerGroups: ['Default', 'trainee'], serializerEnableMaxDepthChecks: true)]
    public function delete(AbstractTrainer $trainer, ManagerRegistry $managerRegistry, int $id): void
    {
        $trainer = $managerRegistry->getRepository(\App\Entity\Core\AbstractTrainer::class)->find($id);
        if (!$trainer) {
            throw new NotFoundHttpException();
        }
        $objectManager = $managerRegistry->getManager();
        $objectManager->remove($trainer);
        $objectManager->flush();

    }

    private function constructAggs($aggs, $keyword, $query_filters, \Doctrine\Persistence\ManagerRegistry $managerRegistry, \App\Repository\TrainerRepository $trainerRepository): array
    {
        $tabAggs = [];

        // CONSTRUCTION CENTRES
        if (isset($aggs['organization.name.source'])) {
            $allOrganizations = $managerRegistry->getRepository(Organization::class)->findAll();

            $i = 0;
            $tabOrg = [];
            //Pour chaque centre on teste la requête
            foreach ($allOrganizations as $allOrganization) {
                $nbTrOrg = $trainerRepository->getNbTrainers($query_filters, $keyword, $aggs, $allOrganization->getName());
                if ($nbTrOrg['total'] > 0) {
                    $tabOrg[$i] = ['key' => $allOrganization->getName(), 'doc_count' => $nbTrOrg['total']];
                    ++$i;
                }
            }

            $tabAggs['organization.name.source']['buckets'] = $tabOrg;
        }

        // CONSTRUCTION ETABLISSEMENT
        if (isset($aggs['institution.name.source'])) {
            $allInstitutions = $managerRegistry->getRepository(Institution::class)->findAll();

            $i = 0;
            $tabInst = [];
            //Pour chaque etablissement on teste la requête
            foreach ($allInstitutions as $allInstitution) {
                $nbTrInst = $trainerRepository->getNbTrainers($query_filters, $keyword, $aggs, $allInstitution->getName());
                if ($nbTrInst['total'] > 0) {
                    $tabInst[$i] = ['key' => $allInstitution->getName(), 'doc_count' => $nbTrInst['total']];
                    ++$i;
                }
            }

            $tabAggs['institution.name.source']['buckets'] = $tabInst;
        }

        // CONSTRUCTION STATUT
        if (isset($aggs['isOrganization'])) {
            $i = 0;
            $tabSta = [];
            foreach ([0, 1] as $status) {
                $nbTrSt = $trainerRepository->getNbTrainers($query_filters, $keyword, ['isOrganization' => $status], $status);
                if ($nbTrSt['total'] > 0) {
                    $tabSta[$i] = ['key' => (string)$status, 'doc_count' => $nbTrSt['total']];
                    ++$i;
                }
            }

            $tabAggs['isOrganization']['buckets'] = $tabSta;
        }

        // CONSTRUCTION INTERVENANT
        $tabAggs['trainerType.source'] = ['buckets' => []];

        if (isset($aggs['trainerType.source'])) {
            $qb = $managerRegistry->getRepository(Inscription::class)->createQueryBuilder('i');
            $qb->innerJoin('i.session', 's')
                ->innerJoin('s.participations', 'p')
                ->innerJoin('p.trainer', 'trainer')
                ->innerJoin('trainer.trainertype', 'trainerType')
                ->select('trainerType.name AS typeName, COUNT(i.id) AS total')
                ->groupBy('trainerType.id')
                ->orderBy('total', 'DESC');

            $results = $qb->getQuery()->getResult();
            $buckets = [];
            //Pour chaque Type d'intervenant on teste la requête
            foreach ($results as $row) {
                $buckets[] = [
                    'key' => $row['typeName'],
                    'doc_count' => $row['total'],
                ];
            }

            $tabAggs['trainerType.source']['buckets'] = $buckets;
        }

        // CONSTRUCTION PUBLIE
        if (isset($aggs['isPublic'])) {
            $i = 0;
            $tabPub = [];
            foreach ([0, 1] as $pub) {
                $nbTrPub = $trainerRepository->getNbTrainers($query_filters, $keyword, ['isPublic' => $pub], $pub);
                if ($nbTrPub['total'] > 0) {
                    $tabPub[$i] = ['key' => (string)$pub, 'doc_count' => $nbTrPub['total']];
                    ++$i;
                }
            }

            $tabAggs['isPublic']['buckets'] = $tabPub;
        }

        // CONSTRUCTION ARCHIVE
        if (isset($aggs['isArchived'])) {
            $i = 0;
            $tabArch = [];
            foreach ([0, 1] as $arch) {
                $nbTrArch = $trainerRepository->getNbTrainers($query_filters, $keyword, ['isArchived' => $arch], $arch);
                if ($nbTrArch['total'] > 0) {
                    $tabArch[$i] = ['key' => (string)$arch, 'doc_count' => $nbTrArch['total']];
                    ++$i;
                }
            }

            $tabAggs['isArchived']['buckets'] = $tabArch;
        }

        return $tabAggs;
    }
}

<?php

namespace App\Controller\Core;

use App\Entity\Back\Institution;
use App\Entity\Back\Presence;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use http\Env\Response;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use App\Form\Type\ChangeOrganizationType;
use App\Entity\Core\AbstractInstitution;
use App\Form\Type\InstitutionType;
use App\Form\Type\BaseInstitutionType;
use App\Entity\Back\Organization;
use App\Repository\InstitutionRepository;


#[Route(path: '/institution')]
abstract class AbstractInstitutionController extends AbstractController
{
    protected string $institutionClass = AbstractInstitution::class;

    #[Groups(['Default', 'institution'])]
    #[Route(path: '/search', name: 'institution.search', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function search(Request $request, ManagerRegistry $managerRegistry, InstitutionRepository $institutionRepository): JsonResponse
    {
        $keywords = $request->request->get('keywords', 'NO KEYWORDS');
        $filters = $request->request->all('filters', 'NO FILTERS');
        $query_filters = $request->request->all('query_filters', 'NO QUERY FILTERS');
        $aggs = $request->request->all( 'aggs','NO AGGS');
        $page = $request->request->get('page', 'NO PAGE');
        $size = $request->request->get('size', 'NO SIZE');

        // Recherche avec les filtres
        $ret = $institutionRepository->getInstitutionsList($keywords, $filters, $page, $size);
        $tabAggs = $this->constructAggs($aggs, $keywords, $query_filters, $institutionRepository);

        // Concatenation des resultats
        $ret['aggs'] = $tabAggs;

        return new JsonResponse($ret);
    }

    #[Groups(['Default', 'institution'])]
    #[Route(path: '/create', name: 'institution.create', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function create(Request $request, ManagerRegistry $managerRegistry): array
    {
        /** @var AbstractInstitution $institution */
        $institution = new $this->institutionClass();

        //institution can't be created if user has no rights for it
        if ( ! $this->isGranted('CREATE', $institution)) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm(BaseInstitutionType::class, $institution);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $institution->setCreatedAt(new \DateTime('now'));
                $institution->setUpdatedAt(new \DateTime('now'));
                $objectManager = $managerRegistry->getManager();
                $objectManager->persist($institution);
                $objectManager->flush();
            }
        }

        return ['institution' => $institution, 'form' => $form->createView()];
    }

    #[Route(path: '/{id}/view', name: 'institution.view', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[IsGranted('VIEW', subject: 'institution')]
    #[Groups(['Default', 'institution'])]
    public function view(Request $request, ManagerRegistry $managerRegistry, AbstractInstitution $institution, int $id): array
    {
        $institution = $managerRegistry->getRepository(Institution::class, $id);
        if (!$institution) {
            throw new NotFoundHttpException('Institution not found');
        }
        if ( ! $this->isGranted('EDIT', $institution)) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm(InstitutionType::class, $institution);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $managerRegistry->getManager()->persist($institution);
                $managerRegistry->getManager()->flush();
            }
        }

        return ['form' => $form->createView(), 'institution' => $institution];
    }

    #[Route(path: '/{id}/remove', name: 'institution.remove', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'], methods: "POST")]
    #[IsGranted('DELETE', subject: 'institution')]
    #[Groups(['Default', 'institution'])]
    public function remove(AbstractInstitution $institution, ManagerRegistry $managerRegistry, int $id): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $institution = $managerRegistry->getRepository(Institution::class, $id);
        if (!$institution) {
            throw new NotFoundHttpException('Institution not found');
        }
        $objectManager = $managerRegistry->getManager();
        $objectManager->remove($institution);
        $objectManager->flush();

        return $this->redirectToRoute('institution.search');
    }

    private function constructAggs($aggs, $keyword, $query_filters, \App\Repository\InstitutionRepository $institutionRepository): array
    {
        $tabAggs = [];

        // CONSTRUCTION VILLE
        if(isset( $aggs['city.source'])){
            $allCities = $institutionRepository->getAllCities();

            $i = 0; $tabCit = [];
            //Pour chaque ville on teste la requête
            foreach($allCities as $allCity){
                $nbInstPub= $institutionRepository->getNbInstitutions($query_filters, $keyword, $aggs, $allCity);
                if ($nbInstPub > 0) {
                    $tabCit[$i] = [ 'key' => $allCity, 'doc_count' => $nbInstPub];
                    ++$i;
                }
            }

            $tabAggs['city.source']['buckets'] = $tabCit;
        }

        return $tabAggs;
    }
}

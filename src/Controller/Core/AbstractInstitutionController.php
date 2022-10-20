<?php

namespace App\Controller\Core;

use App\Entity\Back\Institution;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use App\Form\Type\ChangeOrganizationType;
use App\Entity\Core\AbstractInstitution;
use App\Form\Type\InstitutionType;
use App\Entity\Back\Organization;
use App\Repository\InstitutionRepository;


/**
 * @Route("/institution")
 */
abstract class AbstractInstitutionController extends AbstractController
{
    protected $institutionClass = AbstractInstitution::class;

    /**
     * @Route("/search", name="institution.search", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "institution"}, serializerEnableMaxDepthChecks=true)
     */
    public function searchAction(Request $request, ManagerRegistry $doctrine, InstitutionRepository $institutionRepository)
    {
        $keywords = $request->request->get('keywords', 'NO KEYWORDS');
        $filters = $request->request->get('filters', 'NO FILTERS');
        $query_filters = $request->request->get('query_filters', 'NO QUERY FILTERS');
        $aggs = $request->request->get('aggs', 'NO AGGS');
        $page = $request->request->get('page', 'NO PAGE');
        $size = $request->request->get('size', 'NO SIZE');

        // Recherche avec les filtres
        $ret = $institutionRepository->getInstitutionsList($keywords, $filters, $page, $size);

        // Recherche pour aggs et query_filters
        $tabAggs = array();
        $tabAggs = $this->constructAggs($aggs, $keywords, $query_filters, $doctrine, $institutionRepository);

        // Concatenation des resultats
        $ret['aggs'] = $tabAggs;

        return $ret;
    }

    /**
     * @Route("/create", name="institution.create", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "institution"}, serializerEnableMaxDepthChecks=true)
     */
    public function createAction(Request $request, ManagerRegistry $doctrine)
    {
        /** @var AbstractInstitution $institution */
        $institution = new $this->institutionClass();
        $institution->setOrganization($this->getUser()->getOrganization());

        //institution can't be created if user has no rights for it
        if ( ! $this->isGranted('CREATE', $institution)) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm(InstitutionType::class, $institution);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $institution->setCreatedAt(new \DateTime('now'));
                $institution->setUpdatedAt(new \DateTime('now'));
                $em = $doctrine->getManager();
                $em->persist($institution);
                $em->flush();
            }
        }

        return array('institution' => $institution, 'form' => $form->createView());
    }

    /**
     * This action attach a form to the return array when the user has the permission to edit the institution.
     *
     * @Route("/{id}/view", requirements={"id" = "\d+"}, name="institution.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @IsGranted("VIEW", subject="institution")
     * @ParamConverter("institution", class="App\Entity\Core\AbstractInstitution", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "institution"}, serializerEnableMaxDepthChecks=true)
     */
    public function viewAction(Request $request, ManagerRegistry $doctrine, AbstractInstitution $institution)
    {
        if ( ! $this->isGranted('EDIT', $institution)) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm(InstitutionType::class, $institution);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $doctrine->getManager()->persist($institution);
                $doctrine->getManager()->flush();
            }
        }

        return array('form' => $form->createView(), 'institution' => $institution);
    }

    /**
     * @Route("/{id}/changeorg", name="institution.changeorg", options={"expose"=true}, defaults={"_format" = "json"})
     * @IsGranted("EDIT", subject="institution")
     * @ParamConverter("institution", class="App\Entity\Core\AbstractInstitution", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "institution"}, serializerEnableMaxDepthChecks=true)
     */
    public function changeOrganizationAction(Request $request, ManagerRegistry $doctrine, AbstractInstitution $institution)
    {
        // security check
/*        if (!$this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_inscription.rights.inscription.all.update')) {
            throw new AccessDeniedException();
        }*/

        $form = $this->createForm(ChangeOrganizationType::class, $institution);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $doctrine->getManager()->flush();
            }
        }

        return array('form' => $form->createView(), 'institution' => $institution);
    }

    /**
     * @Route("/{id}/remove", requirements={"id" = "\d+"}, name="institution.remove", options={"expose"=true}, defaults={"_format" = "json"})
     * @Method("POST")
     * @IsGranted("DELETE", subject="institution")
     * @ParamConverter("institution", class="App\Entity\Core\AbstractInstitution", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "institution"}, serializerEnableMaxDepthChecks=true)
     */
    public function removeAction(AbstractInstitution $institution, ManagerRegistry $doctrine)
    {
        $em = $doctrine->getManager();
        $em->remove($institution);
        $em->flush();
//        $this->get('fos_elastica.index')->refresh();

        return $this->redirect($this->generateUrl('institution.search'));
    }

    private function constructAggs($aggs, $keyword, $query_filters, $doctrine, $institutionRepository)
    {
        $tabAggs = array();

        // CONSTRUCTION CENTRES
        if(isset( $aggs['organization.name.source'])){
            $allOrganizations = $doctrine->getRepository(Organization::class)->findAll();

            $i = 0; $tabOrg = array();
            //Pour chaque centre on teste la requête
            foreach($allOrganizations as $organization){
                $nbInstOrg = $institutionRepository->getNbInstitutions($query_filters, $keyword, $aggs, $organization->getName());
                if ($nbInstOrg > 0) {
                    $tabOrg[$i] = [ 'key' => $organization->getName(), 'doc_count' => $nbInstOrg];
                    $i++;
                }
            }
            $tabAggs['organization.name.source']['buckets'] = $tabOrg;
        }

        // CONSTRUCTION VILLE
        if(isset( $aggs['city.source'])){
            $allCities = $institutionRepository->getAllCities();

            $i = 0; $tabCit = array();
            //Pour chaque ville on teste la requête
            foreach($allCities as $city){
                $nbInstPub= $institutionRepository->getNbInstitutions($query_filters, $keyword, $aggs, $city);
                if ($nbInstPub > 0) {
                    $tabCit[$i] = [ 'key' => $city, 'doc_count' => $nbInstPub];
                    $i++;
                }
            }
            $tabAggs['city.source']['buckets'] = $tabCit;
        }

        return $tabAggs;
    }
}

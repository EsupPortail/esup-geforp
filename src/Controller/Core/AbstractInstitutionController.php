<?php

namespace App\Controller\Core;

use App\Entity\Institution;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use App\Form\Type\ChangeOrganizationType;
use App\Entity\Core\AbstractInstitution;
use App\Form\Type\BaseInstitutionType;


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
    public function searchAction(Request $request, ManagerRegistry $doctrine)
    {
/*        $search = $this->get('sygefor_institution.search');
        $search->handleRequest($request);

        return $search->search(); */

        $institutions = $doctrine->getRepository(Institution::class)->findAll();
        $nbInstitutions  = count($institutions);

        $ret = array(
            'total' => $nbInstitutions,
            'pageSize' => 0,
            'items' => $institutions,
        );
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
/*        if ( ! $this->get('security.context')->isGranted('CREATE', $institution)) {
            throw new AccessDeniedException('Action non autorisée');
        }*/

        $form = $this->createForm(BaseInstitutionType::class, $institution);
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
     * @ParamConverter("institution", class="App\Entity\Core\AbstractInstitution", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "institution"}, serializerEnableMaxDepthChecks=true)
     */
    public function viewAction(Request $request, ManagerRegistry $doctrine, AbstractInstitution $institution)
    {
/*        if ( ! $this->get('security.context')->isGranted('EDIT', $institution)) {
            throw new AccessDeniedException('Action non autorisée');
        }*/

        $form = $this->createForm(BaseInstitutionType::class, $institution);
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
     * @ParamConverter("institution", class="SygeforInstitutionBundle:AbstractInstitution", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "institution"}, serializerEnableMaxDepthChecks=true)
     */
    public function changeOrganizationAction(Request $request, AbstractInstitution $institution)
    {
        // security check
        if (!$this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_inscription.rights.inscription.all.update')) {
            throw new AccessDeniedException();
        }

        $form = $this->createForm(ChangeOrganizationType::class, $institution);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->getDoctrine()->getManager()->flush();
            }
        }

        return array('form' => $form->createView(), 'institution' => $institution);
    }

    /**
     * @Route("/{id}/remove", requirements={"id" = "\d+"}, name="institution.remove", options={"expose"=true}, defaults={"_format" = "json"})
     * @Method("POST")
     * @ParamConverter("institution", class="SygeforInstitutionBundle:AbstractInstitution", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "institution"}, serializerEnableMaxDepthChecks=true)
     */
    public function removeAction(AbstractInstitution $institution)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($institution);
        $em->flush();
        $this->get('fos_elastica.index')->refresh();

        return $this->redirect($this->generateUrl('institution.search'));
    }
}

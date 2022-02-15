<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractInscription;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use App\Entity\Core\Term\InscriptionStatus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use App\Form\Type\AbstractInscriptionType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class InscriptionController.
 *
 * @Route("/inscription")
 */
abstract class AbstractInscriptionController extends AbstractController
{
    protected $inscriptionClass = AbstractInscription::class;

    /**
     * @Route("/search", name="inscription.search", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "inscription"}, serializerEnableMaxDepthChecks=true)
     */
    public function searchAction(Request $request)
    {
        $search = $this->get('sygefor_inscription.search');
        $search->handleRequest($request);

        // security check : training
        if (!$this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_core.access_right.inscription.all.view')) {
            $search->addTermFilter('session.training.organization.id', $this->getUser()->getOrganization()->getId());
        }

        return $search->search();
    }

    /**
     * @Route("/create/{session}", name="inscription.create", options={"expose"=true}, defaults={"_format" = "json"})
     * @SecureParam(name="session", permissions="EDIT")
     * @ParamConverter("session", class="SygeforCoreBundle:AbstractSession", options={"id" = "session"})
     * @Rest\View(serializerGroups={"Default", "inscription"}, serializerEnableMaxDepthChecks=true)
     */
    public function createAction(Request $request, AbstractSession $session)
    {
        /** @var AbstractInscription $inscription */
        $inscription = $this->createInscription($session);
        /** @var AbstractInscriptionType $inscriptionClass */
        $inscriptionClass = $inscription::getFormType();

        $form = $this->createForm(new $inscriptionClass($session->getTraining()->getOrganization()), $inscription);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($inscription);
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'inscription' => $inscription);
    }

    /**
     * @Route("/{id}/view", requirements={"id" = "\d+"}, name="inscription.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @SecureParam(name="inscription", permissions="VIEW")
     * @ParamConverter("inscription", class="SygeforCoreBundle:AbstractInscription", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "inscription"}, serializerEnableMaxDepthChecks=true)
     */
    public function viewAction(AbstractInscription $inscription, Request $request)
    {
        if (!$this->get('security.context')->isGranted('EDIT', $inscription)) {
            if ($this->get('security.context')->isGranted('VIEW', $inscription)) {
                return array('inscription' => $inscription);
            }

            throw new AccessDeniedException('Action non autorisée');
        }

        /** @var AbstractInscriptionType $inscriptionClass */
        $inscriptionClass = $inscription::getFormType();
        $form = $this->createForm(new $inscriptionClass($inscription->getSession()->getTraining()->getOrganization()), $inscription);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'inscription' => $inscription);
    }

    /**
     * @Route("/{id}/remove", name="inscription.delete", options={"expose"=true}, defaults={"_format" = "json"})
     * @Method("POST")
     * @SecureParam(name="inscription", permissions="DELETE")
     * @ParamConverter("inscription", class="SygeforCoreBundle:AbstractInscription", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "inscription"}, serializerEnableMaxDepthChecks=true)
     */
    public function deleteAction(AbstractInscription $inscription)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($inscription);
        $em->flush();
        $this->get('fos_elastica.index')->refresh();

        return array();
    }

    /**
     * @param AbstractSession $session
     *
     * @return AbstractInscription
     */
    protected function createInscription($session)
    {
        $em = $this->getDoctrine()->getManager();
        $inscription = new $this->inscriptionClass();
        $inscription->setSession($session);
		$defaultInscriptionStatus = $em->getRepository(InscriptionStatus::class)->findOneBy(['machineName' => 'waiting']);
        $inscription->setInscriptionStatus($defaultInscriptionStatus);

        return $inscription;
    }
}

<?php

namespace App\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Form\Type\ChangeOrganizationType;
use App\Utils\Search\SearchService;
use App\Entity\Core\AbstractTrainer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class TrainerController.
 *
 * @Route("/trainer")
 */
abstract class AbstractTrainerController extends AbstractController
{
    protected $trainerClass = AbstractTrainer::class;

    /**
     * @Route("/search", name="trainer.search", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "trainer"}, serializerEnableMaxDepthChecks=true)
     */
    public function searchAction(Request $request)
    {
        /** @var SearchService $search */
        $search = $this->get('sygefor_trainer.search');
        $search->handleRequest($request);

        // security check
        if (!$this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_core.access_right.trainer.all.view')) {
            $search->addTermFilter('organization.id', $this->getUser()->getOrganization()->getId());
        }

        return $search->search();
    }

    /**
     * @Route("/create", name="trainer.create", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "trainer"}, serializerEnableMaxDepthChecks=true)
     */
    public function createAction(Request $request)
    {
        /** @var AbstractTrainer $trainer */
        $trainer = new $this->trainerClass();
        $trainer->setOrganization($this->getUser()->getOrganization());

        //trainer can't be created if user has no rights for it
        if (!$this->get('security.context')->isGranted('CREATE', $trainer)) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm($trainer::getFormType(), $trainer);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($trainer);
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'trainer' => $trainer);
    }

    /**
     * @Route("/{id}/view", requirements={"id" = "\d+"}, name="trainer.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @SecureParam(name="trainer", permissions="VIEW")
     * @ParamConverter("trainer", class="SygeforCoreBundle:AbstractTrainer", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "trainer"}, serializerEnableMaxDepthChecks=true)
     */
    public function viewAction(AbstractTrainer $trainer, Request $request)
    {
        if (!$this->get('security.context')->isGranted('EDIT', $trainer)) {
            if ($this->get('security.context')->isGranted('VIEW', $trainer)) {
                return array('trainer' => $trainer);
            }

            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm($trainer::getFormType(), $trainer);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->getDoctrine()->getManager()->flush();
            }
        }

        return array('form' => $form->createView(), 'trainer' => $trainer);
    }

    /**
     * @Route("/{id}/changeorg", name="trainer.changeorg", options={"expose"=true}, defaults={"_format" = "json"})
     * @SecureParam(name="trainer", permissions="EDIT")
     * @ParamConverter("trainer", class="SygeforCoreBundle:AbstractTrainer", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "trainer"}, serializerEnableMaxDepthChecks=true)
     */
    public function changeOrganizationAction(Request $request, AbstractTrainer $trainer)
    {
        // security check
        if (!$this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_core.access_right.trainer.all.update')) {
            throw new AccessDeniedException();
        }

        $form = $this->createForm(ChangeOrganizationType::class, $trainer);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->getDoctrine()->getManager()->flush();
            }
        }

        return array('form' => $form->createView(), 'trainer' => $trainer);
    }

    /**
     * @Route("/{id}/remove", name="trainer.delete", options={"expose"=true}, defaults={"_format" = "json"})
     * @Method("POST")
     * @SecureParam(name="trainer", permissions="DELETE")
     * @ParamConverter("trainer", class="SygeforCoreBundle:AbstractTrainer", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "trainer"}, serializerEnableMaxDepthChecks=true)
     */
    public function deleteAction(AbstractTrainer $trainer)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($trainer);
        $em->flush();
        $this->get('fos_elastica.index')->refresh();

        return;
    }
}

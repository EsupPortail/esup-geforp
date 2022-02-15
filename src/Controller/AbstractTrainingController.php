<?php

namespace App\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTraining;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/training")
 */
abstract class AbstractTrainingController extends AbstractController
{
    protected $sessionClass = AbstractSession::class;

    /**
     * @Route("/search", name="training.search", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "training"}, serializerEnableMaxDepthChecks=true)
     */
    public function searchAction(Request $request)
    {
        $search = $this->get('sygefor_training.semestered.search');
        $search->handleRequest($request);

        // security check
        if (!$this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_core.access_right.training.all.view')) {
            $search->addTermFilter('training.organization.id', $this->getUser()->getOrganization()->getId());
        }

        return $search->search();
    }

    /**
     * @Route("/create/{type}", name="training.create", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "training"}, serializerEnableMaxDepthChecks=true)
     */
    public function createAction(Request $request, $type)
    {
        $registry = $this->get('sygefor_core.registry.training_type');
        $type = $registry->getType($type);

        $class = $type['class'];
        /** @var AbstractTraining $training */
        $training = new $class();
        try {
            $training->setOrganization($this->getUser()->getOrganization());
        } catch (\Exception $e) {
            return array($e->getMessage());
        }

        //training can't be created if user has no rights for it
        if (!$this->get('security.context')->isGranted('CREATE', $training)) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $formClass = $training::getFormType();
        $form = $this->createForm(new $formClass($this->get('sygefor_core.access_right_registry')), $training);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($training);
                $em->flush();
            }
        }

        return array('training' => $training, 'form' => $form->createView());
    }

    /**
     * This action attach a form to the return array when the user has the permission to edit the training.
     *
     * @Route("/{id}/view", requirements={"id" = "\d+"}, name="training.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @SecureParam(name="training", permissions="VIEW")
     * @ParamConverter("training", class="SygeforCoreBundle:AbstractTraining", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "training"}, serializerEnableMaxDepthChecks=true)
     */
    public function viewAction(Request $request, AbstractTraining $training)
    {
        if (!$this->get('security.context')->isGranted('EDIT', $training)) {
            if ($this->get('security.context')->isGranted('VIEW', $training)) {
                return array('training' => $training);
            }
            throw new AccessDeniedException('Action non autorisée');
        }

        $formClass = $training::getFormType();
        $form = $this->createForm(new $formClass($this->get('sygefor_core.access_right_registry')), $training);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'training' => $training);
    }

    /**
     * @Route("/{id}/remove", requirements={"id" = "\d+"}, name="training.remove", options={"expose"=true}, defaults={"_format" = "json"})
     * @Method("POST")
     * @ParamConverter("training", class="SygeforCoreBundle:AbstractTraining", options={"id" = "id"})
     * @SecureParam(name="training", permissions="DELETE")
     * @Rest\View(serializerGroups={"Default", "training"}, serializerEnableMaxDepthChecks=true)
     */
    public function removeAction(AbstractTraining $training)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($training);
        $em->flush();
        $this->get('fos_elastica.index')->refresh();

        return $this->redirect($this->generateUrl('training.search'));
    }

    /**
     * @Route("/choosetypeduplicate", name="training.choosetypeduplicate", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "training"}, serializerEnableMaxDepthChecks=true)
     */
    public function chooseTypeDuplicateAction(Request $request)
    {
        $typeChoices = array();
        foreach ($this->get('sygefor_core.registry.training_type')->getTypes() as $type => $entity) {
            $typeChoices[$type] = $entity['label'];
        }
        $form = $this->createFormBuilder()
            ->add('duplicatedType', 'choice', array(
                'label' => 'Type d\'événement',
                'choices' => $typeChoices,
                'required' => true,
                'attr' => array(
                    'title' => 'Type de la formation ciblée',
                ),
            ))->getForm();

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                return array('type' => $form->get('duplicatedType')->getData());
            }
        }

        return array('form' => $form->createView());
    }

    /**
     * @Route("/duplicate/{id}/{type}", name="training.duplicate", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("training", class="SygeforCoreBundle:AbstractTraining")
     * @Rest\View(serializerGroups={"Default", "training"}, serializerEnableMaxDepthChecks=true)
     */
    public function duplicateAction(Request $request, AbstractTraining $training, $type)
    {
        //training can't be created if user has no rights for it
        if (!$this->get('security.context')->isGranted('CREATE', $training)) {
            throw new AccessDeniedException('Action non autorisée');
        }

        /** @var AbstractTraining $cloned */
        $cloned = null;
        // get targetted training type
        $typeClass = $this->get('sygefor_core.registry.training_type')->getType($type);
        if ($type === $training->getType()) {
            $cloned = clone $training;
        } else {
            $cloned = new $typeClass['class']();
            $cloned->copyProperties($training);
        }

        $form = $this->createForm($typeClass['class']::getFormType(), $cloned);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->mergeArrayCollectionsAndFlush($cloned, $training);
                $em = $this->getDoctrine()->getManager();
                $em->persist($cloned);
                $em->flush();

                return array('form' => $form->createView(), 'training' => $cloned);
            }
        }

        return array('form' => $form->createView());
    }

    /**
     * @param AbstractTraining $dest
     * @param AbstractTraining $source
     */
    protected function mergeArrayCollectionsAndFlush($dest, $source)
    {
        // clone duplicate materials
        $tmpMaterials = $source->getMaterials();
        if (!empty($tmpMaterials)) {
            foreach ($tmpMaterials as $material) {
                $newMat = clone $material;
                $dest->addMaterial($newMat);
            }
        }
    }
}

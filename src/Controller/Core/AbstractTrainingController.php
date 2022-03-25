<?php

namespace App\Controller\Core;

use App\Entity\Internship;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTraining;
use Sygefor\Bundle\TrainingBundle\SpreadSheet\TrainingBalanceSheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
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
/*        $search = $this->get('sygefor_training.semestered.search');
        $search->handleRequest($request);

        // security check
        if( ! $this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_training.rights.training.all.view')) {
            $search->addTermFilter('training.organization.id', $this->getUser()->getOrganization()->getId());
        }

        return $search->search(); */


        $ret = array(
            'total' => 0,
            'pageSize' => 0,
            'items' => array(),
        );
        return $ret;
    }

    /**
     * @Route("/create/{type}", name="training.create", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "training"}, serializerEnableMaxDepthChecks=true)
     */
    public function createAction(Request $request, ManagerRegistry $doctrine, $type)
    {
/*        $registry = $this->get('sygefor_training.type.registry');
        $type     = $registry->getType($type);

        $class = $type['class'];*/
        $class = Internship::class;
        /** @var AbstractTraining $training */
        $training = new $class();
        try {
            $training->setOrganization($this->getUser()->getOrganization());
        }
        catch (\Exception $e) {
            return array($e->getMessage());
        }

        //training can't be created if user has no rights for it
/*        if (!$this->get('security.context')->isGranted('CREATE', $training)) {
            throw new AccessDeniedException('Action non autorisée');
        }*/

        $form = $this->createForm($training::getFormType(), $training);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $training->setCreatedAt(new \DateTime('now'));
                $training->setUpdatedAt(new \DateTime('now'));
                $em = $doctrine->getManager();
                $em->persist($training);
                $em->flush();
            }
        }

        return array('training' => $training, 'form' => $form->createView());
        //return new Response(json_encode(array('training' => $training, 'form' => $form->createView())));

    }

    /**
     * This action attach a form to the return array when the user has the permission to edit the training.
     *
     * @Route("/{id}/view", requirements={"id" = "\d+"}, name="training.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("training", class="App\Entity\Core\AbstractTraining", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "training"}, serializerEnableMaxDepthChecks=true)
     */
    public function viewAction(Request $request,ManagerRegistry $doctrine, AbstractTraining $training)
    {
/*        if (!$this->get('security.context')->isGranted('EDIT', $training)) {
            throw new AccessDeniedException('Action non autorisée');
        }*/

        $form = $this->createForm($training::getFormType(), $training);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $doctrine->getManager();
                $em->flush();
            }
        }
        $return = array('form' => $form->createView(), 'training' => $training);

        // if the training is single session, add 'session' to the serialization groups
        if ($training instanceof SingleSessionTraining) {
            $view = new View($return);
            $view->setSerializationContext(SerializationContext::create()->setGroups(array('Default', 'training', 'session')));

            return $view;
        }

        return $return;
    }

    /**
     * @Route("/{id}/remove", requirements={"id" = "\d+"}, name="training.remove", options={"expose"=true}, defaults={"_format" = "json"})
     * @Method("POST")
     * @ParamConverter("training", class="App\Entity\Core\AbstractTraining", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "training"}, serializerEnableMaxDepthChecks=true)
     */
    public function removeAction(ManagerRegistry $doctrine, AbstractTraining $training)
    {
        $em = $doctrine->getManager();
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
        foreach ($this->get('sygefor_training.type.registry')->getTypes() as $type => $entity) {
            $typeChoices[$type] = $entity['label'];
        }
        $form = $this->createFormBuilder()
            ->add('duplicatedType', 'choice', array(
                'label'    => 'Type de stage',
                'choices'  => $typeChoices,
                'required' => true,
                'attr'     => array(
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
     * @ParamConverter("training", class="App\Entity\Core\AbstractTraining")
     * @Rest\View(serializerGroups={"Default", "training"}, serializerEnableMaxDepthChecks=true)
     */
    public function duplicateAction(Request $request, AbstractTraining $training, $type)
    {
        //training can't be created if user has no rights for it
/*        if ( ! $this->get('security.context')->isGranted('CREATE', $training)) {
            throw new AccessDeniedException('Action non autorisée');
        }*/

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
            }
            else {
                if ($training->getSessions() && $training->getSessions()->count() > 0) {
                    $session = clone $training->getSessions()->last();
                }
                else {
                    $session = new $this->sessionClass;
                }
            }
            $session->setNumberOfRegistrations(0);
            $session->setTraining($cloned);
            $cloned->setSession($session);
        }

        // verify if training category matches with new type
        /** @var RepositoryFactory $repository */
        $repository = $this->getDoctrine()->getRepository('SygeforTrainingBundle:Training\Term\TrainingCategory');
        /** @var QueryBuilder $qb */
        $qb = $repository->createQueryBuilder('t')
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
        $em = $this->getDoctrine()->getManager();

        // clone common arrayCollections
        if (method_exists($source, 'getTags')) {
            $dest->duplicateArrayCollection('addTag', $source->getTags());
        }

        // clone duplicate materials
        $tmpMaterials = $source->getMaterials();
        if ( ! empty($tmpMaterials)) {
            foreach ($tmpMaterials as $material) {
                $newMat = clone $material;
                $dest->addMaterial($newMat);
            }
        }

        $em->persist($dest);
        $em->flush();
    }

    /**
     * @Route("/{id}/bilan.{_format}", requirements={"id" = "\d+"}, name="training.balancesheet", options={"expose"=true}, defaults={"_format" = "xls"}, requirements={"_format"="csv|xls|xlsx"})
     * @Method("GET")
     * @ParamConverter("training", class="SygeforTrainingBundle:Training\AbstractTraining", options={"id" = "id"})
     */
    public function balanceSheetAction(AbstractTraining $training)
    {
        $bs = new TrainingBalanceSheet($training, $this->get('phpexcel'), $this->container);

        return $bs->getResponse();
    }

}

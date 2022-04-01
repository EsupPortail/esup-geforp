<?php

namespace App\Controller\Core;

use App\Entity\Session;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use JMS\Serializer\SerializationContext;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Core\AbstractInscription;
use App\Entity\Core\AbstractParticipation;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTraining;
use App\Form\Type\AbstractSessionType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

/**
 * @Route("/training/session")
 */
abstract class AbstractSessionController extends AbstractController
{
    protected $sessionClass = AbstractSession::class;
    protected $participationClass = AbstractParticipation::class;

    /**
     * @Route("/search", name="session.search", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function searchAction(Request $request, ManagerRegistry $doctrine)
    {
/*        $search = $this->get('sygefor_training.session.search');
        $search->handleRequest($request);

        // security check
        if (!$this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_core.access_right.training.all.view')) {
            $search->addTermFilter('training.organization.id', $this->getUser()->getOrganization()->getId());
        }

        return $search->search(); */

        $sessions = $doctrine->getRepository(Session::class)->findAll();
        $nbSessions  = count($sessions);

        $ret = array(
            'total' => $nbSessions,
            'pageSize' => 0,
            'items' => $sessions,
        );
        return $ret;
    }

    /**
     * @Route("/create/{training}", requirements={"id" = "\d+"}, name="session.create", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("training", class="App\Entity\Core\AbstractTraining", options={"id" = "training"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function createAction(Request $request, ManagerRegistry $doctrine, AbstractTraining $training)
    {
        /** @var AbstractSession $session */
        $session = new $this->sessionClass();
        $session->setTraining($training);
        $form = $this->createForm($session::getFormType(), $session);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                dump($session->getDisplayonline());
                $session->setCreatedAt(new \DateTime('now'));
                $session->setUpdatedAt(new \DateTime('now'));
                $em = $doctrine->getManager();
                $em->persist($session);
//                $training->updateTimestamps();
                $em->flush();
            }
        }

/*        if (!$this->get('security.context')->isGranted('EDIT', $session)) {
            if ($this->get('security.context')->isGranted('VIEW', $session)) {
                return array('session' => $session);
            }

            throw new AccessDeniedException('Action non autorisée');
        }*/

        return array('form' => $form->createView(), 'training' => $session->getTraining(), 'session' => $session);
    }

    /**
     * This action attach a form to the return array when the user has the permission to edit the training.
     *
     * @Route("/{id}/view", requirements={"id" = "\d+"}, name="session.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("session", class="App\Entity\Core\AbstractSession", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function viewAction(Request $request, ManagerRegistry $doctrine, AbstractSession $session)
    {
/*        if (!$this->get('security.context')->isGranted('EDIT', $session)) {
            if ($this->get('security.context')->isGranted('VIEW', $session)) {
                return array('session' => $session);
            }

            throw new AccessDeniedException('Action non autorisée');
        }*/

        $sessionRegistration = $session->getRegistration();
        $form = $this->createForm($session::getFormType(), $session);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em = $doctrine->getManager();
                $em->flush();

                return $this->redirectToRoute('session.view', array('id' => $session->getId()));
            }
        }

        return array('form' => $form->createView(), 'session' => $session);
    }

    /**
     * @param Request              $request
     * @param AbstractSession|null $session
     * @param mixed                $inscriptionIds
     *
     * @Route("/duplicate/{id}/{inscriptionIds}", requirements={"id" = "\d+"}, name="session.duplicate", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("session", class="App\Entity\Core\AbstractSession", isOptional="true")
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     *
     * @return array
     */
    public function duplicateAction(Request $request, AbstractSession $session = null, $inscriptionIds = null)
    {
        // we need at least one of both arguments
        if (!$session && empty($inscriptionIds)) {
            throw new MissingOptionsException('You have to pass a session id or an inscription array of ids');
        }

        // get inscriptions and session
        $inscriptions = array();
        $this->retrieveInscriptions($inscriptionIds, $inscriptions);
        if (!$session) {
            // get session
            $session = $inscriptions[0]->getSession();
        }
/*
        // new session can't be created if user has no rights for it
        if (!$this->get('security.context')->isGranted('EDIT', $session->getTraining())) {
            throw new AccessDeniedException('Action non autorisée');
        }
*/
        $cloned = clone $session;
        $form = $this->createFormBuilder($cloned)
            ->add('name', null, array(
                'required' => true,
                'label' => 'Intitulé de la session',
            ))
            ->add('datebegin', DateType::class, array(
                'required' => true,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'label' => 'Date de début',
            ))
            ->add('dateend', DateType::class, array(
                'required' => false,
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'label' => 'Date de fin',
            ));

        if (!empty($inscriptions)) {
            $form
                ->add('inscriptionManagement', ChoiceType::class, array(
                    'label' => 'Choisir la méthode d\'importation des inscriptions',
                    'mapped' => false,
                    'choices' => array(
                        'none' => 'Ne pas importer les inscriptions',
                        'copy' => 'Copier les inscriptions',
                        'move' => 'Déplacer les inscriptions',
                    ),
                    'empty_data' => 'none',
                    'required' => true,
                ));
        }

        $form = $form->getForm();
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $this->cloneSessionArrayCollections($session, $cloned, $inscriptions, $form->has('inscriptionManagement') ? $form->get('inscriptionManagement')->getData() : null);
                $em->persist($cloned);
                $em->flush();

                return array('session' => $cloned);
            }
        }

        return array('form' => $form->createView(), 'session' => $session, 'inscriptions' => $inscriptionIds);
    }

    /**
     * @Route("/{id}/remove", requirements={"id" = "\d+"}, name="session.remove", options={"expose"=true}, defaults={"_format" = "json"})
     * @Method("POST")
     * @ParamConverter("session", class="App\Entity\Core\AbstractSession", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function removeAction(AbstractSession $session, ManagerRegistry $doctrine)
    {
        $training = $session->getTraining();
        $em = $doctrine->getManager();
        $em->remove($session);
//        $training->updateTimestamps();
        $em->flush();
//        $this->get('fos_elastica.index')->refresh();

        return $this->redirect($this->generateUrl('training.view', array('id' => $training->getId())));
    }

    /**
     * Get inscription from json id inscription list.
     *
     * @param $inscriptionIds
     * @param $inscriptions
     */
    protected function retrieveInscriptions(&$inscriptionIds, &$inscriptions)
    {
        if ($inscriptionIds && is_string($inscriptionIds)) {
            $inscriptionIds = json_decode($inscriptionIds, true);
        }

        // retrieve inscriptions and session
        if ($inscriptionIds) {
            $inscriptions = $this->getDoctrine()->getManager()
                ->getRepository(AbstractInscription::class)
                ->findById($inscriptionIds);

            if (empty($inscriptions)) {
                throw new MissingOptionsException('You have to pass a session id or an inscription array of ids');
            }

            // check if all inscription come from a unique session
            $arraySessionIds = array();
            /** @var AbstractInscription $inscription */
            foreach ($inscriptions as $inscription) {
                $arraySessionIds[] = $inscription->getSession()->getId();
            }
            $arraySessionIds = array_unique($arraySessionIds);
            if (count($arraySessionIds) > 1) {
                throw new InvalidOptionException('The inscriptions come from several sessions');
            }
        }
    }

    /**
     * Clone participations, inscriptions and materials.
     *
     * @param AbstractSession $session
     * @param AbstractSession $cloned
     * @param $inscriptions
     * @param mixed $inscriptionManagement
     */
    protected function cloneSessionArrayCollections($session, $cloned, $inscriptions, $inscriptionManagement)
    {
        $em = $this->getDoctrine()->getManager();

        // clone participations
        /** @var AbstractParticipation $participation */
        foreach ($session->getParticipations() as $participation) {
            /** @var AbstractParticipation $newParticipation */
            $newParticipation = new $this->participationClass();
            $newParticipation->setSession($cloned);
            $newParticipation->setTrainer($participation->getTrainer());
            $cloned->addParticipation($newParticipation);
            $em->persist($newParticipation);
        }

        // clone inscriptions
        switch ($inscriptionManagement) {
            case 'copy':
                /** @var AbstractInscription $inscription */
                foreach ($inscriptions as $inscription) {
                    $newInscription = clone $inscription;
                    $newInscription->setSession($cloned);
                    $newInscription->setPresencestatus(null);
                    $cloned->addInscription($newInscription);
                    $em->persist($newInscription);
                }
                break;
            case 'move':
                /** @var AbstractInscription $inscription */
                foreach ($inscriptions as $inscription) {
                    $session->removeInscription($inscription);
                    $inscription->setSession($cloned);
                    $cloned->addInscription($inscription);
                }
                break;
            default:
                break;
        }

        // clone duplicate materials
        $tmpMaterials = $session->getMaterials();
        if (!empty($tmpMaterials)) {
            foreach ($tmpMaterials as $material) {
                $newMat = clone $material;
                $cloned->addMaterial($newMat);
            }
        }
    }
}

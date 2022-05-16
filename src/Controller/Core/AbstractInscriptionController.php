<?php

namespace App\Controller\Core;

use App\Entity\Inscription;
use App\Form\Type\BaseInscriptionType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractInscription;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Core\Term\Inscriptionstatus;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
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
    public function searchAction(Request $request, ManagerRegistry $doctrine)
    {
        /*
        $search = $this->get('sygefor_inscription.search');
        $search->handleRequest($request);

        // security check : training
        if (!$this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_core.access_right.inscription.all.view')) {
            $search->addTermFilter('session.training.organization.id', $this->getUser()->getOrganization()->getId());
        }

        return $search->search();
        */
        $inscriptions = $doctrine->getRepository(Inscription::class)->findAll();
        $nbInscriptions  = count($inscriptions);

        $ret = array(
            'total' => $nbInscriptions,
            'pageSize' => 0,
            'items' => $inscriptions,
        );
        return $ret;
    }

    /**
     * @Route("/create/{session}", name="inscription.create", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("session", class="App\Entity\Core\AbstractSession", options={"id" = "session"})
     * @Rest\View(serializerGroups={"Default", "inscription"}, serializerEnableMaxDepthChecks=true)
     */
    public function createAction(Request $request, AbstractSession $session, ManagerRegistry $doctrine)
    {
        if (!$this->isGranted('EDIT',$session->getTraining())) {
            throw new AccessDeniedException('Action non autorisée');
        }
        /** @var AbstractInscription $inscription */
        $inscription = $this->createInscription($session, $doctrine);
        /** @var BaseInscriptionType $inscriptionClass */
//        $inscriptionClass = $inscription::getFormType();
        $inscriptionClass = BaseInscriptionType::class;

        $form = $this->createForm($inscriptionClass, $inscription,
            array('attr' => array(
                'organization' => $session->getTraining()->getOrganization())
            )
        );
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $inscription->setCreatedAt(new \DateTime('now'));
                $inscription->setUpdatedAt(new \DateTime('now'));
                $em = $doctrine->getManager();
                $em->persist($inscription);
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'inscription' => $inscription);
    }

    /**
     * @Route("/{id}/view", requirements={"id" = "\d+"}, name="inscription.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @IsGranted("VIEW", subject="inscription")
     * @ParamConverter("inscription", class="App\Entity\Core\AbstractInscription", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "inscription"}, serializerEnableMaxDepthChecks=true)
     */
    public function viewAction(AbstractInscription $inscription, Request $request, ManagerRegistry $doctrine)
    {
        if (!$this->isGranted('EDIT', $inscription)) {
            if ($this->isGranted('VIEW', $inscription)) {
                return array('inscription' => $inscription);
            }

            throw new AccessDeniedException('Action non autorisée');
        }

        /** @var AbstractInscriptionType $inscriptionClass */
//        $inscriptionClass = $inscription::getFormType();
        $inscriptionClass = BaseInscriptionType::class;

        $form = $this->createForm($inscriptionClass, $inscription,
            array('attr' => array(
                'organization' => $inscription->getOrganization())
            ));
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $doctrine->getManager();
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'inscription' => $inscription);
    }

    /**
     * @Route("/{id}/remove", name="inscription.delete", options={"expose"=true}, defaults={"_format" = "json"})
     * @Method("POST")
     * @IsGranted("DELETE", subject="inscription")
     * @ParamConverter("inscription", class="App\Entity\Core\AbstractInscription", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "inscription"}, serializerEnableMaxDepthChecks=true)
     */
    public function deleteAction(AbstractInscription $inscription, ManagerRegistry $doctrine)
    {
        $em = $doctrine->getManager();
        $em->remove($inscription);
        $em->flush();
//        $this->get('fos_elastica.index')->refresh();

        return array();
    }

    /**
     * @param AbstractSession $session
     *
     * @return AbstractInscription
     */
    protected function createInscription($session, $doctrine)
    {
        $em = $doctrine->getManager();
        $inscription = new $this->inscriptionClass();
        $inscription->setSession($session);
		$defaultInscriptionStatus = $em->getRepository(Inscriptionstatus::class)->findOneBy(['machinename' => 'waiting']);
        $inscription->setInscriptionstatus($defaultInscriptionStatus);

        return $inscription;
    }
}

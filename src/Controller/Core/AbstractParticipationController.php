<?php

/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 09/12/2015
 * Time: 16:26.
 */

namespace App\Controller\Core;

use App\Entity\Back\Participation;
use App\Repository\ParticipationRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use App\Utils\Search\SearchService;
use App\Entity\Core\AbstractParticipation;
use App\Entity\Core\AbstractSession;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ParticipationController.
 *
 * @Route("/participation")
 */
abstract class AbstractParticipationController extends AbstractController
{
    protected $participationClass = AbstractParticipation::class;

    /**
     * @Route("/participation/search", name="participation.search", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "trainer"}, serializerEnableMaxDepthChecks=true)
     */
    public function participationSearchAction(Request $request, ManagerRegistry $doctrine, ParticipationRepository $participationRepository)
    {
        $keywords = $request->request->get('keywords', 'NO KEYWORDS');
        $filters = $request->request->get('filters', 'NO FILTERS');
        $query_filters = $request->request->get('query_filters', 'NO QUERY FILTERS');
        $aggs = $request->request->get('aggs', 'NO AGGS');

        // Recherche avec les filtres
        $participations = $participationRepository->getParticipationsList($keywords, $filters);
        $nbParticipations  = count($participations);

        // Recherche pour aggs et query_filters
        $tabAggs = array();

        $ret = array(
            'total' => $nbParticipations,
            'pageSize' => 0,
            'items' => $participations,
            'aggs' => $tabAggs
        );
        return $ret;
    }

    /**
     * @Route("/{session}/add", name="participation.add", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("session", class="App\Entity\Core\AbstractSession", options={"id" = "session"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function addParticipationAction(Request $request, ManagerRegistry $doctrine, AbstractSession $session)
    {
        if (!$this->isGranted('EDIT', $session->getTraining())) {
            throw new AccessDeniedException('Action non autorisée');
        }

        /** @var AbstractParticipation $participation */
        $participation = new $this->participationClass();
        $participation->setSession($session);
        $participation->setOrganization($session->getTraining()->getOrganization());
        $form = $this->createForm($participation::getFormType(), $participation);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $existingParticipation = null;
                /** @var AbstractParticipation $existingParticipation */
                foreach ($session->getParticipations() as $existingParticipation) {
                    if ($existingParticipation->getTrainer() === $participation->getTrainer()) {
                        $form->get('trainer')->addError(new FormError('Cet intervenant est déjà associé à cet évènement.'));
                        break;
                    }
                }

                if (!$existingParticipation || ($existingParticipation->getTrainer() !== $participation->getTrainer())) {
                    $session->addParticipation($participation);
                    //$session->updateTimestamps();
                    $session->setUpdatedAt(New \DateTime('now'));
                    //$session->getTraining()->updateTimestamps();
                    $session->getTraining()->setUpdatedAt(New \DateTime('now'));
                    $em = $doctrine->getManager();
                    $em->persist($participation);
                    $em->flush();
                }
            }
        }

        return array('form' => $form->createView(), 'participation' => $participation);
    }

    /**
     * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="participation.edit", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("participation", class="App\Entity\Core\AbstractParticipation", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "participation", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function editParticipationAction(Request $request, ManagerRegistry $doctrine, AbstractParticipation $participation)
    {
        // participation can't be created if user has no rights for it
        if (!$this->isGranted('EDIT', $participation->getSession()->getTraining())) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm($participation::getFormType(), $participation);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                //$participation->getSession()->updateTimestamps();
                $participation->getSession()->setUpdatedAt(New \DateTime('now'));
                $doctrine->getManager()->flush();
            }
        }

        return array('form' => $form->createView(), 'participation' => $participation);
    }

    /**
     * @Route("/{session}/remove/{participation}", name="participation.remove", options={"expose"=true}, defaults={"_format" = "json"})
     * @Method("POST")
     * @IsGranted("EDIT", subject="session")
     * @ParamConverter("session", class="App\Entity\Core\AbstractSession", options={"id" = "session"})
     * @ParamConverter("participation", class="App\Entity\Core\AbstractParticipation", options={"id" = "participation"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function removeParticipationAction(AbstractSession $session, ManagerRegistry $doctrine, AbstractParticipation $participation)
    {
        $session->removeParticipation($participation);
//        $session->updateTimestamps();
//        $session->getTraining()->updateTimestamps();
        $session->setUpdatedAt(New \DateTime('now'));
        $session->getTraining()->setUpdatedAt(New \DateTime('now'));
        $doctrine->getManager()->remove($participation);
        $doctrine->getManager()->flush();
//        $this->get('fos_elastica.index')->refresh();

        return;
    }
}

<?php

/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 09/12/2015
 * Time: 16:26.
 */

namespace App\Controller\Core;

use App\AccessRight\AccessRightRegistry;
use App\Entity\Back\Participation;
use App\Repository\ParticipationRepository;
use FOS\RestBundle\Controller\Annotations as Rest;
use mysql_xdevapi\Exception;
use Symfony\Component\Routing\Annotation\Route;
use App\Utils\Search\SearchService;
use App\Entity\Core\AbstractParticipation;
use App\Entity\Core\AbstractSession;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Class ParticipationController.
 *
 */
#[Route(path: '/participation')]
abstract class AbstractParticipationController extends AbstractController
{
    protected $participationClass = AbstractParticipation::class;
    // Recherche pour aggs et query_filters
    /**
     * @var mixed[]
     */
    private const TAB_AGGS = [];

    /**
     * @Rest\View(serializerGroups={"Default", "trainer"}, serializerEnableMaxDepthChecks=true)
     * @return array{total: int, pageSize: int, items: mixed, aggs: never[]}
     */
    #[Route(path: '/participation/search', name: 'participation.search', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function participationSearch(Request $request, ManagerRegistry $managerRegistry, ParticipationRepository $participationRepository, AccessRightRegistry $accessRightRegistry): array
    {
        $keywords = $request->request->get('keywords', 'NO KEYWORDS');
        $filters = $request->request->all('filters');
        $request->request->get('query_filters', 'NO QUERY FILTERS');
        $request->request->all('aggs');


        // security check : trainer : 'sygefor_trainer.rights.trainer.all.view' -> id=33
        if(!$accessRightRegistry->hasAccessRight(33)) {
            // restriction to user's organization
            $filters['organization.name.source'] = $this->getUser()->getOrganization()->getName();
        }

        // Recherche avec les filtres
        $participations = $participationRepository->getParticipationsList($keywords, $filters);
        $nbParticipations  = is_countable($participations) ? count($participations) : 0;
        return ['total' => $nbParticipations, 'pageSize' => 0, 'items' => $participations, 'aggs' => self::TAB_AGGS];
    }

    /**
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     * @return array{form: \Symfony\Component\Form\FormView, participation: \App\Entity\Core\AbstractParticipation}
     */
    #[Route(path: '/{session}/add', name: 'participation.add', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function addParticipation(Request $request, ManagerRegistry $managerRegistry, AbstractSession $session, int $id): array
    {
        $session = $managerRegistry->getRepository(AbstractSession::class)->find($id);
        if (!$session){
            throw new AccessDeniedException('Aucune session trouvé');
        }
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

                if (!$existingParticipation instanceof \App\Entity\Core\AbstractParticipation || ($existingParticipation->getTrainer() !== $participation->getTrainer())) {
                    $session->addParticipation($participation);
                    //$session->updateTimestamps();
                    $session->setUpdatedAt(New \DateTime('now'));
                    //$session->getTraining()->updateTimestamps();
                    $session->getTraining()->setUpdatedAt(New \DateTime('now'));
                    $objectManager = $managerRegistry->getManager();
                    $objectManager->persist($participation);
                    $objectManager->flush();
                }
            }
        }

        return ['form' => $form->createView(), 'participation' => $participation];
    }

    /**
     * @Rest\View(serializerGroups={"Default", "participation", "session"}, serializerEnableMaxDepthChecks=true)
     * @return array{form: \Symfony\Component\Form\FormView, participation: \App\Entity\Core\AbstractParticipation}
     */
    #[Route(path: '/{id}/edit', name: 'participation.edit', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function editParticipation(Request $request, ManagerRegistry $managerRegistry, AbstractParticipation $participation, int $id): array
    {
        $participation = $managerRegistry->getRepository(AbstractParticipation::class)->find($id);
        if (!$participation){
            throw new AccessDeniedException('Aucune participation trouvé');
        }
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
                $managerRegistry->getManager()->flush();
            }
        }

        return ['form' => $form->createView(), 'participation' => $participation];
    }

    /**
     *
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    #[Route(path: '/{session}/remove/{participation}', name: 'participation.remove', options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[IsGranted('EDIT', subject: 'session')]
    public function removeParticipation(AbstractSession $session, ManagerRegistry $managerRegistry, AbstractParticipation $participation, int $id): void
    {
        $session = $managerRegistry->getRepository(AbstractSession::class)->find($id);
        if (!$session){
            throw new AccessDeniedException('Aucune session trouvé');
        }
        $participation = $managerRegistry->getRepository(AbstractParticipation::class)->find($id);
        if (!$participation){
            throw new AccessDeniedException('Aucune participation trouvé');
        }
        $session->removeParticipation($participation);
//        $session->updateTimestamps();
//        $session->getTraining()->updateTimestamps();
        $session->setUpdatedAt(New \DateTime('now'));
        $session->getTraining()->setUpdatedAt(New \DateTime('now'));
        $managerRegistry->getManager()->remove($participation);
        $managerRegistry->getManager()->flush();
    }
}

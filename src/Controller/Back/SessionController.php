<?php

namespace App\Controller\Back;
use http\Env\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\Back\Participation;
use App\Entity\Back\Session;
use App\Entity\Back\DateSession;
use App\Form\Type\DateSessionType;
use App\Controller\Core\AbstractSessionController;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;

 #[Route("/training/session")]final class SessionController extends AbstractSessionController
{
    protected string $sessionClass = Session::class;

    protected string $participationClass = Participation::class;

     private static string $DATE_CLASS = DateSession::class;

     #[Route("/adddates/{session}", name: "dates.add", requirements: ["id" => "\d+"], options: ["expose"=> true], defaults: ["_format" => "json"])]
    public function adddates(Session $session, Request $request, ManagerRegistry $managerRegistry, int $id): JsonResponse
     {
        $session = $managerRegistry->getRepository(Session::class)->find($id);
        if (!$session) {
            throw new NotFoundHttpException();
        }
        $dateSession = new self::$DATE_CLASS;
        $dateSession->setSession($session);

        $daysSum =0;
        $hoursSum = 0;

        $form        = $this->createForm(DateSessionType::class, $dateSession);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $existingDate = null;
                $datesBegin = [];
                $datesEnd = [];
                /** @var DateSession $existingDate */
                foreach ($session->getDates() as $existingDate) {
                    if ($existingDate->getDatebegin() == $dateSession->getDatebegin()) {
                        $form->get('datebegin')->addError(new FormError('Cette date est déjà associé à cet évènement.'));
                        return new JsonResponse(['form' => $form->createView(), 'dates' => $dateSession, 'groups' => 'session']);

                    }

                    $datesBegin[] = $existingDate->getDatebegin();
                    $datesEnd[] = $existingDate->getDateend();

                    if (($existingDate->getDatebegin() == $existingDate->getDateend()) || ($existingDate->getDateend() == null)) {
                        ++$daysSum;
                        $hoursSum += ($existingDate->getHournumbermorn() + $existingDate->getHournumberafter());
                    }
                    else {
                        $daysSum += $existingDate->getDatebegin()->diff($existingDate->getDateend())->format('%a') + 1;
                        $hoursSum += ($existingDate->getHournumbermorn() + $existingDate->getHournumberafter()) * ($existingDate->getDatebegin()->diff($existingDate->getDateend())->format('%a') + 1);
                    }
                }

                if (!$existingDate instanceof \App\Entity\Back\DateSession || ($existingDate->getDatebegin() !== $dateSession->getDatebegin())) {
                    $session->addDates($dateSession);
                    $session->setUpdatedAt(new \DateTime('now'));
                    $session->getTraining()->setUpdatedAt(new \DateTime('now'));
                    $em = $managerRegistry->getManager();
                    $em->persist($dateSession);
                    $em->flush();
                    $datesBegin[] = $dateSession->getDatebegin();
                    $datesEnd[] = $dateSession->getDateend();

                    // Calcul nombre de jours
                    if (($dateSession->getDatebegin() == $dateSession->getDateend()) || ($dateSession->getDateend() == null)) {
                        ++$daysSum;
                        $hoursSum += ($dateSession->getHournumbermorn() + $dateSession->getHournumberafter());
                    }
                    else {
                        $daysSum += $dateSession->getDatebegin()->diff($dateSession->getDateend())->format('%a') + 1;
                        $hoursSum += ($dateSession->getHournumbermorn() + $dateSession->getHournumberafter()) * ($dateSession->getDatebegin()->diff($dateSession->getDateend())->format('%a') + 1);
                    }
                }

                // Tri des tableaux de dates
                usort($datesBegin, static fn($a, $b): int => $a < $b ? -1: 1);
                usort($datesEnd, static fn($a, $b): int => $a < $b ? -1: 1);

                // Renseigner le lieu
                $session->setPlace($session->getDates()[0]->getPlace());

                // Renseigner le nombre d'heures
                $session->setHournumber($hoursSum);

                // Renseigner le nombre de jours
                $session->setDaynumber($daysSum);

                // Récupérer les dates min et max début et fin pour les caler dans les dates de session
                $session->setDatebegin($datesBegin[0]);
                $session->setDateend($datesEnd[count($datesEnd)-1]);
                $em = $managerRegistry->getManager();
                $em->persist($session);
                $em->flush();

             }
        }
        return new JsonResponse($form, $dateSession, ['groups' => ['session', 'api.session'], 'enable_max_depth' => true]);
    }

      #[Route("/{session}/remove/{dates}", name: "dates.remove", options: ["expose" => true], defaults: ["_format" => "json"])]
      #[Route("POST")]

    public function removedates(Session $session, DateSession $dateSession, ManagerRegistry $managerRegistry, SerializerInterface $serializer, int $id): JsonResponse
    {
        $sessions = $managerRegistry->getRepository(Session::class, $id);
        if (!$sessions) {
            throw new NotFoundHttpException();
        }
        $dateSession = $managerRegistry->getRepository(DateSession::class, $id);
        if (!$dateSession) {
            throw new NotFoundHttpException();
        }
        $session->removeDate($dateSession);
        $session->setUpdatedAt(new \DateTime('now'));
        $session->getTraining()->setUpdatedAt(new \DateTime('now'));
        $managerRegistry->getManager()->remove($dateSession);
        $managerRegistry->getManager()->flush();

        // Traitement des dates min et max
        $datesBegin = [];
        $datesEnd = [];
        $hoursSum = 0;
        $daysSum = 0;

        /** @var DateSession $existingDate */
        foreach ($session->getDates() as $existingDate) {
            $datesBegin[] = $existingDate->getDatebegin();
            $datesEnd[] = $existingDate->getDateend();

            if (($existingDate->getDatebegin() == $existingDate->getDateend()) || ($existingDate->getDateend() == null)) {
                ++$daysSum;
                $hoursSum += ($existingDate->getHournumbermorn() + $existingDate->getHournumberafter());
            }
            else {
                $daysSum += $existingDate->getDatebegin()->diff($existingDate->getDateend())->format('%a') + 1;
                $hoursSum += ($existingDate->getHournumbermorn() + $existingDate->getHournumberafter()) * ($existingDate->getDatebegin()->diff($existingDate->getDateend())->format('%a') + 1);
            }
        }

        // Tri des tableaux de dates
        usort($datesBegin, static fn($a, $b): int => $a < $b ? -1: 1);
        usort($datesEnd, static fn($a, $b): int => $a < $b ? -1: 1);

        // Récupérer les dates min et max début et fin pour les caler dans les dates de session
        if ($datesBegin !== []) {
            $session->setDatebegin($datesBegin[0]);
            $session->setDateend($datesEnd[count($datesEnd) - 1]);
            $session->setHournumber($hoursSum);
            $session->setDaynumber($daysSum);
            $em = $managerRegistry->getManager();
            $em->persist($session);
            $em->flush();
        }

        $data = $serializer->serialize($session, 'json', ['groups' => ['session', 'api.session'],'enable_max_depth' => true]);
        return new JsonResponse($data, ResponseAlias::HTTP_OK, [], true);

    }

    #[Route(path: '/editdates/{dates}', name: 'dates.edit', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function editdates(DateSession $dateSession, Request $request, ManagerRegistry $managerRegistry, int $id): array
    {
        $dateSession = $managerRegistry->getRepository(DateSession::class)->find($id);
        if (!$dateSession) {
            throw new NotFoundHttpException();
        }
        $session = $dateSession->getSession();
        $form = $this->createForm(DateSessionType::class, $dateSession);
        $daysSum =0;
        $hoursSum = 0;

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                //Mise à jour date
                $em = $managerRegistry->getManager();
                $em->flush();

                $datesBegin = [];
                $datesEnd = [];
                /** @var DateSession $existingDate */
                foreach ($session->getDates() as $existingDate) {
                    $datesBegin[] = $existingDate->getDatebegin();
                    $datesEnd[] = $existingDate->getDateend();

                    if (($existingDate->getDatebegin() == $existingDate->getDateend()) || ($existingDate->getDateend() == null)) {
                        ++$daysSum;
                        $hoursSum += ($existingDate->getHournumbermorn() + $existingDate->getHournumberafter());
                    }
                    else {
                        $daysSum += $existingDate->getDatebegin()->diff($existingDate->getDateend())->format('%a') + 1;
                        $hoursSum += ($existingDate->getHournumbermorn() + $existingDate->getHournumberafter()) * ($existingDate->getDatebegin()->diff($existingDate->getDateend())->format('%a') + 1);
                    }

                }

                // Tri des tableaux de dates
                usort($datesBegin, static fn($a, $b): int => $a < $b ? -1: 1);
                usort($datesEnd, static fn($a, $b): int => $a < $b ? -1: 1);

                // Récupérer le lieu
                $session->setPlace($session->getDates()[0]->getPlace());

                // Renseigner le nombre d'heures
                $session->setHournumber($hoursSum);

                // Renseigner le nombre de jours
                $session->setDaynumber($daysSum);

                // Récupérer les dates min et max début et fin pour les caler dans les dates de session
                $session->setDatebegin($datesBegin[0]);
                $session->setDateend($datesEnd[count($datesEnd)-1]);
                $em = $managerRegistry->getManager();
                $em->persist($session);
                $em->flush();

            }
        }

        return ['form' => $form->createView(), 'dates' => $dateSession, 'groups' => ['session', 'api.session'], 'enable_max_depth' => true];
    }

    #[Route(path: '/viewdates/{dates}', name: 'dates.view', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function viewdates(DateSession $dateSession, Request $request, ManagerRegistry $managerRegistry, int $id): array
    {
        $dateSession = $managerRegistry->getRepository(DateSession::class)->find($id);
        if (!$dateSession) {
            throw new NotFoundHttpException();
        }
        $form = $this->createForm(DateSessionType::class, $dateSession);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $objectManager = $managerRegistry->getManager();
                $objectManager->flush();
            }
        }
        return ['form' => $form->createView(), 'dates' => $dateSession, ['groups' => ['session', 'api.session'], 'enable_max_depth' => true]];
    }

}

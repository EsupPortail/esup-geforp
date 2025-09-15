<?php

namespace App\Controller\Back;
use App\Entity\Core\AbstractSession;
use DoctrineExtensions\Query\Mysql\Date;
use FOS\RestBundle\Controller\Annotations as Rest;
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

     #[Rest\View(serializerGroups: ['Default', 'api.session'], serializerEnableMaxDepthChecks: true)]
     #[Route("/adddates/{session}", name: "dates.add", requirements: ["session" => "\d+"], options: ["expose"=> true], defaults: ["_format" => "json"])]
    public function adddates(Session $session, Request $request, ManagerRegistry $managerRegistry): array
     {

        if (!$session) {
            throw new NotFoundHttpException();
        }
        $dateSession = new (self::$DATE_CLASS);
        $dateSession->setSession($session);

        $daysSum =0;
        $hoursSum = 0;
        $form        = $this->createForm(DateSessionType::class, $dateSession);
         $form->handleRequest($request);
         if ($form->isSubmitted()) {
             if ($request->getMethod() === 'POST') {
                 if ($form->isValid()) {
                     $existingDate = null;
                     $datesBegin = [];
                     $datesEnd = [];
                     /** @var DateSession $existingDate */

                     foreach ($session->getDates() as $existingDate) {
                         if ($existingDate->getDatebegin() == $dateSession->getDatebegin()) {
                             $form->get('datebegin')->addError(new FormError('Cette date est déjà associé à cet évènement.'));

                             return ['form' => $form->createView(), 'dates' => $dateSession, 'groups' => 'session'];
                         }

                         $datesBegin[] = $existingDate->getDatebegin();
                         $datesEnd[] = $existingDate->getDateend();

                         if (($existingDate->getDatebegin() == $existingDate->getDateend()) || ($existingDate->getDateend() == null)) {
                             ++$daysSum;
                             $hoursSum += ($existingDate->getHournumbermorn() + $existingDate->getHournumberafter());
                         } else {
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
                         } else {
                             $daysSum += $dateSession->getDatebegin()->diff($dateSession->getDateend())->format('%a') + 1;
                             $hoursSum += ($dateSession->getHournumbermorn() + $dateSession->getHournumberafter()) * ($dateSession->getDatebegin()->diff($dateSession->getDateend())->format('%a') + 1);
                         }
                     }

                     // Tri des tableaux de dates
                     usort($datesBegin, static fn($a, $b): int => $a < $b ? -1 : 1);
                     usort($datesEnd, static fn($a, $b): int => $a < $b ? -1 : 1);

                     // Renseigner le lieu
					if($session->getDates()[0]->getPlace() !==null)
	                     $session->setPlace($session->getDates()[0]->getPlace());

                     // Renseigner le nombre d'heures
                     $session->setHournumber($hoursSum);

                     // Renseigner le nombre de jours
                     $session->setDaynumber($daysSum);

                     // Récupérer les dates min et max début et fin pour les caler dans les dates de session
                     $session->setDatebegin($datesBegin[0]);
                     $session->setDateend($datesEnd[count($datesEnd) - 1]);
                     $em = $managerRegistry->getManager();
                     $em->persist($session);
                     $em->flush();

                 }
             }
         }
         return ['form' => $form->createView(),
             'dateSession' => $dateSession,];
    }

     #[Rest\View(serializerGroups: ['Default', 'api.session'], serializerEnableMaxDepthChecks: true)]
     #[Route("/{session}/remove/{dates}", name: "dates.remove", options: ["expose" => true], defaults: ["_format" => "json"])]
     public function removedates(Session $session, DateSession $dates, ManagerRegistry $managerRegistry): array
     {

         $session->removeDate($dates);
         $session->setUpdatedAt(new \DateTime('now'));
         $session->getTraining()->setUpdatedAt(new \DateTime('now'));
         $managerRegistry->getManager()->remove($dates);
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

             if(count($datesBegin) > 0){
                 $session->setDatebegin($datesBegin[0]);
                 $session->setDateend($datesEnd[count($datesEnd) -1]);
                 $session->setHournumber($hoursSum);
                 $session->setDaynumber($daysSum);
             } else {
                 $session->setDatebegin(null);
                 $session->setDateend(null);
                 $session->setHournumber(0);
                 $session->setPlace(null);
             }

             $em = $managerRegistry->getManager();
             $em->persist($session);


         }
         return ['session' => $session, 'dates' => $datesBegin, 'groups' => 'session'];

     }

    #[Rest\View(serializerGroups: ['session', 'api.session'], serializerEnableMaxDepthChecks: true)]
    #[Route(path: '/editdates/{dates}', name: 'dates.edit', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function editdates(Request $request, ManagerRegistry $managerRegistry, int $dates): array
    {
        $dateSession = $managerRegistry->getRepository(DateSession::class)->find($dates);
        if (!$dateSession) {
            throw new NotFoundHttpException();
        }
        $session = $dateSession->getSession();
        $form = $this->createForm(DateSessionType::class, $dateSession);
        $daysSum =0;
        $hoursSum = 0;

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
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

        return ['form' => $form->createView(), 'dates' => $dateSession];
    }

    #[Route(path: '/viewdates/{dates}', name: 'dates.view', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function viewdates( Request $request, ManagerRegistry $managerRegistry, DateSession $dates): array
    {
        $dateSession = $managerRegistry->getRepository(DateSession::class)->find($dates);
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
        return ['form' => $form->createView(), 'dates' => $dateSession];
    }

}

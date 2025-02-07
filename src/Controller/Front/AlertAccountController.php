<?php

namespace App\Controller\Front;

use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Back\Alert;
use App\Entity\Back\MultipleAlert;
use App\Entity\Back\SingleAlert;
use App\Form\Type\ProgramAlertType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * This controller regroup actions related to alerts.
 *
 */
final class AlertAccountController extends AbstractController
{
    /**
     * All attendances of the trainee
     */
    #[Route(path: '/account/alerts', name: 'front.account.alerts')]
    public function alerts(Request $request, ManagerRegistry $managerRegistry): \Symfony\Component\HttpFoundation\Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            // Si l'utilisateur n'est pas authentifié pleinement, on redirige ou on lève une exception
            throw new AccessDeniedException('Vous devez être pleinement authentifié pour accéder à cette page.');
        }
        // Récupération des alertes du stagiaire
        $user = $this->getUser();
        $arTrainee = $managerRegistry->getRepository(\App\Entity\Back\Trainee::class)->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];
        $alertsTrainee = $trainee->getAlerts();

        // creation entites pour recuperer les alertes
        $multipleAlert = new MultipleAlert();
        foreach ($alertsTrainee as $alertTrainee){
            $alert = new SingleAlert();
            $alert->setAlert(true);
            $alert->setSessionId($alertTrainee->getSession()->getId());
            $alert->setTraineeId($trainee->getId());

            $multipleAlert->getAlerts()->add($alert);
        }

        // creation du formulaire d'alertes
        $form = $this->createForm(ProgramAlertType::class, $multipleAlert);
        $form->handleRequest($request);

        if (($form->isSubmitted()) && ($form->isValid())) {
            $arrAlerts = $multipleAlert->getAlerts();
            $objectManager = $managerRegistry->getManager();
            foreach ($arrAlerts as $arrAlert){
                // On verifie si la session et l'alerte existent déjà
                $sessionExiste = $managerRegistry->getManager()->getRepository(\App\Entity\Back\Session::class)->findOneBy(['id' => $arrAlert->getSessionId()]);

                $alertExiste = $managerRegistry->getManager()->getRepository(\App\Entity\Back\Alert::class)->findOneBy(['trainee' => $trainee, 'session'=> $sessionExiste]);

                // Si la case est cochée
                if ($arrAlert->getAlert() == true) {
                    // Si l'alerte existe déjà, on ne touche à rien, sinon, on la crée
                    if (!$alertExiste instanceof \App\Entity\Back\Alert) {
                        $alertNew = new Alert();
                        $alertNew->setTrainee($trainee);
                        $alertNew->setSession($sessionExiste);
                        $now = new \DateTime();
                        $alertNew->setCreatedAt($now);

                        $objectManager->persist($alertNew);
                        $objectManager->flush();
                    }
                } elseif ($alertExiste instanceof \App\Entity\Back\Alert) {
                    // Si la case n'est pas cochée
                    // Si l'alerte existe, on la supprime, sinon, on ne fait rien
                    $objectManager->remove($alertExiste);
                    $objectManager->flush();
                }
            }

            $this->get('session')->getFlashBag()->add('success', 'Vos modifications ont bien été enregistrées.');
            return $this->render('Front/Account/alert/alerts.html.twig');
        }

        return ['user' => $trainee, 'alerts' => $alertsTrainee, 'form' => $form->createView()];
    }

}

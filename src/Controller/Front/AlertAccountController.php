<?php

namespace App\Controller\Front;

use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Alert;
use App\Entity\MultipleAlert;
use App\Entity\SingleAlert;
use App\Form\Type\ProgramAlertType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * This controller regroup actions related to alerts.
 *
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
class AlertAccountController extends AbstractController
{
    /**
     * All attendances of the trainee
     * @Route("/account/alerts", name="front.account.alerts")
     * @Template("Front/Account/alert/alerts.html.twig")
     */
    public function alertsAction(Request $request, ManagerRegistry $doctrine)
    {
        // Récupération des alertes du stagiaire
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];
        $alertsTrainee = $trainee->getAlerts();

        // creation entites pour recuperer les alertes
        $alerts = new MultipleAlert();
        foreach ($alertsTrainee as $a){
            $alert = new SingleAlert();
            $alert->setAlert(true);
            $alert->setSessionId($a->getSession()->getId());
            $alert->setTraineeId($trainee->getId());

            $alerts->getAlerts()->add($alert);
        }

        // creation du formulaire d'alertes
        $form = $this->createForm(ProgramAlertType::class, $alerts);
        $form->handleRequest($request);

        if (($form->isSubmitted()) && ($form->isValid())) {
            $arrAlerts = $alerts->getAlerts();
            $em = $doctrine->getManager();
            foreach ($arrAlerts as $alert){
                // On verifie si la session et l'alerte existent déjà
                $sessionExiste = $doctrine->getManager()->getRepository('App\Entity\Session')->findOneBy(array(
                    'id' => $alert->getSessionId()
                ));

                $alertExiste = $doctrine->getManager()->getRepository('App\Entity\Alert')->findOneBy(array(
                    'trainee' => $trainee,
                    'session'=> $sessionExiste
                ));

                // Si la case est cochée
                if ($alert->getAlert() == true) {
                    // Si l'alerte existe déjà, on ne touche à rien, sinon, on la crée
                    if (!$alertExiste) {
                        $alertNew = new Alert();
                        $alertNew->setTrainee($trainee);
                        $alertNew->setSession($sessionExiste);
                        $now = new \DateTime();
                        $alertNew->setCreatedAt($now);

                        $em->persist($alertNew);
                        $em->flush();
                    }

                } else {
                    // Si la case n'est pas cochée
                    // Si l'alerte existe, on la supprime, sinon, on ne fait rien
                    if ($alertExiste) {
                        $em->remove($alertExiste);
                        $em->flush();
                    }
                }
            }

            $this->get('session')->getFlashBag()->add('success', 'Vos modifications ont bien été enregistrées.');
            return $this->redirectToRoute('front.account.alerts');
        }

        return array('user' => $trainee, 'alerts' => $alertsTrainee, 'form' => $form->createView());
    }

}

<?php

namespace App\Controller;

use App\Entity\Participation;
use App\Entity\Session;
use App\Entity\DateSession;
use App\Entity\Inscription;
use App\Form\Type\DateSessionType;
use App\Controller\Core\AbstractSessionController;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Route("/training/session")
 */
class SessionController extends AbstractSessionController
{
    protected $sessionClass = Session::class;
    protected $participationClass = Participation::class;
    protected $dateClass = DateSession::class;

    /**
     * @Route("/adddates/{session}", name="dates.add", requirements={"id" = "\d+"}, options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("session", class="SygeforMyCompanyBundle:Session", options={"id" = "session"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function adddatesAction(Session $session, Request $request)
    {
        $dateSession = new $this->dateClass;
        $dateSession->setSession($session);
        $daysSum =0;
        $hoursSum = 0;

        $form        = $this->createForm(new DateSessionType(), $dateSession);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $existingDate = null;
                $datesBegin = array();
                $datesEnd = array();
                /** @var DateSession $existingDate */
                foreach ($session->getDates() as $existingDate) {
                    if ($existingDate->getDateBegin() == $dateSession->getDateBegin()) {
                        $form->get('dateBegin')->addError(new FormError('Cette date est déjà associé à cet évènement.'));
                        return array('form' => $form->createView(), 'dates' => $dateSession);
                    }
                    $datesBegin[] = $existingDate->getDateBegin();
                    $datesEnd[] = $existingDate->getDateEnd();

                    if (($existingDate->getDateBegin() == $existingDate->getDateEnd()) || ($existingDate->getDateEnd() == null)) {
                        $daysSum++;
                        $hoursSum += ($existingDate->getHourNumberMorn() + $existingDate->getHourNumberAfter());
                    }
                    else {
                        $daysSum += $existingDate->getDateBegin()->diff($existingDate->getDateEnd())->format('%a') + 1;
                        $hoursSum += ($existingDate->getHourNumberMorn() + $existingDate->getHourNumberAfter()) * ($existingDate->getDateBegin()->diff($existingDate->getDateEnd())->format('%a') + 1);
                    }
                }

                if (!$existingDate || ($existingDate->getDateBegin() !== $dateSession->getDateBegin())) {
                    $session->addDates($dateSession);
                    $session->updateTimestamps();
                    $session->getTraining()->updateTimestamps();
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($dateSession);
                    $em->flush();
                    $datesBegin[] = $dateSession->getDateBegin();
                    $datesEnd[] = $dateSession->getDateEnd();

                    // Calcul nombre de jours
                    if (($dateSession->getDateBegin() == $dateSession->getDateEnd()) || ($dateSession->getDateEnd() == null)) {
                        $daysSum++;
                        $hoursSum += ($dateSession->getHourNumberMorn() + $dateSession->getHourNumberAfter());
                    }
                    else {
                        $daysSum += $dateSession->getDateBegin()->diff($dateSession->getDateEnd())->format('%a') + 1;
                        $hoursSum += ($dateSession->getHourNumberMorn() + $dateSession->getHourNumberAfter()) * ($dateSession->getDateBegin()->diff($dateSession->getDateEnd())->format('%a') + 1);
                    }
                }

                // Tri des tableaux de dates
                usort($datesBegin, function($a, $b) {
                    return $a < $b ? -1: 1;
                });
                usort($datesEnd, function($a, $b) {
                    return $a < $b ? -1: 1;
                });

                // Renseigner le lieu
                $session->setPlace($session->getDates()[0]->getPlace());

                // Renseigner le nombre d'heures
                $session->setHourNumber($hoursSum);

                // Renseigner le nombre de jours
                $session->setDayNumber($daysSum);

                // Récupérer les dates min et max début et fin pour les caler dans les dates de session
                $session->setDateBegin($datesBegin[0]);
                $session->setDateEnd($datesEnd[count($datesEnd)-1]);
                $em = $this->getDoctrine()->getManager();
                $em->persist($session);
                $em->flush();

             }
        }

        return array('form' => $form->createView(), 'date' => $dateSession);
    }

        /**
     * @Route("/{session}/remove/{dates}", name="dates.remove", options={"expose"=true}, defaults={"_format" = "json"})
     * @Method("POST")
     * @ParamConverter("session", class="SygeforMyCompanyBundle:Session", options={"id" = "session"})
     * @ParamConverter("dates", class="SygeforMyCompanyBundle:DateSession", options={"id" = "dates"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function removedatesAction(Session $session, DateSession $dates)
    {
        $session->removeDate($dates);
        $session->updateTimestamps();
        $session->getTraining()->updateTimestamps();
        $this->getDoctrine()->getManager()->remove($dates);
        $this->getDoctrine()->getManager()->flush();

        // Traitement des dates min et max
        $datesBegin = array();
        $datesEnd = array();
        $hoursSum = 0;
        $daysSum = 0;

        /** @var DateSession $existingDate */
        foreach ($session->getDates() as $existingDate) {
            $datesBegin[] = $existingDate->getDateBegin();
            $datesEnd[] = $existingDate->getDateEnd();

            if (($existingDate->getDateBegin() == $existingDate->getDateEnd()) || ($existingDate->getDateEnd() == null)) {
                $daysSum++;
                $hoursSum += ($existingDate->getHourNumberMorn() + $existingDate->getHourNumberAfter());
            }
            else {
                $daysSum += $existingDate->getDateBegin()->diff($existingDate->getDateEnd())->format('%a') + 1;
                $hoursSum += ($existingDate->getHourNumberMorn() + $existingDate->getHourNumberAfter()) * ($existingDate->getDateBegin()->diff($existingDate->getDateEnd())->format('%a') + 1);
            }
        }
        // Tri des tableaux de dates
        usort($datesBegin, function($a, $b) {
            return $a < $b ? -1: 1;
        });
        usort($datesEnd, function($a, $b) {
            return $a < $b ? -1: 1;
        });

        // Récupérer les dates min et max début et fin pour les caler dans les dates de session
        if (!empty($datesBegin) && isset($datesBegin)) {
            $session->setDateBegin($datesBegin[0]);
            $session->setDateEnd($datesEnd[count($datesEnd) - 1]);
            $session->setHourNumber($hoursSum);
            $session->setDayNumber($daysSum);
            $em = $this->getDoctrine()->getManager();
            $em->persist($session);
            $em->flush();
        }

        return;
    }

    /**
     * This action attach a form to the return array when the user has the permission to edit the training.
     *
     * @Route("/editdates/{dates}", name="dates.edit", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("dates", class="SygeforMyCompanyBundle:DateSession", options={"id" = "dates"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function editdatesAction(DateSession $dates, Request $request )
    {
        $session = $dates->getSession();
        $form = $this->createForm(new DateSessionType(), $dates);
        $daysSum =0;
        $hoursSum = 0;

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                //Mise à jour date
                $em = $this->getDoctrine()->getManager();
                $em->flush();

                $existingDate = null;
                $datesBegin = array();
                $datesEnd = array();
                /** @var DateSession $existingDate */
                foreach ($session->getDates() as $existingDate) {
                    $datesBegin[] = $existingDate->getDateBegin();
                    $datesEnd[] = $existingDate->getDateEnd();

                    if (($existingDate->getDateBegin() == $existingDate->getDateEnd()) || ($existingDate->getDateEnd() == null)) {
                        $daysSum++;
                        $hoursSum += ($existingDate->getHourNumberMorn() + $existingDate->getHourNumberAfter());
                    }
                    else {
                        $daysSum += $existingDate->getDateBegin()->diff($existingDate->getDateEnd())->format('%a') + 1;
                        $hoursSum += ($existingDate->getHourNumberMorn() + $existingDate->getHourNumberAfter()) * ($existingDate->getDateBegin()->diff($existingDate->getDateEnd())->format('%a') + 1);
                    }

                }

                // Tri des tableaux de dates
                usort($datesBegin, function($a, $b) {
                    return $a < $b ? -1: 1;
                });
                usort($datesEnd, function($a, $b) {
                    return $a < $b ? -1: 1;
                });

                // Récupérer le lieu
                $session->setPlace($session->getDates()[0]->getPlace());

                // Renseigner le nombre d'heures
                $session->setHourNumber($hoursSum);

                // Renseigner le nombre de jours
                $session->setDayNumber($daysSum);

                // Récupérer les dates min et max début et fin pour les caler dans les dates de session
                $session->setDateBegin($datesBegin[0]);
                $session->setDateEnd($datesEnd[count($datesEnd)-1]);
                $em = $this->getDoctrine()->getManager();
                $em->persist($session);
                $em->flush();

            }
        }

        return array('form' => $form->createView(), 'dates' => $dates);
    }

    /**
     * This action attach a form to the return array when the user has the permission to edit the training.
     *
     * @Route("/viewdates/{dates}", name="dates.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("dates", class="SygeforMyCompanyBundle:DateSession", options={"id" = "dates"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function viewdatesAction(DateSession $dates, Request $request )
    {
        $form = $this->createForm(new DateSessionType(), $dates);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'dates' => $dates);
    }

}

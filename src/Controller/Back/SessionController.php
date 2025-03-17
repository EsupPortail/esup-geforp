<?php

namespace App\Controller\Back;

use App\Entity\Back\Participation;
use App\Entity\Back\Session;
use App\Entity\Back\DateSession;
use App\Entity\Back\Inscription;
use App\Entity\Core\AbstractSession;
use App\Form\Type\DateSessionType;
use App\Controller\Core\AbstractSessionController;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
     * @ParamConverter("session", class="App\Entity\Back\Session", options={"id" = "session"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function adddatesAction(Session $session, Request $request, ManagerRegistry $doctrine)
    {
        $dateSession = new $this->dateClass;
        $dateSession->setSession($session);
        $daysSum =0;
        $hoursSum = 0;

        $form        = $this->createForm(DateSessionType::class, $dateSession);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $existingDate = null;
                $datesBegin = array();
                $datesEnd = array();
                /** @var DateSession $existingDate */
                foreach ($session->getDates() as $existingDate) {
                    if ($existingDate->getDatebegin() == $dateSession->getDatebegin()) {
                        $form->get('datebegin')->addError(new FormError('Cette date est déjà associé à cet évènement.'));
                        return array('form' => $form->createView(), 'dates' => $dateSession);
                    }
                    $datesBegin[] = $existingDate->getDatebegin();
                    $datesEnd[] = $existingDate->getDateend();

                    if (($existingDate->getDatebegin() == $existingDate->getDateend()) || ($existingDate->getDateend() == null)) {
                        $daysSum++;
                        $hoursSum += ($existingDate->getHournumbermorn() + $existingDate->getHournumberafter());
                    }
                    else {
                        $daysSum += $existingDate->getDatebegin()->diff($existingDate->getDateend())->format('%a') + 1;
                        $hoursSum += ($existingDate->getHournumbermorn() + $existingDate->getHournumberafter()) * ($existingDate->getDatebegin()->diff($existingDate->getDateend())->format('%a') + 1);
                    }
                }

                if (!$existingDate || ($existingDate->getDatebegin() !== $dateSession->getDatebegin())) {
                    $session->addDates($dateSession);
                    $session->setUpdatedAt(new \DateTime('now'));
                    $session->getTraining()->setUpdatedAt(new \DateTime('now'));
                    $em = $doctrine->getManager();
                    $em->persist($dateSession);
                    $em->flush();
                    $datesBegin[] = $dateSession->getDatebegin();
                    $datesEnd[] = $dateSession->getDateend();

                    // Calcul nombre de jours
                    if (($dateSession->getDatebegin() == $dateSession->getDateend()) || ($dateSession->getDateend() == null)) {
                        $daysSum++;
                        $hoursSum += ($dateSession->getHournumbermorn() + $dateSession->getHournumberafter());
                    }
                    else {
                        $daysSum += $dateSession->getDatebegin()->diff($dateSession->getDateend())->format('%a') + 1;
                        $hoursSum += ($dateSession->getHournumbermorn() + $dateSession->getHournumberafter()) * ($dateSession->getDatebegin()->diff($dateSession->getDateend())->format('%a') + 1);
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
                $session->setHournumber($hoursSum);

                // Renseigner le nombre de jours
                $session->setDaynumber($daysSum);

                // Récupérer les dates min et max début et fin pour les caler dans les dates de session
                $session->setDatebegin($datesBegin[0]);
                $session->setDateend($datesEnd[count($datesEnd)-1]);
                $em = $doctrine->getManager();
                $em->persist($session);
                $em->flush();

             }
        }

        return array('form' => $form->createView(), 'date' => $dateSession);
    }

        /**
     * @Route("/{session}/remove/{dates}", name="dates.remove", options={"expose"=true}, defaults={"_format" = "json"})
     * @Method("POST")
     * @ParamConverter("session", class="App\Entity\Back\Session", options={"id" = "session"})
     * @ParamConverter("dates", class="App\Entity\Back\DateSession", options={"id" = "dates"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function removedatesAction(Session $session, DateSession $dates, ManagerRegistry $doctrine)
    {
        $session->removeDate($dates);
        $session->setUpdatedAt(new \DateTime('now'));
        $session->getTraining()->setUpdatedAt(new \DateTime('now'));
        $doctrine->getManager()->remove($dates);
        $doctrine->getManager()->flush();

        // Traitement des dates min et max
        $datesBegin = array();
        $datesEnd = array();
        $hoursSum = 0;
        $daysSum = 0;

        /** @var DateSession $existingDate */
        foreach ($session->getDates() as $existingDate) {
            $datesBegin[] = $existingDate->getDatebegin();
            $datesEnd[] = $existingDate->getDateend();

            if (($existingDate->getDatebegin() == $existingDate->getDateend()) || ($existingDate->getDateend() == null)) {
                $daysSum++;
                $hoursSum += ($existingDate->getHournumbermorn() + $existingDate->getHournumberafter());
            }
            else {
                $daysSum += $existingDate->getDatebegin()->diff($existingDate->getDateend())->format('%a') + 1;
                $hoursSum += ($existingDate->getHournumbermorn() + $existingDate->getHournumberafter()) * ($existingDate->getDatebegin()->diff($existingDate->getDateend())->format('%a') + 1);
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
            $session->setDatebegin($datesBegin[0]);
            $session->setDateend($datesEnd[count($datesEnd) - 1]);
            $session->setHournumber($hoursSum);
            $session->setDaynumber($daysSum);
            $em = $doctrine->getManager();
            $em->persist($session);
            $em->flush();
        }

        return;
    }

    /**
     * This action attach a form to the return array when the user has the permission to edit the training.
     *
     * @Route("/editdates/{dates}", name="dates.edit", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("dates", class="App\Entity\Back\DateSession", options={"id" = "dates"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function editdatesAction(DateSession $dates, Request $request, ManagerRegistry $doctrine)
    {
        $session = $dates->getSession();
        $form = $this->createForm(DateSessionType::class, $dates);
        $daysSum =0;
        $hoursSum = 0;

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                //Mise à jour date
                $em = $doctrine->getManager();
                $em->flush();

                $existingDate = null;
                $datesBegin = array();
                $datesEnd = array();
                /** @var DateSession $existingDate */
                foreach ($session->getDates() as $existingDate) {
                    $datesBegin[] = $existingDate->getDatebegin();
                    $datesEnd[] = $existingDate->getDateend();

                    if (($existingDate->getDatebegin() == $existingDate->getDateend()) || ($existingDate->getDateend() == null)) {
                        $daysSum++;
                        $hoursSum += ($existingDate->getHournumbermorn() + $existingDate->getHournumberafter());
                    }
                    else {
                        $daysSum += $existingDate->getDatebegin()->diff($existingDate->getDateend())->format('%a') + 1;
                        $hoursSum += ($existingDate->getHournumbermorn() + $existingDate->getHournumberafter()) * ($existingDate->getDatebegin()->diff($existingDate->getDateend())->format('%a') + 1);
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
                $session->setHournumber($hoursSum);

                // Renseigner le nombre de jours
                $session->setDaynumber($daysSum);

                // Récupérer les dates min et max début et fin pour les caler dans les dates de session
                $session->setDatebegin($datesBegin[0]);
                $session->setDateend($datesEnd[count($datesEnd)-1]);
                $em = $doctrine->getManager();
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
     * @ParamConverter("dates", class="App\Entity\Back\DateSession", options={"id" = "dates"})
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     */
    public function viewdatesAction(DateSession $dates, Request $request, ManagerRegistry $doctrine)
    {
        $form = $this->createForm(DateSessionType::class, $dates);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $doctrine->getManager();
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'dates' => $dates);
    }

    /**
     * @param Request              $request
     * @param DateSession          $dates
     *
     * @Route("/duplicate/{id}}", requirements={"id" = "\d+"}, name="dates.duplicate", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("dates", class="App\Entity\Back\DateSession", isOptional="true")
     * @Rest\View(serializerGroups={"Default", "session"}, serializerEnableMaxDepthChecks=true)
     *
     * @return array
     */
    public function duplicatedatesAction(Request $request, ManagerRegistry $doctrine, DateSession $dates)
    {
        // we need at least one of both arguments
        if (!$dates) {
            throw new MissingOptionsException('You have to pass a dates id');
        }

        // new session can't be created if user has no rights for it
        if (!$this->isGranted('EDIT', $dates->getSession()->getTraining())) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $cloned = clone $dates;
        $form = $this->createFormBuilder($cloned)
            ->add('datebegin', DateType::class, array(
                'label' => 'Date de début',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => true,
            ))
            ->add('dateend', DateType::class, array(
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'format' => 'dd/MM/yyyy',
                'html5' => false,
                'required' => false,
            ));

        $form = $form->getForm();
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $em = $doctrine->getManager();
                $em->persist($cloned);
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'dates' => $dates);
    }
}

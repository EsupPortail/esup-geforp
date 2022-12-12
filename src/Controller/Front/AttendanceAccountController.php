<?php

namespace App\Controller\Front;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\QueryBuilder;
use App\Entity\Term\Presencestatus;
use App\Entity\Back\EvaluationNotedCriterion;
use App\Entity\Back\Inscription;
use App\Form\Type\EvaluationType;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Snappy\Pdf;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This controller regroup actions related to attendance.
 *
 * @Route("/account")
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
class AttendanceAccountController extends AbstractController
{
    /**
     * All attendances of the trainee
     * @Route("/attendances", name="front.account.attendances")
     * @Template("Front/Account/attendance/attendances.html.twig")
     * @Method("GET")
     */
    public function attendancesAction(Request $request, ManagerRegistry $doctrine)
    {
        // recup trainee
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        // Recup param evaluations
        $evalActif = $this->getParameter('eval_actif');

        // recup inscriptions
        $qb          = $this->createQueryBuilder($doctrine, $trainee);
        $attendances = $qb->getQuery()->getResult();

        return array('user' => $trainee, 'attendances' => $attendances, 'evalActif' => $evalActif);
    }

    /**
     * Single attendance.
     *
     * @Route("/attendance/{session}", name="front.account.attendance")
     * @Template("Front/Account/attendance/attendance.html.twig")
     * @Method("GET")
     */
    public function attendanceAction($session, ManagerRegistry $doctrine, Request $request)
    {
        // recup trainee
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        // Recup param pour l'activation des evaluations
        $evalActif = $this->getParameter('eval_actif');

        /** @var Inscription $attendance */
        $attendance = $this->getAttendance($doctrine, $session, $trainee);
        $session = $attendance->getSession();
        $allMaterials = new ArrayCollection();
        foreach ($session->getMaterials() as $material) {
            $allMaterials->add($material);
        }
/*        foreach ($session->getTraining()->getMaterials() as $material) {
            $allMaterials->add($material);
        }*/
        $attendance->getSession()->setAllMaterials($allMaterials);

        return array('user' => $trainee, 'attendance' => $attendance, 'evalActif' => $evalActif);
    }

    /**
     * @Route("/attendance/{id}/evaluation", name="front.account.attendance.evaluation")
     * @ParamConverter("attendance", class="App\Entity\Back\Inscription", options={"id" = "id"})
     * @Template("Front/Account/attendance/evaluation.html.twig")
     */
    public function evaluationAction(Request $request, ManagerRegistry $doctrine, Inscription $attendance)
    {
        // recup trainee
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        // Recup params pour les critères d'évalutation
        $evalCritere0Actif = $this->getParameter('eval_critere_0_actif');
        $evalCritere1 = $this->getParameter('eval_critere_1');
        $evalCritere2 = $this->getParameter('eval_critere_2');
        $evalCritere3 = $this->getParameter('eval_critere_3');
        $evalCritere4 = $this->getParameter('eval_critere_4');
        $evalMessage = $this->getParameter('eval_message');

        //Construction du tableau de choix du formulaire d'évaluation
        if ($evalCritere0Actif) {
            $tabEvalChoices = array(
                "Non concerné" => 0,
                $evalCritere4 => 4,
                $evalCritere3 => 3,
                $evalCritere2 => 2,
                $evalCritere1 => 1);
        } else {
            $tabEvalChoices = array(
                $evalCritere4 => 4,
                $evalCritere3 => 3,
                $evalCritere2 => 2,
                $evalCritere1 => 1);
        }

        if ($attendance->getCriteria() && $attendance->getCriteria()->count() > 0) {
            throw new AccessDeniedHttpException("This session has already been evaluated.");
        }

        $evaluationCriterions = $doctrine
            ->getRepository('App\Entity\Term\EvaluationCriterion')
            ->findBy(array('organization'=> $attendance->getSession()->getTraining()->getOrganization()));

        foreach ($evaluationCriterions as $evaluationCriterion) {
            $evaluationNotedCriterion = new EvaluationNotedCriterion();
            $evaluationNotedCriterion->setInscription($attendance);
            $evaluationNotedCriterion->setCriterion($evaluationCriterion);
            $attendance->addCriterion($evaluationNotedCriterion);
        }
        $form = $this->createForm(EvaluationType::class, $attendance, array('tab_eval' => $tabEvalChoices, 'message' => $evalMessage));
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $doctrine->getManager()->flush();
                $this->get('session')->getFlashBag()->add('success', "Les réponses ont bien été enregistrées. Merci d'avoir noté la session.");
                return $this->redirectToRoute('front.account.attendance', array('session' => $attendance->getSession()->getId()));
            }
        }

        return array('user' => $trainee, 'attendance' => $attendance, 'form' => $form->createView());
    }

    /**
     * Download a material
     * @Route("/attendance/{session}/download/{material}", name="front.account.attendance.download")
     * @Method("GET")
     */
    public function downloadAction(Request $request, ManagerRegistry $doctrine, $session, $material)
    {
        // recup trainee
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        $attendance   = $this->getAttendance($doctrine, $session, $trainee);
        $allMaterials = array();
        $material     = intval($material);

        // get all materials
        foreach ($attendance->getSession()->getMaterials() as $sessionMaterial) {
            $allMaterials[$sessionMaterial->getId()] = $sessionMaterial;
        }
/*        foreach ($attendance->getSession()->getTraining()->getMaterials() as $trainingMaterial) {
            $allMaterials[$trainingMaterial->getId()] = $trainingMaterial;
        }
*/
        foreach ($allMaterials as $_material) {
            if ($_material->getId() === $material) {
                $material = $_material;
                if ($material->getType() === 'file') {
                    return $material->send();
                }
                else if ($material->getType() === 'link') {
                    return new RedirectResponse($_material->getUrl());
                }
            }
        }

        throw new NotFoundHttpException('Unknown resource.');
    }

    /**
     * Attestation of attendance
     * @Route("/attendance/{session}/attestation", name="front.account.attendance.attestation")
     * @Method("GET")
     */
    public function attestationAction($session, ManagerRegistry $doctrine, Request $request, Pdf $knpPdf)
    {
        // recup trainee
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        $attendance = $this->getAttendance($doctrine, $session, $trainee);
        $session = $attendance->getSession();

        // Gestion nombre d'heures de formation
        // On crée le tableau de dates correspondant au tableau des présences
        $tabDates = array();$nbJoursDate2 = -1;
        foreach ($session->getDates() as $dateSes) {
            // Conversion date de début de session
            $dateDeb = $dateSes->getDateBegin();
            $dateNewS = $dateDeb->format('d/m/Y');
            $tab = explode('/', $dateNewS);
            $dateNew = new \DateTime();
            $dateNew->setDate($tab[2], $tab[1], $tab[0]);

            $nbJoursDate2 = date_diff($dateSes->getDateEnd(), $dateSes->getDateBegin());
            $nbJoursDate = $nbJoursDate2->format('%a');
            // création du tableau des dates suivant le nombre de jours à afficher
            for ($j = 0; $j < $nbJoursDate + 1; $j++) {
                $tabDates[] = array("dateDeb" => $dateNew->format('d/m/Y'), "nbHeuresMatin" => $dateSes->getHourNumberMorn(), "nbHeuresApr" => $dateSes->getHourNumberAfter());
                $dateNew->modify('+ 1 days');

            }
        }

        // calcul du nombre d'heures de présence effective
        // On initialise le nombre d'heures de présence
        $nbHeuresPresence = 0;
        // Pour chaque presence, on compare avec le tableau des dates et on calcule le nombre d'heures
        foreach ($attendance->getPresences() as $pres) {
            foreach ($tabDates as $datePres) {
                if ($pres->getDateBegin()->format('d/m/Y') == $datePres["dateDeb"]) {
                    if ($pres->getMorning() == "Présent") {
                        $nbHeuresPresence += $datePres["nbHeuresMatin"];
                    }
                    if ($pres->getAfternoon() == "Présent") {
                        $nbHeuresPresence += $datePres["nbHeuresApr"];
                    }
                    break;
                }
            }
        }
        $nbHeuresSession = $session->getHourNumber();

        // Recuperation des fichiers logos et signature
        $organization = $session->getTraining()->getOrganization();
        $images = $doctrine->getRepository('App\Entity\Term\ImageFile')->findBy(array('organization' => $organization));

        //checking file existence
        $fileSignature = null;
        $fileLogo = null;
        $fs = new Filesystem();
        foreach ($images as $img) {
            $fileName = $img->getName();
            if(strpos($fileName, 'logo') !== false){
                if ($fs->exists($this->get('parameter_bag')->get('kernel.project_dir') . '/public/img/vocabulary/'.$img->getFilepath())) {
                    $fileLogo = 'img/vocabulary/'.$img->getFilepath();
                }
            }
            if(strpos($fileName, 'signature') !== false){
                if ($fs->exists($this->get('parameter_bag')->get('kernel.project_dir') . '/public/img/vocabulary/'.$img->getFilepath())) {
                    $fileSignature = 'img/vocabulary/'.$img->getFilepath();
                }
            }
        }

        $pdf = $this->renderView('PDF/attestation.pdf.twig', array(
            'inscription' => $attendance,
            'nbHeuresPresence' => $nbHeuresPresence."/".$nbHeuresSession,
            'logo' => $fileLogo,
            'signature' => $fileSignature
        ));

        return new Response(
            $knpPdf->getOutputFromHtml($pdf, array('print-media-type' => null)), 200,
            array(
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="attestation.pdf"', )
        );
    }

    /**
     * Return the attendance belong to the session.
     *
     * @return AbstractInscription
     */
    private function getAttendance($doctrine, $session, $trainee)
    {
        $qb = $this->createQueryBuilder($doctrine, $trainee);
        $qb->andWhere('i.session = :session')
            ->setParameter('session', $session);
        $attendance = $qb->getQuery()->getOneOrNullResult();
        if( ! $attendance) {
            throw new NotFoundHttpException('Unknown attendance.');
        }

        return $attendance;
    }

    /**
     * Create a specific query builder for attendees.
     *
     * @return QueryBuilder
     */
    private function createQueryBuilder($doctrine, $trainee)
    {
        $em         = $doctrine->getManager();
        $repository = $em->getRepository('App\Entity\Core\AbstractInscription');
        /** @var QueryBuilder $qb */
        $qb = $repository->createQueryBuilder('i');
        // only for the current user
        $qb->where('i.trainee = :trainee')
            ->setParameter('trainee', $trainee);
        // only with the PRESENT status
        $qb->join('i.presencestatus', 'p');
        $qb->andWhere('p.status = :presenceStatus')
            ->setParameter('presenceStatus', Presencestatus::STATUS_PRESENT);
        // only past sessions
        $qb->join('i.session', 's');
        $qb->andWhere('s.datebegin <= CURRENT_DATE()');

        return $qb;
    }
}

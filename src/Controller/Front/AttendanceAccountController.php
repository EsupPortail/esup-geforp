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
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This controller regroup actions related to attendance.
 *
 */
#[Route(path: '/account')]
class AttendanceAccountController extends AbstractController
{
    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException('Vous devez être pleinement authentifié pour accéder à cette page.');
        }
        return $this->render('Front/Account/profile/profile.html.twig');
    }
    #[Route('/attendances', methods: ['GET'])]
    #[Route(path: '/attendances', name: 'front.account.attendances')]
    public function attendances(ManagerRegistry $doctrine): array
    {
        // recup trainee
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        // Recup param evaluations
        $evalActif = $this->getParameter('eval_actif');

        // recup inscriptions
        $qb          = $this->createQueryBuilder($doctrine, $trainee);
        $attendances = $qb->getQuery()->getResult();

        return ['user' => $trainee, 'attendances' => $attendances, 'evalActif' => $evalActif, $this->render('Front/Account/attendance/attendances.html.twig')];
    }

    #[Route(path: '/attendance/{session}', name: 'front.account.attendance', methods: ['GET'])]
    public function attendance($session, ManagerRegistry $doctrine): array
    {
        // recup trainee
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findByEmail($user->getCredentials()['mail']);
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

        return ['user' => $trainee, 'attendance' => $attendance, 'evalActif' => $evalActif,
        $this->render('Front/Account/attendance/attendance.html.twig')];
    }

    #[Route(path: '/attendance/{id}/evaluation', name: 'front.account.attendance.evaluation')]
    public function evaluation(Request $request, ManagerRegistry $doctrine, Inscription $attendance, int $id): Response
    {
        $attendance = $doctrine->getRepository(\App\Entity\Back\Inscription::class)->find($id);
        if (!$attendance) {
            throw $this->createNotFoundException();
        }
        // recup trainee
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findByEmail($user->getCredentials()['mail']);
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
            $tabEvalChoices = ["Non concerné" => 0, $evalCritere4 => 4, $evalCritere3 => 3, $evalCritere2 => 2, $evalCritere1 => 1];
        } else {
            $tabEvalChoices = [$evalCritere4 => 4, $evalCritere3 => 3, $evalCritere2 => 2, $evalCritere1 => 1];
        }

        if ($attendance->getCriteria() && $attendance->getCriteria()->count() > 0) {
            // Pb : l'évaluation a déjà été remplie
            $this->get('session')->getFlashBag()->add('error', 'Vous avez déjà évalué cette formation. Vous ne pouvez pas renseigner l\'évaluation à nouveau.');
            return $this->render('Front/Account/attendance/evaluation.html.twig');

        }

        $evaluationCriterionsLoc = $doctrine
            ->getRepository('App\Entity\Term\EvaluationCriterion')
            ->findBy(['organization'=> $attendance->getSession()->getTraining()->getOrganization()], ['name' => 'ASC']);
        $evaluationCriterionsNat = $doctrine
            ->getRepository('App\Entity\Term\EvaluationCriterion')
            ->findBy(['organization'=> null], ['name' => 'ASC']);
        $evaluationCriterions = array_merge($evaluationCriterionsLoc, $evaluationCriterionsNat);

        foreach ($evaluationCriterions as $evaluationCriterion) {
            $evaluationNotedCriterion = new EvaluationNotedCriterion();
            $evaluationNotedCriterion->setInscription($attendance);
            $evaluationNotedCriterion->setCriterion($evaluationCriterion);
            $attendance->addCriterion($evaluationNotedCriterion);
        }
        $form = $this->createForm(EvaluationType::class, $attendance, ['tab_eval' => $tabEvalChoices, 'message' => $evalMessage]);
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $doctrine->getManager()->flush();
                $this->get('session')->getFlashBag()->add('success', "Les réponses ont bien été enregistrées. Merci d'avoir noté la session.");
                return $this->redirectToRoute('front.account.attendance', ['session' => $attendance->getSession()->getId()]);
            }
        }

        return ['user' => $trainee, 'attendance' => $attendance, 'form' => $form->createView()];
    }


    #[Route(path: '/attendance/{session}/download/{material}', name: 'front.account.attendance.download', methods: ['GET'])]
    public function download(ManagerRegistry $doctrine, $session, $material)
    {
        // recup trainee
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        $attendance   = $this->getAttendance($doctrine, $session, $trainee);
        $allMaterials = [];
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

    #[Route(path: '/attendance/{session}/attestation', name: 'front.account.attendance.attestation', methods: ['GET'])]
    public function attestation($session, ManagerRegistry $doctrine, Pdf $knpPdf): \Symfony\Component\HttpFoundation\Response
    {
        // recup trainee
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        $attendance = $this->getAttendance($doctrine, $session, $trainee);
        $session = $attendance->getSession();

        // Gestion nombre d'heures de formation
        // On crée le tableau de dates correspondant au tableau des présences
        $tabDates = [];$nbJoursDate2 = -1;
        foreach ($session->getDates() as $dateSes) {
            // Conversion date de début de session
            $dateDeb = $dateSes->getDateBegin();
            $dateNewS = $dateDeb->format('d/m/Y');
            $tab = explode('/', (string) $dateNewS);
            $dateNew = new \DateTime();
            $dateNew->setDate($tab[2], $tab[1], $tab[0]);

            $nbJoursDate2 = date_diff($dateSes->getDateEnd(), $dateSes->getDateBegin());
            $nbJoursDate = $nbJoursDate2->format('%a');
            // création du tableau des dates suivant le nombre de jours à afficher
            for ($j = 0; $j < $nbJoursDate + 1; $j++) {
                $tabDates[] = ["dateDeb" => $dateNew->format('d/m/Y'), "nbHeuresMatin" => $dateSes->getHourNumberMorn(), "nbHeuresApr" => $dateSes->getHourNumberAfter()];
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
        $images = $doctrine->getRepository(\App\Entity\Term\ImageFile::class)->findBy(['organization' => $organization]);

        //checking file existence
        $fileSignature = null;
        $fileLogo = null;
        $fs = new Filesystem();
        foreach ($images as $img) {
            $fileName = $img->getName();
            if(str_contains($fileName, 'logo')){
                if ($fs->exists($this->get('parameter_bag')->get('kernel.project_dir') . '/public/img/vocabulary/'.$img->getFilepath())) {
                    $fileLogo = 'https://' . $this->getParameter('front_host') . '/img/vocabulary/'.$img->getFilepath();
                }
            }
            if(str_contains($fileName, 'signature')){
                if ($fs->exists($this->get('parameter_bag')->get('kernel.project_dir') . '/public/img/vocabulary/'.$img->getFilepath())) {
                    $fileSignature = 'https://' . $this->getParameter('front_host') . '/img/vocabulary/'.$img->getFilepath();
                }
            }
        }

        // patch pb encodage HTML
        $firstNameTrainee = htmlentities((string) $attendance->getTrainee()->getFirstname());
        $attendance->getTrainee()->setFirstname($firstNameTrainee);
        $lastNameTrainee = htmlentities((string) $attendance->getTrainee()->getLastname());
        $attendance->getTrainee()->setLastname($lastNameTrainee);
        $orgName = htmlentities((string) $session->getTraining()->getOrganization()->getName());
        $session->getTraining()->getOrganization()->setName($orgName);
        $orgAdr = htmlentities((string) $session->getTraining()->getOrganization()->getAddress());
        $session->getTraining()->getOrganization()->setAddress($orgAdr);
        $orgCity = htmlentities((string) $session->getTraining()->getOrganization()->getCity());
        $session->getTraining()->getOrganization()->setCity($orgCity);
        $nameForm = htmlentities((string) $session->getTraining()->getName());
        $session->getTraining()->setName($nameForm);
        $trainers = $session->getTrainers();
        foreach ($trainers as $trainer) {
            $firstNameTrainer = htmlentities((string) $trainer->getFirstname());
            $trainer->setFirstName($firstNameTrainer);
            $lastNameTrainer = htmlentities((string) $trainer->getLastname());
            $trainer->setLastname($lastNameTrainer);
        }
        $session->getTraining()->setName($nameForm);

        $pdf = $this->renderView('PDF/attestation.pdf.twig', ['inscription' => $attendance, 'nbHeuresPresence' => $nbHeuresPresence."/".$nbHeuresSession, 'logo' => $fileLogo, 'signature' => $fileSignature]);

        return new Response(
            $knpPdf->getOutputFromHtml($pdf, ['print-media-type' => null]), \Symfony\Component\HttpFoundation\Response::HTTP_OK,
            ['Content-Type'        => 'application/pdf', 'Content-Disposition' => 'attachment; filename="attestation.pdf"']
        );
    }

    private function getAttendance(\Doctrine\Persistence\ManagerRegistry $doctrine, $session, $trainee)
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


    private function createQueryBuilder(\Doctrine\Persistence\ManagerRegistry $doctrine, $trainee)
    {
        $em         = $doctrine->getManager();
        $repository = $em->getRepository(\App\Entity\Core\AbstractInscription::class);
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

<?php
/**
 * Created by PhpStorm.
 */

namespace App\Controller\Front;

use App\BatchOperations\BatchOperationRegistry;
use App\BatchOperations\Generic\EmailingBatchOperation;
use App\Entity\Core\AbstractInscription;
use App\Entity\Back\Inscription;
use App\Entity\Core\AbstractTraining;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Term\Emailtemplate;
use App\Entity\Term\Inscriptionstatus;
use App\Entity\Back\Organization;
use App\Form\Type\AuthorizationType;
use App\Vocabulary\VocabularyRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\ForbiddenOverwriteException;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;

#[Route(path: '/account/team')]
class TeamAccountController extends AbstractController
{
    protected $inscriptionClass = Inscription::class;

    public function Index(): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            // Si l'utilisateur n'est pas authentifié pleinement, on redirige ou on lève une exception
            throw new AccessDeniedException('Vous devez être pleinement authentifié pour accéder à cette page.');
        }
        return $this->render('Front/Account/team/registrations.html.twig');
    }

    #[Route(path: '/registrations', name: 'front.account.team.registrations', methods: ['GET'])]
    public function teamregistrations(ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();
        // Récupération du user avec le format trainee
        $arTraineeUser = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findOneBy(['email' => $user->getCredentials()['mail']]);
        $traineeUser = $arTraineeUser;

        // Recupération des agents dont on est responsable
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findBy(['emailsup' => $user->getCredentials()['mail'], 'isactive' => true]);

        $upcoming = [];
        $upcomingIds = [];
        $past = [];
        $pastEffective = [];
        $pastOther = [];
        $now = new \DateTime();

        if (!empty($arTrainee)) {
            foreach ($arTrainee as $trainee) {
                $inscriptions = $trainee->getInscriptions();

                foreach ($inscriptions as $inscription) {
                    if ($inscription->getSession()->getDatebegin() < $now) {
                        $past[] = $inscription;
                        $inscription->upcoming = false;
                        if (($inscription->getPresencestatus() != null) && ($inscription->getPresencestatus()->getStatus() == true)) {
                            $pastEffective[] = $inscription;
                        } else {
                            $pastOther[] = $inscription;
                        }
                    } else {
                        $inscription->upcoming = true;
                        $upcoming[] = $inscription;
                        $upcomingIds[] = $inscription->getId();
                    }
                }
            }
        }

        return $this->render('Front/Account/team/registrations.html.twig',['user' => $traineeUser, 'upcoming' => $upcoming, 'past' => $past, 'pastEffective' => $pastEffective, 'pastOther' => $pastOther, 'upcomingIds' => implode(',', $upcomingIds)]);
    }

    #[Route(path: '/trainees', name: 'front.account.team.trainees', methods: 'GET')]
    public function teamtrainees(ManagerRegistry $doctrine): Response
    {
        $user = $this->getUser();
        // Récupération du user avec le format trainee
        $arTraineeUser = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findOneBy(['email' => $user->getCredentials()['mail']]);
        $traineeUser = $arTraineeUser;

        // Recupération des agents dont on est responsable
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findBy(['emailsup' => $user->getCredentials()['mail'], 'isactive' => true]);

        return $this->render('Front/Account/team/trainees.html.twig',['user' => $traineeUser, 'trainees' => $arTrainee]);
    }


    #[Route(path: '/trainee/{id}/registrations', name: 'front.account.team.trainee.registrations', methods: 'GET')]
    public function traineeregistrations(ManagerRegistry $doctrine, $id): Response
    {
        $user = $this->getUser();
        $arSup = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findOneBy(['email' => $user->getCredentials()['mail']]);
        $sup = $arSup;

        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->find($id);
        $trainee = $arTrainee;
        if (!$trainee) {
            throw $this->createNotFoundException('Le stagiaire demandé n’existe pas.');
        }

        $access = 'OK';
        // On verifie que la personne connectée est bien le N+1 du stagiaire
        if ($sup->getEmail() != $trainee->getEmailsup()) {
            // Erreur : accès non autorisé
            $access = 'NOK';
        }


        $inscriptions = $trainee->getInscriptions();
        $upcoming = [];
        $upcomingIds = [];
        $past = [];
        $pastEffective = [];
        $pastOther = [];

        $now = new \DateTime();
        foreach ($inscriptions as $inscription) {
            if ($inscription->getSession()->getDatebegin() < $now) {
                $past[] = $inscription;
                $inscription->upcoming = false;
                if (($inscription->getPresencestatus() != null) && ($inscription->getPresencestatus()->getStatus() == true)) {
                    $pastEffective[] = $inscription;
                } else {
                    $pastOther[] = $inscription;
                }
            }
            else {
                $inscription->upcoming = true;
                $upcoming[] = $inscription;
                $upcomingIds[] = $inscription->getId();
            }
        }

        return $this->render('Front/Account/team/trainee-registrations.html.twig', ['user' => $sup, 'upcoming' => $upcoming, 'past' => $past, 'pastEffective' => $pastEffective, 'pastOther' => $pastOther, 'upcomingIds' => implode(',', $upcomingIds), 'trainee' => $trainee, 'access' => $access]);
    }

}
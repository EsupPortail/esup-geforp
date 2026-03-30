<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/15/16
 * Time: 11:00 AM
 */

namespace App\Controller\Front;

use App\Entity\Core\AbstractSession;
use App\Entity\Term\Theme;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Core\AbstractTraining;
use App\Entity\Back\Session;
use App\Entity\Back\Inscription;
use App\Entity\Back\Organization;
use App\Entity\Back\Alert;
use App\Entity\Back\MultipleAlert;
use App\Entity\Back\SingleAlert;
use App\Entity\Term\Emailtemplate;
use App\Repository\SessionRepository;
use App\Vocabulary\VocabularyRegistry;
use App\Form\Type\ProgramAlertType;
use App\Form\Type\ProgramSearchType;
use App\Form\Type\InscriptionType;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use mysql_xdevapi\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route(path: '/program')]
class ProgramController extends AbstractController
{

    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        // Vérification manuelle de l'authentification de l'utilisateur
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            // Si l'utilisateur n'est pas authentifié pleinement, on redirige ou on lève une exception
            throw new AccessDeniedException('Vous devez être pleinement authentifié pour accéder à cette page.');
        }

        // Vous pouvez continuer avec la logique du contrôleur...
        return $this->render('Front/Public/program/contact.html.twig');
    }
    

    #[Route(path: '/contact', name: 'front.program.contact')]
    public function contact(ManagerRegistry $doctrine): \Symfony\Component\HttpFoundation\Response
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findOneBy(['email' => $user->getCredentials()['mail']]);

        // si pas de trainee enregistré
        if (!isset($arTrainee)) {
            // redirect user to registration form
            $url = $this->generateUrl('front.account.register');
            return new RedirectResponse($url);
        } else {
            $trainee = $arTrainee;
        }

        // Récupération des établissements de la plate-forme
        $institutions = $doctrine->getRepository(\App\Entity\Back\Institution::class)->findBy([], ['name' => 'ASC']);
        $instContacts = [];
        foreach ($institutions as $institution) {
            if ($institution->getEmail() !== null)
                $instContacts[] = $institution;
        }
        return $this->render('Front/Public/program/contact.html.twig', ['etablissements' => $instContacts, 'user' => $trainee]);
    }

    #[Route(path: '/faq', name: 'front.program.faq')]
    public function faq(ManagerRegistry $doctrine): \Symfony\Component\HttpFoundation\Response
    {

        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findOneBy(['email' => $user->getCredentials()['mail']]);

        // si pas de trainee enregistré
        if (!isset($arTrainee)) {
            // redirect user to registration form
            $url = $this->generateUrl('front.account.register');
            return new RedirectResponse($url);
        } else {
            $trainee = $arTrainee;
        }

        return $this->render('Front/Public/program/faq.html.twig',['contact_mail' => $this->getParameter('contact_mail'), 'front_url' => $this->getParameter('front_url'), 'user' => $trainee]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param null $token
     * @param null $sessionId
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route(path: '/training/{id}/{sessionId}/{token}', name: 'front.program.training', requirements: ['id' => '\d+', 'sessionId' => '\d+'])]
    public function training(ManagerRegistry $doctrine, int $id, int $sessionId = null, $token = null)
    {

        $training = $doctrine->getRepository(\App\Entity\Core\AbstractTraining::class)->find($id);
        if (!isset($training)) {
            throw $this->createNotFoundException();
        }
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findOneBy(['email' => $user->getCredentials()['mail']]);

        // si pas de trainee enregistré
        if (!isset($arTrainee)) {
            // redirect user to registration form
            $url = $this->generateUrl('front.account.register');
            return new RedirectResponse($url);
        } else {
            $trainee = $arTrainee;

            $focusSession = null;
            foreach ($training->getSessions() as $session) {
                if ($session->getId() == $sessionId) {
                    $focusSession = $session;
                    break;
                }
            }

            $now = new \DateTime();
            $pastSessions = [];
            $upcomingSessions = [];

            // Mise en forme lien hypertexte dans programme du stage
            $programme = $training->getProgram();
            $programme = htmlspecialchars($programme, ENT_QUOTES, 'UTF-8');
            $programmeLien = preg_replace(
                '#(https://[^\s]+)#',
                '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>',
                $programme
            );
            $programmeLien = nl2br($programmeLien);

            /** @var Session $session */
            foreach ($training->getSessions() as $session) {

                $sesId = $session->getId();
                $inscription = null;

                /** @var EntityManager $em */
                $em = $doctrine->getManager();
                $inscription = $em->getRepository(\App\Entity\Core\AbstractInscription::class)->createQueryBuilder('inscription')
                    ->leftJoin(\App\Entity\Core\AbstractSession::class, 'session', 'WITH', 'inscription.session = session.id')
                    ->leftJoin(\App\Entity\Core\AbstractTrainee::class, 'trainee', 'WITH', 'inscription.trainee = trainee.id')
                    ->where('session.id = :sessionId')
                    ->andWhere('trainee.id = :traineeId')
                    ->setParameter('sessionId', $sesId)
                    ->setParameter('traineeId', $trainee->getId())
                    ->getQuery()->execute();

                $alert = $em->getRepository(\App\Entity\Back\Alert::class)->createQueryBuilder('alert')
                    ->leftJoin(\App\Entity\Core\AbstractSession::class, 'session', 'WITH', 'alert.session = session.id')
                    ->leftJoin(\App\Entity\Core\AbstractTrainee::class, 'trainee', 'WITH', 'alert.trainee = trainee.id')
                    ->where('session.id = :sessionId')
                    ->andWhere('trainee.id = :traineeId')
                    ->setParameter('sessionId', $sesId)
                    ->setParameter('traineeId', $trainee->getId())
                    ->getQuery()->execute();


                $session->isRegistered = !empty($inscription);

                $session->getDatebegin() > $now ? $upcomingSessions[] = $session : $pastSessions[] = $session;
                // Gestion des alertes existantes pour les sessions à venir
                if ($session->getDatebegin() > $now) {
                    $session->isAlerted = !empty($alert);
                }
                if ($session->getRegistration() === $session::REGISTRATION_PRIVATE) {
                    $session->availablePrivateSession = true;
                } else {
                    $session->availablePrivateSession = false;
                }

            }

            // Affichage d'un flag si le stage en public désigné
            if ($training->getDesignatedpublic())
                $this->addFlash('warning', 'Ce stage est réservé à un public désigné. Vous devez faire partie de la liste des personnes autorisées à s\'inscrire.');

            usort($pastSessions, function($a, $b) {
                return $b->getDatebegin() <=> $a->getDatebegin();
            });
            return $this->render('Front/Public/program/training.html.twig', [
                'user' => $trainee,
                'training' => $training,
                'programme' => $programmeLien,
                'session' => $focusSession,
                'upcomingSessions' => $upcomingSessions,
                'pastSessions' => $pastSessions,
                'token' => $token
            ]);
        }
    }

    /**
     * @param null $token
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route(path: '/training/inscription/{id}/{sessionId}/{token}', name: 'front.program.inscription', requirements: ['id' => '\d+', 'sessionId' => '\d+'])]
    public function inscription(Request $request, ManagerRegistry $doctrine, VocabularyRegistry $vocRegistry, MailerInterface $mailer, AbstractTraining $training, int $id, int $sessionId, $token = null): Response
    {
        $training = $doctrine->getRepository(\App\Entity\Core\AbstractTraining::class)->find($id);
        if (!isset($training)) {
            throw $this->createNotFoundException();
        }
        $session = $doctrine->getRepository(\App\Entity\Back\Session::class)->find($sessionId);
        if (!isset($session)) {
            throw $this->createNotFoundException();
        }
        // in case shibboleth authentication
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findOneBy(['email' => $user->getCredentials()['mail']]);
        $trainee = $arTrainee;

        if (!$trainee) {
            throw $this->createAccessDeniedException('Aucun stagiaire associé.');
        }
        $inscription = $doctrine->getManager()->getRepository(\App\Entity\Core\AbstractInscription::class)->findOneBy(['trainee' => $trainee, 'session'=> $session]);
        if ($inscription) {
            $this->addFlash('warning', "Vous êtes déjà inscrit à cette session.");
            return $this->redirectToRoute('front.account.registrations');
            //throw new ForbiddenOverwriteException('An inscription has already been found');
        }
        if (!$inscription) {
            $inscription = new Inscription();
            $inscription->setTrainee($trainee);
            $inscription->setSession($session);
            $datesInsc = ['begin' => $session->getDatebegin(), 'end' => $session->getDateend()];

            // Recuperation des inscriptions du stagiaire pour vérifier les dates de chevauchement
            $inscriptionsTrainee = $doctrine->getManager()->getRepository(\App\Entity\Core\AbstractInscription::class)->findBy(['trainee' => $trainee]);

            // Test dates des sessions des inscriptions existantes
            foreach ($inscriptionsTrainee as $insc) {
                if (($insc->getSession()->getDatebegin() > $datesInsc['end']) || ($insc->getSession()->getDateend() < $datesInsc['begin'])) {
                    // pas de chevauchement possible
                }else {
                    // chevauchement possible

                    // Test statut de l'inscription pour eliminer les avis défavorables, session annulée, ...
                    if ($insc->getInscriptionstatus()->getStatus() != 3) {
                        $libelleinsc = $insc->getSession()->getName();
                        $this->addFlash('error', 'Attention : les dates de cette session peuvent chevaucher une session pour laquelle vous avez déjà réalisé une inscription !');
                    }
                }
            }
        }
        $inscription->setInscriptionstatus(
            $doctrine->getRepository(\App\Entity\Term\Inscriptionstatus::class)->findOneBy(
                ['machinename' => 'waiting']
            )
        );

        $publicType = $trainee->getPublictype();
        $publicRestrict = $training->getPublictypesrestrict();
        $flagInsc = 0;
        if (sizeof($publicRestrict)) {
            foreach ($publicRestrict as $public) {
                if ($publicType == $public) {
                    $flagInsc = 1;
                }
            }
        } else {
            $flagInsc = 1;
        }

        // Test responsable hiérarchique si biatss
        $EmailSup = $trainee->getEmailsup();
        if (($EmailSup == null) && ($publicType == null) || (($EmailSup == null) && ($publicType->getId() == 1))) {
            // Message pour indiquer qu'il faut renseigner le supéieur hiérarchique
            $flagInsc = 2;
        }

        $form = $this->createForm(InscriptionType::class, $inscription);
        if ($flagInsc==1) {
            // Ajout affichage supérieur hiérarchique s'il existe
            if (($trainee->getFirstnamesup() !== null) && ($trainee->getLastnamesup())) {
                $sup = $trainee->getFirstnamesup() . " " . $trainee->getLastnamesup();
                $this->addFlash('warning', 'Le supérieur hiérarchique que vous avez renseigné est ' . $sup . ' dont l\'email est '. $trainee->getEmailsup() . '. Si ce n\'est pas la bonne personne, merci de mettre à jour la donnée dans le menu "Mon compte", onglet "Mon profil".');
            }

            if ($request->getMethod() === 'POST') {
                $form->handleRequest($request);
                if (($form->isSubmitted())&&($form->isValid())) {
                    $inscription->setCreatedat(new \DateTime('now'));
                    $inscription->setUpdatedat(new \DateTime('now'));
                    $em = $doctrine->getManager();
                    $em->persist($inscription);
                    $em->flush();
                    $this->addFlash('success', 'Votre inscription a bien été enregistrée.');

                    $id = $inscription->getId();
                    // Lien vers la page d'autorisation
                    $lien = "https://" . $this->getParameter('front_url') . "/account/registration/" . $id . "/valid";


//                    if ($form['authorization']->getData() == TRUE) {
                    // si on a bien un responsable renseigné
                    if (null !== $inscription->getTrainee()->getEmailsup()) {
                        // Recuperation des templates emails dans le registre des vocabulaires
                        $templateTerm = $vocRegistry->getVocabularyById(5);
                        $repo = $em->getRepository($templateTerm::class);
                        /** @var Emailtemplate $template */
                        $templates = $repo->findBy([
                            'name' => "Demande de validation d'inscription",
                            'organization' => $inscription->getSession()->getTraining()->getOrganization()]);
                        if (!$templates || count($templates) === 0) {
                            // Aucun modèle d'email trouvé pour cette organisation : on ajoute juste un message flash
                            $this->addFlash('success', "Votre demande a été enregistrée. Aucun email n'a été envoyé car aucun modèle n'existe pour cette organisation.");
                            return $this->redirectToRoute('front.account.registrations');
                        }
                        $subject1 = $templates[0]->getSubject();
                        $subject = str_replace("[session.formation.nom]", $inscription->getSession()->getTraining()->getName(), (string) $subject1);
                        $subject = str_replace("[session.nom]", $inscription->getSession()->getName(), $subject);
                        $subject = str_replace("[stagiaire.prenom]", $inscription->getTrainee()->getFirstname(), $subject);
                        $subject = str_replace("[stagiaire.nom]", $inscription->getTrainee()->getLastname(), $subject);

                        $body = $templates[0]->getBody();
                        $formathtml = $templates[0]->getPosition();
                        if ($formathtml)
                            $newline = "<br>";
                        else
                            $newline = "\n";

                        $newbody = str_replace("[session.formation.nom]", $inscription->getSession()->getTraining()->getName(), (string) $body);

                        $Texte = "";
                        foreach ($inscription->getSession()->getDates() as $date) {
                            if ($date->getDatebegin() == $date->getDateend()) {
                                $Texte .= $date->getDatebegin()->format('d/m/Y') . "        " . $date->getSchedulemorn() . "        " . $date->getScheduleafter() . "        " . $date->getPlace() . $newline;
                            } else {
                                $Texte .= $date->getDatebegin()->format('d/m/Y') . " au " . $date->getDateend()->format('d/m/Y') . "        " . $date->getSchedulemorn() . "        " . $date->getScheduleafter() . "        " . $date->getPlace() . $newline;
                            }
                        }
                        $newbody = str_replace("[dates]", $Texte, $newbody);
                        $newbody = str_replace("[stagiaire.prenom]", $inscription->getTrainee()->getFirstname(), $newbody);
                        $newbody = str_replace("[stagiaire.nom]", $inscription->getTrainee()->getLastname(), $newbody);
                        $newbody = str_replace("[session.dateDebut]", $inscription->getSession()->getDatebegin()->format('d/m/Y'), $newbody);
                        $newbody = str_replace("[session.dateFin]", $inscription->getSession()->getDateend()->format('d/m/Y'), $newbody);
                        $newbody = str_replace("[lien]", $lien, $newbody);

                        // Envoyer un mail au supérieur hiérarchique
                        /*$body = "Bonjour,\n" .
                            "Une inscription à la session du " . $inscription->getSession()->getDateBegin()->format('d/m/Y') . "\nde la formation intitulée '" . $inscription->getSession()->getTraining()->getName() . "'\n"
                            . "a été réalisée par ".$inscription->getTrainee()->getFullName() .".\n"
                            . "Pour autoriser ". $inscription->getTrainee()->getFullName()  . " à participer à cette formation, merci de valider l'inscription en cliquant sur le lien suivant :". "\n"
                            . "http://www.univ-amu.fr";
                        */
                        $message = (new Email())
                            ->from($inscription->getSession()->getTraining()->getOrganization()->getEmail())
                            ->replyTo($inscription->getSession()->getTraining()->getOrganization()->getEmail())
                            ->to($inscription->getTrainee()->getEmailsup())
                            ->subject($subject);

                        // si Format HTML coché pour ce modèle, sinon format texte
                        if ($templates[0]->getPosition() == 1) {
                            $message->html($newbody);
                        } else
                            $message->text($newbody);

                        $mailer->send($message);

                    }


                    return $this->redirectToRoute(
                        'front.account.checkout', ['inscriptionId' => $inscription->getId()]
                    );
                }
            }



            return $this->render('Front/Public/program/inscription.html.twig', [
                'user' => $trainee,
                'form' => $form->createView(),
                'training' => $training,
                'session' => $session,
                'token' => $token,
                'flag' => $flagInsc,
            ]);
        }


        return $this->render('Front/Public/program/inscription.html.twig', [
            'user' => $trainee,
            'form' => $form->createView(),
            'training' => $training,
            'session' => $session,
            'token' => $token,
            'flag' => $flagInsc,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param null $token
     *
     *
     * @return RedirectResponse
     */
    #[Route(path: '/training/alert/{id}/{sessionId}', name: 'front.program.alert', requirements: ['id' => '\d+', 'sessionId' => '\d+'])]
    public function alert(ManagerRegistry $doctrine, AbstractTraining $training, int $id, Session $sessionId, $token = null): RedirectResponse
    {
        $training = $doctrine->getRepository(AbstractTraining::class)->find($id);
        if (!$training){
            throw new Exception('Training not found');
        }
        $session = $doctrine->getRepository(Session::class)->find($sessionId);
        if (!$session){
            throw new Exception('Session not found');
        }
        // in case shibboleth authentication
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findOneBy(['email' => $user->getCredentials()['mail']]);
        $trainee = $arTrainee;

        $alert = $doctrine->getManager()->getRepository(\App\Entity\Back\Alert::class)->findOneBy(['trainee' => $trainee, 'session'=> $session]);

        if ($alert) {
            $this->addFlash('warning', "Vous êtes déjà inscrit à l'alerte d'ouverture de la session.");
            return $this->redirectToRoute('front.account.registrations');
            //throw new ForbiddenOverwriteException('An inscription has already been found');
        }
        if (!$alert) {
            $alert = new Alert();
            $alert->setTrainee($trainee);
            $alert->setSession($session);
            $now = new \DateTime();
            $alert->setCreatedAt($now);

            $em = $doctrine->getManager();
            $em->persist($alert);
            $em->flush();
            $this->addFlash('success', 'Votre alerte a bien été enregistrée.');
        }

        return $this->redirectToRoute('front.program.training', ['id' => $training->getId(), 'sessionId' => $session->getId(), 'token' => $token]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param null $token
     *
     *
     * @return RedirectResponse
     */
    #[Route(path: '/training/alertremove/{id}/{sessionId}', name: 'front.program.alertremove', requirements: ['id' => '\d+', 'sessionId' => '\d+'])]
    public function alertRemove(ManagerRegistry $doctrine, AbstractTraining $training, Session $sessionId, int $id, $token = null): RedirectResponse
    {
        $session = $doctrine->getRepository(Session::class)->find($sessionId);
        if (!$session){
            throw new Exception('Session not found');
        }
        $training = $doctrine->getRepository(AbstractTraining::class)->find($id);
        if (!$training){
            throw new Exception('Training not found');
        }
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findOneBy(['email' => $user->getCredentials()['mail']]);
        $trainee = $arTrainee;

        $alert = $doctrine->getManager()->getRepository(\App\Entity\Back\Alert::class)->findOneBy(['trainee' => $trainee, 'session'=> $session]);
        if (!$alert) {
            $this->addFlash('warning', "Vous ne pouvez pas vous désinscrire de l'alerte.");
            return $this->redirectToRoute('front.account.registrations');
            //throw new ForbiddenOverwriteException('An inscription has already been found');
        }
        if ($alert) {
            // Suppression de l'alerte
            $em = $doctrine->getManager();
            $em->remove($alert);
            $em->flush();
        }

        $this->addFlash('success', 'Vous vous êtes bien désinscrit de l\'alerte.');

        return $this->redirectToRoute('front.program.training', ['id' => $training->getId(), 'sessionId' => $session->getId(), 'token' => $token]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route(path: '/myprogram', name: 'front.program.myprogram')]
    public function myProgram(Request $request, ManagerRegistry $doctrine, SessionRepository $sessionRepository): \Symfony\Component\HttpFoundation\Response
    {
        $codes = [];
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findOneBy(['email' => $user->getCredentials()['mail']]);
        if (!$arTrainee) {
            // si compte stagiaire n'existe pas, on renvoie vers la création de compte
            $url = $this->generateUrl('front.account.register');
            return new RedirectResponse($url);
        }

        $etablissement = $arTrainee->getInstitution()->getName();
        if ($etablissement == "Extérieur") {
            // si le compte stagiaire existe avec l'établissement 'Extérieur', on renvoie vers la mise à jour de la fiche stagiaire
            $url = $this->generateUrl('front.account.register');
            return new RedirectResponse($url);
        }

        // Recup param pour l'activation du multi établissement
        $multiEtab = $this->isMultiEtab($arTrainee);

        // Recupération des centres de mon établissement
        $organizations = $doctrine->getRepository(\App\Entity\Back\Organization::class)->findBy(['institution' => $arTrainee->getInstitution()]);
        foreach ($organizations as $organization) {
            $codes[] = $organization->getCode();
        }

        $search = $this->createProgramQuery($sessionRepository, $codes);
        $sessions = $search["items"];

        // creation entites pour recuperer les alertes
        $alerts = new MultipleAlert();
        foreach ($sessions as $session){
            if ($session->getSessiontype() == "A venir") {
                $alert = new SingleAlert();

                $sessionExiste = $doctrine->getManager()->getRepository(\App\Entity\Back\Session::class)->findOneBy(['id' => $session->getId()]);
                // on regarde s'il existe déjà une alerte
                $alertExiste = $doctrine->getManager()->getRepository(\App\Entity\Back\Alert::class)->findOneBy(['trainee' => $arTrainee, 'session'=> $sessionExiste]);
                if ($alertExiste) {
                    // si l'alerte existe, on coche la case de présence
                    $alert->setAlert(true);
                } else {
                    $alert->setAlert(false);
                }

                $alert->setSessionId($session->getId());
                $alert->setTraineeId($arTrainee->getId());
                $alerts->getAlerts()->add($alert);
            }
        }

        // creation du formulaire d'alertes
        $form = $this->createForm(ProgramAlertType::class, $alerts);
        $form->handleRequest($request);

        if (($form->isSubmitted()) && ($form->isValid())) {
            $arrAlerts = $alerts->getAlerts();
            $em = $doctrine->getManager();
            foreach ($arrAlerts as $alert){
                // On verifie si la session et l'alerte existent déjà
                $sessionExiste = $doctrine->getManager()->getRepository(\App\Entity\Back\Session::class)->findOneBy(['id' => $alert->getSessionId()]);

                $alertExiste = $doctrine->getManager()->getRepository(\App\Entity\Back\Alert::class)->findOneBy(['trainee' => $arTrainee, 'session'=> $sessionExiste]);

                // Si la case est cochée
                if ($alert->getAlert() == true) {
                    // Si l'alerte existe déjà, on ne touche à rien, sinon, on la crée
                    if (!$alertExiste) {
                        $alertNew = new Alert();
                        $alertNew->setTrainee($arTrainee);
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

            $this->addFlash('success', 'Vos modifications ont bien été enregistrées.');}

        return $this->render('Front/Public/myprogram.html.twig', [
            'user' => $arTrainee,
            'search' => $search,
            'img' => '',
            'form' => $form->createView(),
            'multiEtab' => $multiEtab
        ]);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route(path: '/allprogram', name: 'front.program.allprogram')]
    public function allProgram(Request $request, ManagerRegistry $doctrine, SessionRepository $sessionRepository): \Symfony\Component\HttpFoundation\Response
    {
        // Recuperation info du user authentifié
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findOneBy(['email' => $user->getCredentials()['mail']]);

        // Recup allProgram = toutes les formations des centres et établissements liés
        // Récupération des centres de l'établissement du stagiaire
        $organizations = $doctrine->getRepository(\App\Entity\Back\Organization::class)->findBy(['institution' => $arTrainee->getInstitution()]);
        $codes = [];
        foreach ($organizations as $centre) {
            $codes[] = $centre->getCode();
        }
        // Récupération des établissements liés
        $otherEtabs = $arTrainee->getInstitution()->getVisuinstitutions();
        if ($otherEtabs != null) {
            // Récupération des centres pour chaque établissement
            foreach ($otherEtabs as $otherEtab) {
                $otherOrgs = $doctrine->getRepository(\App\Entity\Back\Organization::class)->findBy(['institution' => $otherEtab]);
                foreach ($otherOrgs as $centre) {
                    $codes[] = $centre->getCode();
                }
            }
        }

        $search = $this->createProgramQuery($sessionRepository, $codes);
        $sessions = $search["items"];

        // creation entites pour recuperer les alertes
        $alerts = new MultipleAlert();
        foreach ($sessions as $session){
            if ($session->getSessiontype() == "A venir") {
                $alert = new SingleAlert();

                $sessionExiste = $doctrine->getManager()->getRepository(\App\Entity\Back\Session::class)->findOneBy(['id' => $session->getId()]);
                // on regarde s'il existe déjà une alerte
                $alertExiste = $doctrine->getManager()->getRepository(\App\Entity\Back\Alert::class)->findOneBy(['trainee' => $arTrainee, 'session'=> $sessionExiste]);
                if ($alertExiste) {
                    // si l'alerte existe, on coche la case de présence
                    $alert->setAlert(true);
                } else {
                    $alert->setAlert(false);
                }

                $alert->setSessionId($session->getId());
                $alert->setTraineeId($arTrainee->getId());
                $alerts->getAlerts()->add($alert);
            }
        }

        // creation du formulaire d'alertes
        $form = $this->createForm(ProgramAlertType::class, $alerts);
        $form->handleRequest($request);

        if (($form->isSubmitted()) && ($form->isValid())) {
            $arrAlerts = $alerts->getAlerts();
            $em = $doctrine->getManager();
            foreach ($arrAlerts as $alert){
                // On verifie si la session et l'alerte existent déjà
                $sessionExiste = $doctrine->getManager()->getRepository(\App\Entity\Back\Session::class)->findOneBy(['id' => $alert->getSessionId()]);

                $alertExiste = $doctrine->getManager()->getRepository(\App\Entity\Back\Alert::class)->findOneBy(['trainee' => $arTrainee, 'session'=> $sessionExiste]);

                // Si la case est cochée
                if ($alert->getAlert() == true) {
                    // Si l'alerte existe déjà, on ne touche à rien, sinon, on la crée
                    if (!$alertExiste) {
                        $alertNew = new Alert();
                        $alertNew->setTrainee($arTrainee[0]);
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

            $this->addFlash('success', 'Vos modifications ont bien été enregistrées.');
        }

        return $this->render('Front/Public/allprogram.html.twig', ['user' => $arTrainee, 'search' => $search, 'img' => '', 'form' => $form->createView()]);
    }

    /**
     * @param null centreCode
     * @param null theme
     * @param null texte
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route(path: '/searchalerts/{centreCode}/{theme}/{texte}', name: 'front.program.searchalerts')]
    public function searchalerts(Request $request, ManagerRegistry $doctrine, SessionRepository $sessionRepository, $centreCode=null, $theme=null, $texte=null): \Symfony\Component\HttpFoundation\Response
    {
        $organizations = [];
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findOneBy(['email' => $user->getCredentials()['mail']]);

        // Recup param pour l'activation du multi établissement
        $multiEtab = $this->isMultiEtab($arTrainee);

        if ($centreCode=="tous") {
            $centreCodes = [];
            // Recup allProgram = toutes les formations des centres et établissements liés
            // Récupération des centres de l'établissement du stagiaire
            $organizations = $doctrine->getRepository(\App\Entity\Back\Organization::class)->findBy(['institution' => $arTrainee->getInstitution()]);
            foreach ($organizations as $centre) {
                $centreCodes[] = $centre->getCode();
            }

            // Récupération des établissements liés
            $otherEtabs = $arTrainee->getInstitution()->getVisuinstitutions();
            if ($otherEtabs != null) {
                // Récupération des centres pour chaque établissement
                foreach ($otherEtabs as $otherEtab) {
                    $otherOrgs = $doctrine->getRepository(\App\Entity\Back\Organization::class)->findBy(['institution' => $otherEtab]);
                    foreach ($otherOrgs as $centre) {
                        $organizations[] = $centre;
                        $centreCodes[] = $centre->getCode();
                    }
                }
            }
        } else {
            $centreCodes = $centreCode;
            $organizations[0] = $doctrine->getRepository(\App\Entity\Back\Organization::class)->findBy(['code' => $centreCodes]);
        }

        if ($theme=="tous") {
            $themeName = [];
            // recuperation theme des centres associés
            foreach($organizations as $org) {
                $themes = $doctrine->getRepository(Theme::class)->findBy(['organization' => $org]);
                foreach ($themes as $the) {
                    $themeName[] = $the->getName();
                }
            }
            // themes sans centre (org -> null)
            $themesNull = $doctrine->getRepository(Theme::class)->findBy(['organization' => null]);
            foreach ($themesNull as $theNull) {
                $themeName[] = $theNull->getName();
            }

        }else
            $themeName = $theme;

        $search = $this->createProgramQuerySearch($sessionRepository, $centreCodes, $themeName, $texte);
        $sessions = $search["items"];

        // creation entites pour recuperer les alertes
        $alerts = new MultipleAlert();
        foreach ($sessions as $session){
            if ($session->getSessiontype() == "A venir") {
                $alert = new SingleAlert();

                $sessionExiste = $doctrine->getManager()->getRepository(\App\Entity\Back\Session::class)->findOneBy(['id' => $session->getId()]);
                // on regarde s'il existe déjà une alerte
                $alertExiste = $doctrine->getManager()->getRepository(\App\Entity\Back\Alert::class)->findOneBy(['trainee' => $arTrainee, 'session'=> $sessionExiste]);
                if ($alertExiste) {
                    // si l'alerte existe, on coche la case de présence
                    $alert->setAlert(true);
                } else {
                    $alert->setAlert(false);
                }

                $alert->setSessionId($session->getId());
                $alert->setTraineeId($arTrainee->getId());
                $alerts->getAlerts()->add($alert);
            }
        }

        // creation du formulaire d'alertes
        $formAlert = $this->createForm(ProgramAlertType::class, $alerts);
        $formAlert->handleRequest($request);

        if(($formAlert->isSubmitted()) && ($formAlert->isValid())) {
            $arrAlerts = $alerts->getAlerts();
            $em = $doctrine->getManager();
            foreach ($arrAlerts as $alert){
                // On verifie si la session et l'alerte existent déjà
                $sessionExiste = $doctrine->getManager()->getRepository(\App\Entity\Back\Session::class)->findOneBy(['id' => $alert->getSessionId()]);

                $alertExiste = $doctrine->getManager()->getRepository(\App\Entity\Back\Alert::class)->findOneBy(['trainee' => $arTrainee, 'session'=> $sessionExiste]);

                // Si la case est cochée
                if ($alert->getAlert() == true) {
                    // Si l'alerte existe déjà, on ne touche à rien, sinon, on la crée
                    if (!$alertExiste) {
                        $alertNew = new Alert();
                        $alertNew->setTrainee($arTrainee);
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

            $this->addFlash('success', 'Vos modifications ont bien été enregistrées.');
        }

        return $this->render('Front/Public/searchResult.html.twig', [
            'search' => $search,
            'form' => $formAlert->createView(),
            'multiEtab' => $multiEtab,
        ]);
    }

    #[Route(path: '/search', name: 'front.program.search')]
    public function search(Request $request, ManagerRegistry $doctrine): \Symfony\Component\HttpFoundation\Response
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findOneBy(['email' => $user->getCredentials()['mail']]);

        // Recup param pour l'activation du multi établissement
        $multiEtab = $this->isMultiEtab($arTrainee);

        /** @var EntityManager $em */
        $em = $doctrine->getManager();
        $theme = $em->getRepository(\App\Entity\Term\Theme::class)->findOneBy(['name' => 'Tous les domaines']);
        // Récupération des centres de l'établissement du stagiaire
        $organizations = $doctrine->getRepository(\App\Entity\Back\Organization::class)->findBy(['institution' => $arTrainee->getInstitution()]);

        // Récupération des établissements liés
        $visuInstitutions = $arTrainee->getInstitution()->getVisuinstitutions();
        // creer le tableau des centres liés aux établissements visibles
        foreach($visuInstitutions as $visuInst) {
            $organizationsVisu = $doctrine->getRepository(\App\Entity\Back\Organization::class)->findBy(['institution' => $visuInst]);
            foreach ($organizationsVisu as $orgVisu) {
                $organizations[] = $orgVisu;
            }
        }

        $defaultData = ['centre' => $organizations[0], 'theme' => $theme, 'texte' => ""];
        $form = $this->createForm(ProgramSearchType::class, $defaultData,
            ['institution' => $arTrainee->getInstitution(), 'organizations' => $organizations]
            );

        $centreCode = '';
        $themeName = '';
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if (($form->isSubmitted()) && ($form->isValid())) {
                $theme = $form['theme']->getData();
                if (!empty($theme)) {
                    $themeName = $theme->getName();
                    if ($themeName == "Tous les domaines") {
                        $themeName = "tous";
                    }
                }
                $organization = $form['centre']->getData();
                if (!empty($organization)) {
                    $centreCode = $organization->getCode();
                }
                $texte = $form['texte']->getData();

                return $this->redirectToRoute('front.program.searchalerts', ['centreCode' => $centreCode, 'theme' => $themeName, 'texte' => $texte]);

            }
        }

        return $this->render('Front/Public/search.html.twig', [
            'user' => $this->getUser(),
            'form' => $form->createView(),
            'multiEtab' => $multiEtab,
        ]);
    }


    /**
     * @param $page
     * @param int $itemPerPage
     * @param $code
     * @return array{total: int, pageSize: int, items: mixed}
     */
    protected function createProgramQuery($sessionRepository, $code = null): array
    {
        $filters = [];
        // Construction filtres : code et date
        $filters["training.organization.name.source"] = $code;

        // Construction date : prochaines sessions (aujourd'hui +5ans)
        $dateB = new \DateTime('now');
        $dateBegin = $dateB->format('d/m/Y');
        $dateB->modify('+ 5 years');
        $dateFin = $dateB->format('d/m/Y');
        $filters["datebegin"] = $dateBegin . " - " . $dateFin;

        // Recherche avec les filtres
        $sessions = $sessionRepository->getSessionsProgram('NO KEYWORDS', $filters);
        $nbSessions  = is_countable($sessions) ? count($sessions) : 0;

        $ret = ['total' => $nbSessions, 'pageSize' => 0, 'items' => $sessions];
        return $ret;
    }

    /**
     * @param $page
     * @param int $itemPerPage
     * @param $code
     * @param $theme
     * @return array{total: int, pageSize: int, items: mixed}
     */
    protected function createProgramQuerySearch($sessionRepository, $code = null, $theme = null, $texte = null): array
    {
        $filters = [];
        $keywords = $texte;

        // Construction filtres : code et date
        $filters["training.organization.name.source"] = $code;

        // Construction date : prochaines sessions (aujourd'hui +5ans)
        $dateB = new \DateTime('now');
        $dateBegin = $dateB->format('d/m/Y');
        $dateB->modify('+ 5 years');
        $dateFin = $dateB->format('d/m/Y');
        $filters["datebegin"] = $dateBegin . " - " . $dateFin;

        // Filtre theme
        $filters["theme.name"] = $theme;

        // Recherche avec les filtres
        $sessions = $sessionRepository->getSessionsProgram($keywords, $filters);
        $nbSessions  = is_countable($sessions) ? count($sessions) : 0;

        $ret = ['total' => $nbSessions, 'pageSize' => 0, 'items' => $sessions];
        return $ret;

    }

    /**
     * @param $trainee
     * @return bool
     */
    protected function isMultiEtab($trainee)
    {
        $multiEtab = false;
        // Récupération des établissements liés
        $otherEtabs = $trainee->getInstitution()->getVisuinstitutions();
        if ((isset($otherEtabs[0])) && ($otherEtabs[0] != null)) {
            // S'il y a des établissements liés, on active la conf multi-établissements
            $multiEtab = true;
        }
        return $multiEtab;
    }

}

<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/15/16
 * Time: 11:00 AM
 */

namespace App\Controller\Front;

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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * @Route("/program")
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
class ProgramController extends AbstractController
{
    /**
     * @Route("/contact", name="front.pg.contact")
     * @Template("Front/Public/contact.html.twig")
     */
    public function contactAction(Request $request, ManagerRegistry $doctrine)
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];
        return array('contact_mail' => $this->getParameter('contact_mail'), 'user' => $trainee);
    }

    /**
     * @Route("/faq", name="front.pg.faq")
     * @Template("Front/Public/faq.html.twig")
     */
    public function faqAction(Request $request, ManagerRegistry $doctrine)
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];
        return array('contact_mail' => $this->getParameter('contact_mail'), 'front_url' => $this->getParameter('front_url'), 'user' => $trainee);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param App\Entity\Core\AbstractTraining $training
     * @param null $sessionId
     * @param null $token
     *
     * @Route("/training/{id}/{sessionId}/{token}", name="front.program.training", requirements={"id": "\d+", "sessionId": "\d+"})
     * @ParamConverter("training", class="App\Entity\Core\AbstractTraining", options={"id" = "id"})
     * @Template("Front/Public/program/training.html.twig")
     *
     * @return array
     */
    public function trainingAction(Request $request, ManagerRegistry $doctrine, AbstractTraining $training, $sessionId = null, $token = null)
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        $focusSession = null;
        foreach ($training->getSessions() as $session) {
            if ($session->getId() == $sessionId) {
                $focusSession = $session;
                break;
            }
        }

        $now = new \DateTime();
        $pastSessions = array();
        $upcomingSessions = array();

        /** @var Session $session */
        foreach ($training->getSessions() as $session) {

            $sesId = $session->getId();
            $inscription = null;

            /** @var EntityManager $em */
            $em = $doctrine->getManager();
            $inscription = $em->getRepository('App\Entity\Core\AbstractInscription')->createQueryBuilder('inscription')
                ->leftJoin('App\Entity\Core\AbstractSession', 'session', 'WITH', 'inscription.session = session.id')
                ->leftJoin('App\Entity\Core\AbstractTrainee', 'trainee', 'WITH', 'inscription.trainee = trainee.id')
                ->where('session.id = :sessionId')
                ->andWhere('trainee.id = :traineeId')
                ->setParameter('sessionId', $sesId)
                ->setParameter('traineeId', $trainee->getId())
                ->getQuery()->execute();

            $alert = $em->getRepository('App\Entity\Back\Alert')->createQueryBuilder('alert')
                ->leftJoin('App\Entity\Core\AbstractSession', 'session', 'WITH', 'alert.session = session.id')
                ->leftJoin('App\Entity\Core\AbstractTrainee', 'trainee', 'WITH', 'alert.trainee = trainee.id')
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
            if ($session->getRegistration() === $session::REGISTRATION_PRIVATE ) {
                $session->availablePrivateSession = true;
            }
            else {
                $session->availablePrivateSession = false;
            }

        }

        // Affichage d'un flag si le stage en public désigné
        if ($training->getDesignatedpublic())
            $this->get('session')->getFlashBag()->add('warning', 'Ce stage est réservé à un public désigné. Vous devez faire partie de la liste des personnes autorisées à s\'inscrire.');

        return array(
            'user' => $trainee,
            'training' => $training,
            'session' => $focusSession,
            'upcomingSessions' => $upcomingSessions,
            'pastSessions' => $pastSessions,
            'token' => $token
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Entity\Core\AbstractTraining $training
     * @param \App\Entity\Back\Session $session
     * @param null $token
     *
     * @Route("/training/inscription/{id}/{sessionId}/{token}", name="front.program.inscription", requirements={"id": "\d+", "sessionId": "\d+"})
     * @ParamConverter("training", class="App\Entity\Core\AbstractTraining", options={"id" = "id"})
     * @ParamConverter("session", class="App\Entity\Back\Session", options={"id" = "sessionId"})
     * @Template("Front/Public/program/inscription.html.twig")
     *
     * @return array
     */
    public function inscriptionAction(Request $request, ManagerRegistry $doctrine, VocabularyRegistry $vocRegistry, MailerInterface $mailer, AbstractTraining $training, Session $session, $token = null)
    {
        // in case shibboleth authentication
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        $inscription = $doctrine->getManager()->getRepository('App\Entity\Core\AbstractInscription')->findOneBy(array(
            'trainee' => $trainee,
            'session'=> $session
        ));
        if ($inscription) {
            $this->get('session')->getFlashBag()->add('warning', "Vous êtes déjà inscrit à cette session.");
            return $this->redirectToRoute('front.account.registrations');
            //throw new ForbiddenOverwriteException('An inscription has already been found');
        }
        if (!$inscription) {
            $inscription = new Inscription();
            $inscription->setTrainee($trainee);
            $inscription->setSession($session);
        }
        $inscription->setInscriptionstatus(
            $doctrine->getRepository('App\Entity\Term\Inscriptionstatus')->findOneBy(
                array('machinename' => 'waiting')
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

        if ($flagInsc==1) {
            $form = $this->createForm(InscriptionType::class, $inscription);
            if ($request->getMethod() === 'POST') {
                $form->handleRequest($request);
                if (($form->isSubmitted())&&($form->isValid())) {
                    $inscription->setCreatedat(new \DateTime('now'));
                    $inscription->setUpdatedat(new \DateTime('now'));
                    $em = $doctrine->getManager();
                    $em->persist($inscription);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('success', 'Votre inscription a bien été enregistrée.');

                    $id = $inscription->getId();
                    // Lien vers la page d'autorisation
                    $lien = "https://" . $this->getParameter('front_url') . "/account/registration/" . $id . "/valid";


//                    if ($form['authorization']->getData() == TRUE) {
                    // si on a bien un responsable renseigné
                    if (null !== $inscription->getTrainee()->getEmailsup()) {
                        // Recuperation des templates emails dans le registre des vocabulaires
                        $templateTerm = $vocRegistry->getVocabularyById(5);
                        $repo = $em->getRepository(get_class($templateTerm));
                        /** @var Emailtemplate $template */
                        $templates = $repo->findBy(array('name' => "Demande de validation d'inscription", 'organization' => $inscription->getSession()->getTraining()->getOrganization()));
                        $subject = $templates[0]->getSubject();
                        $body = $templates[0]->getBody();
                        $newbody = str_replace("[session.formation.nom]", $inscription->getSession()->getTraining()->getName(), $body);

                        $Texte = "";
                        foreach ($inscription->getSession()->getDates() as $date) {
                            if ($date->getDatebegin() == $date->getDateend()) {
                                $Texte .= $date->getDatebegin()->format('d/m/Y') . "        " . $date->getSchedulemorn() . "        " . $date->getScheduleafter() . "        " . $date->getPlace() . "\n";
                            } else {
                                $Texte .= $date->getDatebegin()->format('d/m/Y') . " au " . $date->getDateend()->format('d/m/Y') . "        " . $date->getSchedulemorn() . "        " . $date->getScheduleafter() . "        " . $date->getPlace() . "\n";
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
                        'front.account.checkout', array(
                            'inscriptionId' => $inscription->getId())
                    );
                }

                $sup = $inscription->getTrainee()->getFirstnamesup() . " " . $inscription->getTrainee()->getLastnamesup();
                $this->get('session')->getFlashBag()->add('warning', 'Le supérieur hiérarchique que vous avez renseigné est ' . $sup . '. Si ce n\'est pas la bonne personne, merci de mettre à jour la donnée dans le menu "Mon compte", onglet "Mon profil".');
            }


            return array(
                'user' => $trainee,
                'form' => $form->createView(),
                'training' => $training,
                'session' => $session,
                'token' => $token,
                'flag' => $flagInsc
            );
        }
        else {
            //$this->get('session')->getFlashBag()->add('error', "Vous ne pouvez pas vous inscrire à cette session car vous ne faites pas partie des publics cibles autorisés à s'inscrire.");
            return array(
                'user' => $trainee,
                'training' => $training,
                'session' => $session,
                'flag' => $flagInsc
            );
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Entity\Core\AbstractTraining $training
     * @param \App\Entity\Back\Session $session
     * @param null $token
     *
     * @Route("/training/alert/{id}/{sessionId}", name="front.program.alert", requirements={"id": "\d+", "sessionId": "\d+"})
     * @ParamConverter("training", class="App\Entity\Core\AbstractTraining", options={"id" = "id"})
     * @ParamConverter("session", class="App\Entity\Back\Session", options={"id" = "sessionId"})
     * @Template("Front/Public/program/inscription.html.twig")
     *
     * @return array
     */
    public function alertAction(Request $request, ManagerRegistry $doctrine, AbstractTraining $training, Session $session, $token = null)
    {
        // in case shibboleth authentication
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        $alert = $doctrine->getManager()->getRepository('App\Entity\Back\Alert')->findOneBy(array(
            'trainee' => $trainee,
            'session'=> $session
        ));

        if ($alert) {
            $this->get('session')->getFlashBag()->add('warning', "Vous êtes déjà inscrit à l'alerte d'ouverture de la session.");
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
            $this->get('session')->getFlashBag()->add('success', 'Votre alerte a bien été enregistrée.');
        }

        return $this->redirectToRoute('front.program.training', array('id' => $training->getId(), 'sessionId' => $session->getId(), 'token' => $token));
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Entity\Core\AbstractTraining $training
     * @param \App\Entity\Back\Session  $session
     * @param null $token
     *
     * @Route("/training/alertremove/{id}/{sessionId}", name="front.program.alertremove", requirements={"id": "\d+", "sessionId": "\d+"})
     * @ParamConverter("training", class="App\Entity\Core\AbstractTraining", options={"id" = "id"})
     * @ParamConverter("session", class="App\Entity\Back\Session", options={"id" = "sessionId"})
     * @Template("Front/Public/program/inscription.html.twig")
     *
     * @return array
     */
    public function alertRemoveAction(Request $request,ManagerRegistry $doctrine, AbstractTraining $training, Session $session, $token = null)
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        $alert = $doctrine->getManager()->getRepository('App\Entity\Back\Alert')->findOneBy(array(
            'trainee' => $trainee,
            'session'=> $session
        ));
        if (!$alert) {
            $this->get('session')->getFlashBag()->add('warning', "Vous ne pouvez pas vous désinscrire de l'alerte.");
            return $this->redirectToRoute('front.account.registrations');
            //throw new ForbiddenOverwriteException('An inscription has already been found');
        }
        if ($alert) {
            // Suppression de l'alerte
            $em = $doctrine->getManager();
            $em->remove($alert);
            $em->flush();
        }

        $this->get('session')->getFlashBag()->add('success', 'Vous vous êtes bien désinscrit de l\'alerte.');

        return $this->redirectToRoute('front.program.training', array('id' => $training->getId(), 'sessionId' => $session->getId(), 'token' => $token));
    }

    /**
     * @Route("/myprogram", name="front.program.myprogram")
     * @Template("Front/Public/myprogram.html.twig")
     */
    public function myProgramAction(Request $request, ManagerRegistry $doctrine, SessionRepository $sessionRepository)
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $etablissement = $arTrainee[0]->getInstitution()->getName();

        // Recup param pour l'activation du multi établissement
        $multiEtab = $this->isMultiEtab($arTrainee[0]);

        // Recupération des centres de mon établissement
        $organizations = $doctrine->getRepository('App\Entity\Back\Organization')->findBy(array('institution' => $arTrainee[0]->getInstitution()));
        foreach ($organizations as $organization) {
            $codes[] = $organization->getCode();
        }

        $search = $this->createProgramQuery($codes, $sessionRepository);
        $sessions = $search["items"];

        // creation entites pour recuperer les alertes
        $alerts = new MultipleAlert();
        foreach ($sessions as $session){
            if ($session->getSessiontype() == "A venir") {
                $alert = new SingleAlert();

                $sessionExiste = $doctrine->getManager()->getRepository('App\Entity\Back\Session')->findOneBy(array(
                    'id' => $session->getId()
                ));
                // on regarde s'il existe déjà une alerte
                $alertExiste = $doctrine->getManager()->getRepository('App\Entity\Back\Alert')->findOneBy(array(
                    'trainee' => $arTrainee[0],
                    'session'=> $sessionExiste
                ));
                if ($alertExiste) {
                    // si l'alerte existe, on coche la case de présence
                    $alert->setAlert(true);
                } else {
                    $alert->setAlert(false);
                }

                $alert->setSessionId($session->getId());
                $alert->setTraineeId($arTrainee[0]->getId());
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
                $sessionExiste = $doctrine->getManager()->getRepository('App\Entity\Back\Session')->findOneBy(array(
                    'id' => $alert->getSessionId()
                ));

                $alertExiste = $doctrine->getManager()->getRepository('App\Entity\Back\Alert')->findOneBy(array(
                    'trainee' => $arTrainee[0],
                    'session'=> $sessionExiste
                ));

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

            $this->get('session')->getFlashBag()->add('success', 'Vos modifications ont bien été enregistrées.');
        }

        return array('user' => $arTrainee[0], 'search' => $search, 'img' => '', 'form' => $form->createView(),'multiEtab' => $multiEtab);
    }

    /**
     * @Route("/allprogram", name="front.program.allprogram")
     * @Template("Front/Public/allprogram.html.twig")
     */
    public function allProgramAction(Request $request, ManagerRegistry $doctrine, SessionRepository $sessionRepository)
    {
        // Recuperation info du user authentifié
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);

        // Recup allProgram = toutes les formations des centres et établissements liés
        // Récupération des centres de l'établissement du stagiaire
        $organizations = $doctrine->getRepository('App\Entity\Back\Organization')->findBy(array('institution' => $arTrainee[0]->getInstitution()));
        $codes = array();
        foreach ($organizations as $centre) {
            $codes[] = $centre->getName();
        }
        // Récupération des établissements liés
        $otherEtabs = $arTrainee[0]->getInstitution()->getVisuinstitutions();
        if ($otherEtabs != null) {
            // Récupération des centres pour chaque établissement
            foreach ($otherEtabs as $otherEtab) {
                $otherOrgs = $doctrine->getRepository('App\Entity\Back\Organization')->findBy(array('institution' => $otherEtab));
                foreach ($otherOrgs as $centre) {
                    $codes[] = $centre->getName();
                }
            }
        }

        $search = $this->createProgramQuery($codes, $sessionRepository);
        $sessions = $search["items"];

        // creation entites pour recuperer les alertes
        $alerts = new MultipleAlert();
        foreach ($sessions as $session){
            if ($session->getSessiontype() == "A venir") {
                $alert = new SingleAlert();

                $sessionExiste = $doctrine->getManager()->getRepository('App\Entity\Back\Session')->findOneBy(array(
                    'id' => $session->getId()
                ));
                // on regarde s'il existe déjà une alerte
                $alertExiste = $doctrine->getManager()->getRepository('App\Entity\Back\Alert')->findOneBy(array(
                    'trainee' => $arTrainee[0],
                    'session'=> $sessionExiste
                ));
                if ($alertExiste) {
                    // si l'alerte existe, on coche la case de présence
                    $alert->setAlert(true);
                } else {
                    $alert->setAlert(false);
                }

                $alert->setSessionId($session->getId());
                $alert->setTraineeId($arTrainee[0]);
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
                $sessionExiste = $doctrine->getManager()->getRepository('App\Entity\Back\Session')->findOneBy(array(
                    'id' => $alert->getSessionId()
                ));

                $alertExiste = $doctrine->getManager()->getRepository('App\Entity\Back\Alert')->findOneBy(array(
                    'trainee' => $arTrainee[0],
                    'session'=> $sessionExiste
                ));

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

            $this->get('session')->getFlashBag()->add('success', 'Vos modifications ont bien été enregistrées.');
        }

        return array('user' => $arTrainee, 'search' => $search, 'img' => '', 'form' => $form->createView());
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param null centreCode
     * @param null theme
     * @param null texte
     * @Route("/searchalerts/{centreCode}/{theme}/{texte}", name="front.program.searchalerts")
     * @Template("Front/Public/searchResult.html.twig")
     */
    public function searchalertsAction(Request $request, ManagerRegistry $doctrine, SessionRepository $sessionRepository, $centreCode=null, $theme=null, $texte=null)
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);

        // Recup param pour l'activation du multi établissement
        $multiEtab = $this->isMultiEtab($arTrainee[0]);

        if ($centreCode=="tous") {
            $centreCodes = array();
            // Recup allProgram = toutes les formations des centres et établissements liés
            // Récupération des centres de l'établissement du stagiaire
            $organizations = $doctrine->getRepository('App\Entity\Back\Organization')->findBy(array('institution' => $arTrainee[0]->getInstitution()));
            foreach ($organizations as $centre) {
                $centreCodes[] = $centre->getCode();
            }

            // Récupération des établissements liés
            $otherEtabs = $arTrainee[0]->getInstitution()->getVisuinstitutions();
            if ($otherEtabs != null) {
                // Récupération des centres pour chaque établissement
                foreach ($otherEtabs as $otherEtab) {
                    $otherOrgs = $doctrine->getRepository('App\Entity\Back\Organization')->findBy(array('institution' => $otherEtab));
                    foreach ($otherOrgs as $centre) {
                        $organizations[] = $centre;
                        $centreCodes[] = $centre->getCode();
                    }
                }
            }
        } else {
            $centreCodes = $centreCode;
            $organizations[0] = $doctrine->getRepository('App\Entity\Back\Organization')->findBy(array('code' => $centreCodes));
        }

        if ($theme=="tous") {
            $themeName = array();
            // recuperation theme des centres associés
            foreach($organizations as $org) {
                $themes = $doctrine->getRepository(Theme::class)->findBy(array('organization' => $org));
                foreach ($themes as $the) {
                    $themeName[] = $the->getName();
                }
            }
            // themes sans centre (org -> null)
            $themesNull = $doctrine->getRepository(Theme::class)->findBy(array('organization' => null));
            foreach ($themesNull as $theNull) {
                $themeName[] = $theNull->getName();
            }

        }else
            $themeName = $theme;

        $search = $this->createProgramQuerySearch($centreCodes, $themeName, $texte, $sessionRepository);
        $sessions = $search["items"];

        // creation entites pour recuperer les alertes
        $alerts = new MultipleAlert();
        foreach ($sessions as $session){
            if ($session->getSessiontype() == "A venir") {
                $alert = new SingleAlert();

                $sessionExiste = $doctrine->getManager()->getRepository('App\Entity\Back\Session')->findOneBy(array(
                    'id' => $session->getId()
                ));
                // on regarde s'il existe déjà une alerte
                $alertExiste = $doctrine->getManager()->getRepository('App\Entity\Back\Alert')->findOneBy(array(
                    'trainee' => $arTrainee[0],
                    'session'=> $sessionExiste
                ));
                if ($alertExiste) {
                    // si l'alerte existe, on coche la case de présence
                    $alert->setAlert(true);
                } else {
                    $alert->setAlert(false);
                }

                $alert->setSessionId($session->getId());
                $alert->setTraineeId($arTrainee[0]->getId());
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
                $sessionExiste = $doctrine->getManager()->getRepository('App\Entity\Back\Session')->findOneBy(array(
                    'id' => $alert->getSessionId()
                ));

                $alertExiste = $doctrine->getManager()->getRepository('App\Entity\Back\Alert')->findOneBy(array(
                    'trainee' => $arTrainee[0],
                    'session'=> $sessionExiste
                ));

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

            $this->get('session')->getFlashBag()->add('success', 'Vos modifications ont bien été enregistrées.');
        }

        return array('search' => $search, 'form' => $formAlert->createView(), 'multiEtab' => $multiEtab);
    }

    /**
     * @Route("/search", name="front.program.search")
     * @Template("Front/Public/search.html.twig")
     */
    public function searchAction(Request $request, ManagerRegistry $doctrine)
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);

        // Recup param pour l'activation du multi établissement
        $multiEtab = $this->isMultiEtab($arTrainee[0]);

        /** @var EntityManager $em */
        $em = $doctrine->getManager();
        $theme = $em->getRepository('App\Entity\Term\Theme')->findOneBy(array('name' => 'Tous les domaines' ));
        // Récupération des centres de l'établissement du stagiaire
        $organizations = $doctrine->getRepository('App\Entity\Back\Organization')->findBy(array('institution' => $arTrainee[0]->getInstitution()));

        // Récupération des établissements liés
        $visuInstitutions = $arTrainee[0]->getInstitution()->getVisuinstitutions();
        // creer le tableau des centres liés aux établissements visibles
        foreach($visuInstitutions as $visuInst) {
            $organizationsVisu = $doctrine->getRepository('App\Entity\Back\Organization')->findBy(array('institution' => $visuInst));
            foreach ($organizationsVisu as $orgVisu) {
                $organizations[] = $orgVisu;
            }
        }

        $defaultData = array('centre' => $organizations[0], 'theme' => $theme, 'texte' => "");
        $form = $this->createForm(ProgramSearchType::class, $defaultData,
            array(
                'institution' => $arTrainee[0]->getInstitution(),
                'organizations' => $organizations)
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

                return $this->redirectToRoute('front.program.searchalerts', array('centreCode' => $centreCode, 'theme' => $themeName, 'texte' => $texte));

            }
        }

        return array('user' => $this->getUser(), 'form' => $form->createView(), 'multiEtab' => $multiEtab);
    }


    /**
     * @param $page
     * @param int $itemPerPage
     * @param $code
     * @return array
     */
    protected function createProgramQuery($code = null, $sessionRepository)
    {
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
        $nbSessions  = count($sessions);

        $ret = array(
            'total' => $nbSessions,
            'pageSize' => 0,
            'items' => $sessions,
        );
        return $ret;
    }

    /**
     * @param $page
     * @param int $itemPerPage
     * @param $code
     * @param $theme
     * @return array
     */
    protected function createProgramQuerySearch($code = null, $theme = null, $texte = null, $sessionRepository)
    {
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
        $nbSessions  = count($sessions);

        $ret = array(
            'total' => $nbSessions,
            'pageSize' => 0,
            'items' => $sessions,
        );
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
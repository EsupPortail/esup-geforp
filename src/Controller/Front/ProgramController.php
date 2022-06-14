<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/15/16
 * Time: 11:00 AM
 */

namespace App\Controller\Front;

use App\Entity\Trainee;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Organization;
use App\Entity\Alert;
use App\Entity\MultipleAlert;
use App\Entity\SingleAlert;
use App\Form\Type\ProgramAlertType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/account")
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
class ProgramController extends AbstractController
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Sygefor\Bundle\TrainingBundle\Entity\Training\AbstractTraining $training
     * @param null $sessionId
     * @param null $token
     *
     * @Route("/training/{id}/{sessionId}/{token}", name="front.public.training", requirements={"id": "\d+", "sessionId": "\d+"})
     * @ParamConverter("training", class="SygeforTrainingBundle:Training\AbstractTraining", options={"id" = "id"})
     * @Template("@SygeforFront/Public/program/training.html.twig")
     *
     * @return array
     */
    public function trainingAction(Request $request, ManagerRegistry $doctrine,AbstractTraining $training, $sessionId = null, $token = null)
    {
        $this->apiTrainingController->setContainer($this->container);
        $training = $this->apiTrainingController->trainingAction($training);
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
            if ($this->getUser() instanceof AbstractTrainee) {
                /** @var EntityManager $em */
                $em = $doctrine->getManager();
                $inscription = $em->getRepository('SygeforInscriptionBundle:AbstractInscription')->createQueryBuilder('inscription')
                    ->leftJoin('SygeforTrainingBundle:Session\AbstractSession', 'session', 'WITH', 'inscription.session = session.id')
                    ->leftJoin('SygeforTraineeBundle:AbstractTrainee', 'trainee', 'WITH', 'inscription.trainee = trainee.id')
                    ->where('session.id = :sessionId')
                    ->andWhere('trainee.id = :traineeId')
                    ->setParameter('sessionId', $sesId)
                    ->setParameter('traineeId', $this->getUser()->getId())->getQuery()->execute();

                $alert = $em->getRepository('SygeforMyCompanyBundle:Alert')->createQueryBuilder('alert')
                    ->leftJoin('SygeforTrainingBundle:Session\AbstractSession', 'session', 'WITH', 'alert.session = session.id')
                    ->leftJoin('SygeforTraineeBundle:AbstractTrainee', 'trainee', 'WITH', 'alert.trainee = trainee.id')
                    ->where('session.id = :sessionId')
                    ->andWhere('trainee.id = :traineeId')
                    ->setParameter('sessionId', $sesId)
                    ->setParameter('traineeId', $this->getUser()->getId())->getQuery()->execute();
            }

            $session->isRegistered = !empty($inscription);

            $session->getDateBegin() > $now ? $upcomingSessions[] = $session : $pastSessions[] = $session;
            // Gestion des alertes existantes pour les sessions à venir
            if ($session->getDateBegin() > $now) {
                $session->isAlerted = !empty($alert);
            }
            if ($session->getRegistration() === $session::REGISTRATION_PRIVATE && (!method_exists($session, 'getModule') || !$session->getModule())) {
                $session->availablePrivateSession = true;
            }
            else {
                $session->availablePrivateSession = false;
            }
            if (method_exists($session, 'getModule') && $session->getModule()) {
                $session->moduleToken = md5($session->getTraining()->getType() . $session->getTraining()->getId()) === $token;
            }

        }

        if ($this->getUser() && !$this->getUser()->getIsActive()) {
            $this->get('session')->getFlashBag()->add('warning', "Vous ne pouvez pas vous inscrire à une session tant que votre compte n'a pas
             été validé par un administrateur.");
        }

        // Affichage d'un flag si le stage en public désigné
        if ($training->getDesignatedPublic())
            $this->get('session')->getFlashBag()->add('warning', 'Ce stage est réservé à un public désigné. Vous devez faire partie de la liste des personnes autorisées à s\'inscrire.');

        return array(
            'user' => $this->getUser(),
            'training' => $training,
            'session' => $focusSession,
            'upcomingSessions' => $upcomingSessions,
            'pastSessions' => $pastSessions,
            'token' => $token
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Sygefor\Bundle\TrainingBundle\Entity\Training\AbstractTraining $training
     * @param \Sygefor\Bundle\MyCompanyBundle\Entity\Session $session
     * @param null $token
     *
     * @Route("/training/inscription/{id}/{sessionId}/{token}", name="front.public.inscription", requirements={"id": "\d+", "sessionId": "\d+"})
     * @ParamConverter("training", class="SygeforTrainingBundle:Training\AbstractTraining", options={"id" = "id"})
     * @ParamConverter("session", class="SygeforMyCompanyBundle:Session", options={"id" = "sessionId"})
     * @Template("@SygeforFront/Public/program/inscription.html.twig")
     *
     * @return array
     */
    public function inscriptionAction(Request $request, ManagerRegistry $doctrine, AbstractTraining $training, Session $session, $token = null)
    {
        // in case shibboleth authentication done but user has not registered his account
        if (!is_object($this->getUser())) {
            return $this->redirectToRoute('front.account.register');
        }

        if (!$this->getUser()->getIsActive()) {
            $this->get('session')->getFlashBag()->add('warning', "Vous ne pouvez pas vous inscrire à une session tant que votre compte n'a pas
             été validé par un administrateur.");
            return $this->redirectToRoute('front.public.training', array('id' => $training->getId(), 'sessionId' => $session->getId(), 'token' => $token));
        }

        $this->apiTrainingController->setContainer($this->container);
        $training = $this->apiTrainingController->trainingAction($training);
        if (method_exists($session, 'getModule') && $session->getModule()) {
            $session->moduleToken = md5($session->getTraining()->getType() . $session->getTraining()->getId()) === $token;
        }

        $inscription = $doctrine->getManager()->getRepository('SygeforInscriptionBundle:AbstractInscription')->findOneBy(array(
            'trainee' => $this->getUser(),
            'session'=> $session
        ));
        if ($inscription) {
            $this->get('session')->getFlashBag()->add('warning', "Vous êtes déjà inscrit à cette session.");
            return $this->redirectToRoute('front.account.registrations');
            //throw new ForbiddenOverwriteException('An inscription has already been found');
        }
        if (!$inscription) {
            $inscription = new Inscription();
            $inscription->setTrainee($this->getUser());
            $inscription->setSession($session);
        }
        $inscription->setInscriptionStatus(
            $doctrine->getRepository('SygeforInscriptionBundle:Term\InscriptionStatus')->findOneBy(
                array('machineName' => 'waiting')
            )
        );

        $publicType = $this->getUser()->getPublicType();
        $publicRestrict = $training->getPublicTypesRestrict();
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
        $EmailSup = $this->getUser()->getEmailSup();
        if (($EmailSup == null) && ($publicType == null) || (($EmailSup == null) && ($publicType->getId() == 1))) {
            // Message pour indiquer qu'il faut renseigner le supéieur hiérarchique
            $flagInsc = 2;
        }

        if ($flagInsc==1) {
            $form = $this->createForm(new InscriptionType(), $inscription);
            if ($request->getMethod() === 'POST') {
                $form->handleRequest($request);
                if ($form->isValid()) {
                    $em = $doctrine->getManager();
                    $em->persist($inscription);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('success', 'Votre inscription a bien été enregistrée.');

                    $id = $inscription->getId();
                    // Lien vers la page d'autorisation
                    $lien = $this->container->getParameter('front_url') . "/account/registration/" . $id . "/valid";


//                    if ($form['authorization']->getData() == TRUE) {
                    // si on a bien un responsable renseigné
                    if (count($inscription->getTrainee()->getEmailSup())) {
                        $templateTerm = $this->container->get('sygefor_core.vocabulary_registry')->getVocabularyById('sygefor_trainee.vocabulary_email_template');
                        $repo = $em->getRepository(get_class($templateTerm));
                        /** @var EmailTemplate $template */
                        $templates = $repo->findBy(array('name' => "Demande de validation d'inscription", 'organization' => $inscription->getSession()->getTraining()->getOrganization()));
                        $subject = $templates[0]->getSubject();
                        $body = $templates[0]->getBody();
                        $newbody = str_replace("[session.formation.nom]", $inscription->getSession()->getTraining()->getName(), $body);

                        $Texte = "";
                        foreach ($inscription->getSession()->getDates() as $date) {
                            if ($date->getDateBegin() == $date->getDateEnd()) {
                                $Texte .= $date->getDateBegin()->format('d/m/Y') . "        " . $date->getScheduleMorn() . "        " . $date->getScheduleAfter() . "        " . $date->getPlace() . "\n";
                            } else {
                                $Texte .= $date->getDateBegin()->format('d/m/Y') . " au " . $date->getDateEnd()->format('d/m/Y') . "        " . $date->getScheduleMorn() . "        " . $date->getScheduleAfter() . "        " . $date->getPlace() . "\n";
                            }
                        }
                        $newbody = str_replace("[dates]", $Texte, $newbody);
                        $newbody = str_replace("[stagiaire.prenom]", $inscription->getTrainee()->getFirstName(), $newbody);
                        $newbody = str_replace("[stagiaire.nom]", $inscription->getTrainee()->getLastName(), $newbody);
                        $newbody = str_replace("[lien]", $lien, $newbody);

                        // Envoyer un mail au supérieur hiérarchique
                        /*$body = "Bonjour,\n" .
                            "Une inscription à la session du " . $inscription->getSession()->getDateBegin()->format('d/m/Y') . "\nde la formation intitulée '" . $inscription->getSession()->getTraining()->getName() . "'\n"
                            . "a été réalisée par ".$inscription->getTrainee()->getFullName() .".\n"
                            . "Pour autoriser ". $inscription->getTrainee()->getFullName()  . " à participer à cette formation, merci de valider l'inscription en cliquant sur le lien suivant :". "\n"
                            . "http://www.univ-amu.fr";
                        */
                        $message = \Swift_Message::newInstance();
                        $message->setFrom($this->container->getParameter('mailer_from'), "Sygefor");
                        $message->setReplyTo($inscription->getSession()->getTraining()->getOrganization()->getEmail());
                        $message->setTo($inscription->getTrainee()->getEmailSup());
                        $message->setSubject($subject);
                        $message->setBody($newbody);

                        $this->container->get('mailer')->send($message);

                    }


                    return $this->redirectToRoute(
                        'front.account.checkout', array(
                            'inscriptionId' => $inscription->getId())
                    );
                }

                $sup = $inscription->getTrainee()->getFirstNameSup() . " " . $inscription->getTrainee()->getLastNameSup();
                $this->get('session')->getFlashBag()->add('warning', 'Le supérieur hiérarchique que vous avez renseigné est ' . $sup . '. Si ce n\'est pas la bonne personne, merci de mettre à jour la donnée dans le menu "Mon compte", onglet "Mon profil".');
            }


            return array(
                'user' => $this->getUser(),
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
                'user' => $this->getUser(),
                'training' => $training,
                'session' => $session,
                'flag' => $flagInsc
            );
        }
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Sygefor\Bundle\TrainingBundle\Entity\Training\AbstractTraining $training
     * @param \Sygefor\Bundle\MyCompanyBundle\Entity\Session $session
     * @param null $token
     *
     * @Route("/training/alert/{id}/{sessionId}", name="front.public.alert", requirements={"id": "\d+", "sessionId": "\d+"})
     * @ParamConverter("training", class="SygeforTrainingBundle:Training\AbstractTraining", options={"id" = "id"})
     * @ParamConverter("session", class="SygeforMyCompanyBundle:Session", options={"id" = "sessionId"})
     * @Template("@SygeforFront/Public/program/inscription.html.twig")
     *
     * @return array
     */
    public function alertAction(Request $request, ManagerRegistry $doctrine, AbstractTraining $training, Session $session, $token = null)
    {
        // in case shibboleth authentication done but user has not registered his account
        if (!is_object($this->getUser())) {
            return $this->redirectToRoute('front.account.register');
        }

        if (!$this->getUser()->getIsActive()) {
            $this->get('session')->getFlashBag()->add('warning', "Vous ne pouvez pas vous inscrire à une session tant que votre compte n'a pas
             été validé par un administrateur.");
            return $this->redirectToRoute('front.public.training', array('id' => $training->getId(), 'sessionId' => $session->getId(), 'token' => $token));
        }

        $this->apiTrainingController->setContainer($this->container);
        $training = $this->apiTrainingController->trainingAction($training);

        $alert = $doctrine->getManager()->getRepository('SygeforMyCompanyBundle:Alert')->findOneBy(array(
            'trainee' => $this->getUser(),
            'session'=> $session
        ));
        if ($alert) {
            $this->get('session')->getFlashBag()->add('warning', "Vous êtes déjà inscrit à l'alerte d'ouverture de la session.");
            return $this->redirectToRoute('front.account.registrations');
            //throw new ForbiddenOverwriteException('An inscription has already been found');
        }
        if (!$alert) {
            $alert = new Alert();
            $alert->setTrainee($this->getUser());
            $alert->setSession($session);
            $now = new \DateTime();
            $alert->setCreatedAt($now);

            $em = $doctrine->getManager();
            $em->persist($alert);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', 'Votre alerte a bien été enregistrée.');
        }

        return $this->redirectToRoute('front.public.training', array('id' => $training->getId(), 'sessionId' => $session->getId(), 'token' => $token));
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Sygefor\Bundle\TrainingBundle\Entity\Training\AbstractTraining $training
     * @param \Sygefor\Bundle\MyCompanyBundle\Entity\Session $session
     * @param null $token
     *
     * @Route("/training/alertremove/{id}/{sessionId}", name="front.public.alertremove", requirements={"id": "\d+", "sessionId": "\d+"})
     * @ParamConverter("training", class="SygeforTrainingBundle:Training\AbstractTraining", options={"id" = "id"})
     * @ParamConverter("session", class="SygeforMyCompanyBundle:Session", options={"id" = "sessionId"})
     * @Template("@SygeforFront/Public/program/inscription.html.twig")
     *
     * @return array
     */
    public function alertRemoveAction(Request $request,ManagerRegistry $doctrine, AbstractTraining $training, Session $session, $token = null)
    {
        // in case shibboleth authentication done but user has not registered his account
        if (!is_object($this->getUser())) {
            return $this->redirectToRoute('front.account.register');
        }

        if (!$this->getUser()->getIsActive()) {
            $this->get('session')->getFlashBag()->add('warning', "Vous ne pouvez pas vous inscrire à une session tant que votre compte n'a pas
             été validé par un administrateur.");
            return $this->redirectToRoute('front.public.training', array('id' => $training->getId(), 'sessionId' => $session->getId(), 'token' => $token));
        }

        $this->apiTrainingController->setContainer($this->container);
        $training = $this->apiTrainingController->trainingAction($training);

        $alert = $doctrine->getManager()->getRepository('SygeforMyCompanyBundle:Alert')->findOneBy(array(
            'trainee' => $this->getUser(),
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

        return $this->redirectToRoute('front.public.training', array('id' => $training->getId(), 'sessionId' => $session->getId(), 'token' => $token));
    }

    /**
     * @Route("/myprogram", name="front.account.myprogram")
     * @Template("Front/Public/myprogram.html.twig")
     */
    public function myProgramAction(Request $request, ManagerRegistry $doctrine, SessionRepository $sessionRepository)
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Trainee')->findByEmail($user->getCredentials()['mail']);
        $etablissement = $arTrainee[0]->getInstitution()->getName();

        // Recup param pour l'activation du multi établissement
        $multiEtab = $this->getParameter('multi_etab_actif');
        $listeEtab = $this->getParameter('liste_etab');

        if (array_key_exists($etablissement, $listeEtab)){
            $etab = $listeEtab[$etablissement];
            $code = $etab["codes"];
            $img = 'img/'.$etab["logo"];
        }else {
            $code = array("amu-drh", "AMU-CIPE");
            $img = 'img/logo.png';
        }

        $search = $this->createProgramQuery($code, $sessionRepository);
        $sessions = $search["items"];

        // creation entites pour recuperer les alertes
        $alerts = new MultipleAlert();
        foreach ($sessions as $session){
            if ($session->getSessiontype() == "A venir") {
                $alert = new SingleAlert();

                $sessionExiste = $doctrine->getManager()->getRepository('App\Entity\Session')->findOneBy(array(
                    'id' => $session["id"]
                ));
                // on regarde s'il existe déjà une alerte
                $alertExiste = $doctrine->getManager()->getRepository('App\Entity\Alert')->findOneBy(array(
                    'trainee' => $arTrainee[0],
                    'session'=> $sessionExiste
                ));
                if ($alertExiste) {
                    // si l'alerte existe, on coche la case de présence
                    $alert->setAlert(true);
                } else {
                    $alert->setAlert(false);
                }

                $alert->setSessionId($session["id"]);
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
                $sessionExiste = $doctrine->getManager()->getRepository('App/Entity/Session')->findOneBy(array(
                    'id' => $alert->getSessionId()
                ));

                $alertExiste = $doctrine->getManager()->getRepository('App/Entity/Alert')->findOneBy(array(
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

        return array('user' => $this->getUser(), 'search' => $search, 'img' => $img, 'form' => $form->createView(),'multiEtab' => $multiEtab);
    }

    /**
     * @Route("/allprogram", name="front.account.allprogram")
     * @Template("Front/Public/allprogram.html.twig")
     */
    public function allProgramAction(Request $request, ManagerRegistry $doctrine, SessionRepository $sessionRepository)
    {
        // Recuperation info du user authentifié
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Trainee')->findByEmail($user->getCredentials()['mail']);

        // Recuperation de tous les centres
        $centres = $doctrine->getRepository(Organization::class)->findAll();
        $codes = array();
        foreach ($centres as $centre) {
            $codes[] = $centre->getName();
        }
        $search = $this->createProgramQuery($codes, $sessionRepository);
        $sessions = $search["items"];

        // creation entites pour recuperer les alertes
        $alerts = new MultipleAlert();
        foreach ($sessions as $session){
            if ($session->getSessiontype() == "A venir") {
                $alert = new SingleAlert();

                $sessionExiste = $doctrine->getManager()->getRepository('App\Entity\Session')->findOneBy(array(
                    'id' => $session["id"]
                ));
                // on regarde s'il existe déjà une alerte
                $alertExiste = $doctrine->getManager()->getRepository('App\Entity\Alert')->findOneBy(array(
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
                $sessionExiste = $doctrine->getManager()->getRepository('App\Entity\Session')->findOneBy(array(
                    'id' => $alert->getSessionId()
                ));

                $alertExiste = $doctrine->getManager()->getRepository('App\Entity\Alert')->findOneBy(array(
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
     * @Route("/searchalerts/{centreCode}/{theme}/{texte}", name="front.account.searchalerts")
     * @Template("@SygeforFront/Public/searchResult.html.twig")
     */
    public function searchalertsAction(Request $request, ManagerRegistry $doctrine, SessionRepository $sessionRepository, $centreCode=null, $theme=null, $texte=null)
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Trainee')->findByEmail($user->getCredentials()['mail']);

        // Recup param pour l'activation du multi établissement
        $multiEtab = $this->getParameter('multi_etab_actif');

        if ($centreCode == 'tous')
            $centre = null;
        else
            $centre = $centreCode;
        if ($theme == 'tous')
            $thematique = null;
        else
            $thematique = $theme;

        $search = $this->createProgramQuerySearch(1, 1000, $centre, $thematique, $texte, $sessionRepository);
        $sessions = $search["items"];

        // creation entites pour recuperer les alertes
        $alerts = new MultipleAlert();
        foreach ($sessions as $session){
            if ($session->getSessiontype() == "A venir") {
                $alert = new SingleAlert();

                $sessionExiste = $doctrine->getManager()->getRepository('App\Entity\Session')->findOneBy(array(
                    'id' => $session->getId()
                ));
                // on regarde s'il existe déjà une alerte
                $alertExiste = $doctrine->getManager()->getRepository('App\Entity\Alert')->findOneBy(array(
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

        if ($formAlert->isValid()) {
            $arrAlerts = $alerts->getAlerts();
            $em = $doctrine->getManager();
            foreach ($arrAlerts as $alert){
                // On verifie si la session et l'alerte existent déjà
                $sessionExiste = $doctrine->getManager()->getRepository('App\Entity\Session')->findOneBy(array(
                    'id' => $alert->getSessionId()
                ));

                $alertExiste = $doctrine->getManager()->getRepository('App\Entity\Alert')->findOneBy(array(
                    'trainee' => $arTrainee[0],
                    'session'=> $sessionExiste
                ));

                // Si la case est cochée
                if ($alert->getAlert() == true) {
                    // Si l'alerte existe déjà, on ne touche à rien, sinon, on la crée
                    if (!$alertExiste) {
                        $alertNew = new Alert();
                        $alertNew->setTrainee($this->getUser());
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
     * @Route("/search", name="front.account.search")
     * @Template("@SygeforFront/Public/search.html.twig")
     */
    public function searchAction(Request $request, ManagerRegistry $doctrine)
    {
        $user = $this->getUser();

        // Recup param pour l'activation du multi établissement
        $multiEtab = $this->getParameter('multi_etab_actif');

        /** @var EntityManager $em */
        $em = $doctrine->getManager();
        $theme = $em->getRepository('App\Entity\Core\Term\Theme')->findOneBy(array('name' => 'Tous les domaines' ));
        $organization = $em->getRepository('App\Entity\Organization')->findOneBy(array('name' => 'Tous les établissements'));

        $defaultData = array('centre' => $organization, 'theme' => $theme, 'texte' => "");
        $form = $this->createForm(new SearchType(), $defaultData);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
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
                    if ($centreCode == "tous") {
                        $centreCode = "tous";
                    }
                }
                $texte = $form['texte']->getData();

                if ($request->get('shibboleth') == 1) {
                    if ($request->get('error') == "activation") {
                        $this->get('session')->getFlashBag()->add('warning', "Votre compte doit être activé par un administrateur avant de pouvoir vous connecter.");
                    }
                }

                return $this->redirectToRoute('front.public.searchalerts', array('centreCode' => $centreCode, 'theme' => $themeName, 'texte' => $texte));

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

}
<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/15/16
 * Time: 10:42 AM
 */

namespace App\Controller\Front;

use Sygefor\Bundle\ApiBundle\Controller\TrainingController;
use Sygefor\Bundle\ApiBundle\Controller\Account\AbstractRegistrationAccountController;
use Sygefor\Bundle\MyCompanyBundle\Entity\Inscription;
use Sygefor\Bundle\TrainingBundle\Entity\Training\AbstractTraining;
use Symfony\Component\Config\Definition\Exception\ForbiddenOverwriteException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Sygefor\Bundle\FrontBundle\Form\AuthorizationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

/**
 * This controller regroup actions related to registration.
 *
 * @Route("/account")
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
class RegistrationAccountController extends AbstractRegistrationAccountController
{
    protected $inscriptionClass = Inscription::class;

    protected $apiTrainingController;

    public function __construct()
    {
        $this->apiTrainingController = new TrainingController();
    }

    /**
     * Checkout registrations cart.
     *
     * @Route("/checkout", name="front.account.checkout")
     * @Template("@SygeforFront/Account/registration/checkout.html.twig")
     */
    public function checkoutAction(Request $request, $sessions = array())
    {
        if (!$this->getUser()->getIsActive()) {
            throw new ForbiddenOverwriteException("You account is not active");
        }

        $inscription = $this->getDoctrine()->getManager()->getRepository('SygeforMyCompanyBundle:Inscription')->find($request->get('inscriptionId'));
        $this->sendCheckoutNotification(array($inscription), $inscription->getTrainee());

        return $this->redirectToRoute('front.account.registrations');
    }

    /**
     * Registrations.
     *
     * @Route("/registrations", name="front.account.registrations")
     * @Template("@SygeforFront/Account/registration/registrations.html.twig")
     * @Method("GET")
     */
    public function registrationsAction(Request $request)
    {
        // Recup param pour l'activation du bouton de relance au N+1
        $relanceActif = $this->container->getParameter('relance_actif');

        $inscriptions = parent::registrationsAction($request);
        $upcoming = array();
        $upcomingIds = array();
        $past = array();
        $now = new \DateTime();
        $sup = "vide";
        foreach ($inscriptions as $inscription) {
            if ($inscription->getSession()->getDateBegin() < $now) {
                $past[] = $inscription;
                $inscription->upcoming = false;
            }
            else {
                $inscription->upcoming = true;
                $upcoming[] = $inscription;
                $upcomingIds[] = $inscription->getId();
                if ($inscription->getInscriptionStatus()->getName() == "En attente") {
                    $sup = $inscription->getTrainee()->getFirstNameSup() ." ". $inscription->getTrainee()->getLastNameSup();
                }
            }
        }

        if ($sup!="vide") {

//            $this->get('session')->getFlashBag()->add('warning', 'Le supérieur hiérarchique que vous avez renseigné est '.$sup.'. Si ce n\'est pas la bonne personne, merci de mettre à jour la donnée dans l\'onglet "Mon profil".');
        }

        return array('user' => $this->getUser(), 'upcoming' => $upcoming, 'past' => $past, 'upcomingIds' => implode(',', $upcomingIds), 'relance' => $relanceActif);
    }

    /**
     * Desist a registration.
     *
     * @Route("/registration/{id}/desist", name="front.account.registration.desist")
     * @Template("@SygeforFront/Account/registration/registration-desist.html.twig")
     */
    public function desistAction($id, Request $request)
    {
        $registration = $this->getDoctrine()->getRepository('SygeforInscriptionBundle:AbstractInscription')->find($id);
        $registration->pending = $registration->getInscriptionStatus()->getId() === 1;
        if ($request->getMethod() === "POST") {
            if (parent::desistAction($id, $request)['desisted']) {
                $this->get('session')->getFlashBag()->add('success', 'Votre désistement a bien été enregistré.');
                return $this->redirectToRoute('front.account.registrations');
            }
        }

        return array('user' => $this->getUser(), 'registration' => $registration);
    }

    /**
     * Authorize a registration.
     *
     * @Route("/registration/{id}/authorize", name="front.account.registration.authorize")
     */
    public function authorizeAction($id, Request $request)
    {
        $registration = $this->getDoctrine()->getRepository('SygeforInscriptionBundle:AbstractInscription')->find($id);
        $registration->pending = $registration->getInscriptionStatus()->getId() === 1;

        // Lien vers la page d'autorisation
        $lien = $this->container->getParameter('front_url') . "/account/registration/" . $id . "/valid";

        // Envoyer un mail au supérieur hiérarchique
        $templateTerm = $this->container->get('sygefor_core.vocabulary_registry')->getVocabularyById('sygefor_trainee.vocabulary_email_template');
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository(get_class($templateTerm));
        /** @var EmailTemplate $template */
        $templates = $repo->findBy(array('name' => "Demande de validation d'inscription", 'organization' => $registration->getSession()->getTraining()->getOrganization()));
        $subject = $templates[0]->getSubject();
        $body = $templates[0]->getBody();
        $newbody = str_replace("[session.formation.nom]", $registration->getSession()->getTraining()->getName(), $body);
        $Texte = "";
        foreach ($registration->getSession()->getDates() as $date) {
            if ($date->getDateBegin() == $date->getDateEnd()) {
                $Texte .= $date->getDateBegin()->format('d/m/Y') . "        " . $date->getScheduleMorn() . "        " . $date->getScheduleAfter() . "        " . $date->getPlace() . "\n";
            } else {
                $Texte .= $date->getDateBegin()->format('d/m/Y') . " au " . $date->getDateEnd()->format('d/m/Y') . "        " . $date->getScheduleMorn() . "        " . $date->getScheduleAfter() . "        " . $date->getPlace() . "\n";
            }
        }
        $newbody = str_replace("[dates]", $Texte, $newbody);
        $newbody = str_replace("[stagiaire.prenom]", $registration->getTrainee()->getFirstName(), $newbody);
        $newbody = str_replace("[stagiaire.nom]", $registration->getTrainee()->getLastName(), $newbody);
        $newbody = str_replace("[lien]", $lien, $newbody);

        $message = \Swift_Message::newInstance();
        $message->setFrom($this->container->getParameter('mailer_from'), "Sygefor");
        $message->setReplyTo($registration->getSession()->getTraining()->getOrganization()->getEmail());
        $message->setTo($registration->getTrainee()->getEmailSup());
        $message->setSubject($subject);
        $message->setBody($newbody);

        $this->container->get('mailer')->send($message);

        $this->get('session')->getFlashBag()->add('success', 'Votre demande d\'autorisation a bien été envoyée.');
        return $this->redirectToRoute('front.account.registrations');

    }

    /**
     * Valid registration
     * @Route("/registration/{id}/valid", name="front.account.registration.valid")
     * @Template("@SygeforFront/Account/registration/registration-valid.html.twig")
     *
     */
    public function validAction($id, Request $request)
    {
        // Authentification et récup du mail retourné par Shibboleth
        $shibbolethAttributes = $this->get('security.token_storage')->getToken()->getAttributes();
        $supMail = $shibbolethAttributes['mail'];
        // transforme le mail en minu
        $supMail = strtolower($supMail);
        $supFirstName = $shibbolethAttributes['givenName'];
        $supLastName = $shibbolethAttributes['sn'];

        // Récupération des infos de l'inscription
        $registration = $this->getDoctrine()->getRepository('SygeforInscriptionBundle:AbstractInscription')->find($id);
        if ($registration) {
            $dateSession = $registration->getSession()->getDateBegin()->format('d/m/Y');
            $nameTraining = $registration->getSession()->getTraining()->getName();

            // Récupération des infos du stagiaire
            $nameTrainee = $registration->getTrainee()->getFullName();
            $supMailTrainee = $registration->getTrainee()->getEmailSup();
            // transforme le mail en minu
            $supMailTrainee = strtolower($supMailTrainee);
            $supMail = strtolower($supMail);

            // Création du formulaire d'autorisation
            // Ajout du champ motif de refus
            $defaultData = array();
            $form = $this->createForm(AuthorizationType::class, $defaultData);
            /*        $form = $this->createFormBuilder($defaultData)
                        ->add('validation', ChoiceType::class, array(
                            'choices' => array('ok' => 'Favorable', 'nok' => 'Défavorable'),
                            'expanded' => true,
                            'multiple' => false,
                            'data' => 'ok',
                            'label' => "Avis"
                        ))
                        ->add('refuse', TextareaType::class, array(
                            'label' => 'Motif de refus',
                            'attr' => array('placeholder' => 'Expliquez les raisons pour lesquelles vous émettez un avis défavorable à cette demande de formation.')))
                        ->getForm();*/
            $form->handleRequest($request);

            // Si la personne authentifiée est bien le supérieur hiérarchique
            if ($supMailTrainee == $supMail) {
                // On vérifie que la demande n'a pas déjà été traitée (statut de l'inscription =1 ou 2)
                if ($registration->getInscriptionStatus()->getId() < 3) {
                    // On renvoie vers le formulaire d'autorisation
                    $access = "Formulaire";

                    if ($form->isSubmitted() && $form->isValid()) {
                        // Récupération de la décision
                        $dataForm = $form->getData();
                        if (isset($dataForm)) {
                            if ($dataForm['validation'] == "ok") {
                                // Si avis favorable, on modifie le statut de l'inscription et on envoie un mail au stagiaire
                                $registration->setInscriptionStatus(
                                    $this->getDoctrine()->getRepository('SygeforInscriptionBundle:Term\InscriptionStatus')->findOneBy(
                                        array('machineName' => 'favorable')
                                    )
                                );
                                $em = $this->getDoctrine()->getManager();
                                $em->persist($registration);
                                $em->flush();

                                $body = "Bonjour,\n" .
                                    "Votre inscription à la session du " . $registration->getSession()->getDateBegin()->format('d/m/Y') . "\nde la formation intitulée '" . $registration->getSession()->getTraining()->getName() . "'\n"
                                    . "a été approuvée par " . $supFirstName . " " . $supLastName . "\n";

                                $message = \Swift_Message::newInstance();
                                $message->setFrom($this->container->getParameter('mailer_from'), "Sygefor");
                                $message->setReplyTo($registration->getSession()->getTraining()->getOrganization()->getEmail());
                                $message->setTo($registration->getTrainee()->getEmail());
                                if ($registration->getTrainee()->getEmailCorr() != null)
                                    $message->setCc($registration->getTrainee()->getEmailCorr());
                                $message->setSubject("Avis favorable pour inscription à une formation");
                                $message->setBody($body);

                                $this->container->get('mailer')->send($message);

                                $this->get('session')->getFlashBag()->add('success', 'L\'avis favorable a bien été émis.');

                            } else {
                                // Sinon, on modifie le statut de l'inscription à "avis défavorable" et on envoie un mail au stagiaire
                                // Si avis défavorable, on modifie le statut de l'inscription et on envoie un mail au stagiaire
                                $registration->setInscriptionStatus(
                                    $this->getDoctrine()->getRepository('SygeforInscriptionBundle:Term\InscriptionStatus')->findOneBy(
                                        array('machineName' => 'defavorable')
                                    )
                                );
                                $registration->setRefuse($dataForm['refuse']);
                                $em = $this->getDoctrine()->getManager();
                                $em->persist($registration);
                                $em->flush();

                                $body = "Bonjour,\n" .
                                    "Votre inscription à la session du " . $registration->getSession()->getDateBegin()->format('d/m/Y') . "\nde la formation intitulée '" . $registration->getSession()->getTraining()->getName() . "'\n"
                                    . "a été refusée par " . $supFirstName . " " . $supLastName . ", au motif de : " . $registration->getRefuse() . "\n";

                                $message = \Swift_Message::newInstance();
                                $message->setFrom($this->container->getParameter('mailer_from'), "Sygefor");
                                $message->setReplyTo($registration->getSession()->getTraining()->getOrganization()->getEmail());
                                $message->setTo($registration->getTrainee()->getEmail());
                                if ($registration->getTrainee()->getEmailCorr() != null)
                                    $message->setCc($registration->getTrainee()->getEmailCorr());
                                $message->setSubject("Avis défavorable pour inscription à une formation");
                                $message->setBody($body);

                                $this->container->get('mailer')->send($message);

                                $this->get('session')->getFlashBag()->add('success', 'L\'avis défavorable a bien été émis.');

                            }
                        }
                        $access = "Avis émis";
                    }
                } else {
                    $access = "Demande déjà traitée";
                }
            } else {
                // Sinon, on affiche un message d'erreur
                $access = "Non autorisé";
            }
            return array('form'=> $form->createView(), 'trainee' => $registration->getTrainee(), 'registration' => $registration, 'access' => $access);
        } else {
            // Sinon, on affiche un message d'erreur
            $access = "Inscription non trouvée";
            return array('form'=> '', 'trainee' => '', 'registration' => '', 'access' => $access);
        }


    }

    /**
     * Download a authorization form.
     *
     * @Route("/registration/{ids}/authorization", name="front.account.registration.authorization")
     * @Method("GET")
     */
    public function authorizationAction($ids, Request $request)
    {
        return parent::authorizationAction($ids, $request);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Sygefor\Bundle\TrainingBundle\Entity\Training\AbstractTraining $training
     * @param null $sessionId
     * @param null $token
     *
     * @Route("/training/{id}/{sessionId}/{token}", name="front.account.training", requirements={"id": "\d+", "sessionId": "\d+"})
     * @ParamConverter("training", class="SygeforTrainingBundle:Training\AbstractTraining", options={"id" = "id"})
     * @Template("@SygeforFront/Public/program/training.html.twig")
     *
     *
     * @return array
     */
    public function trainingAccountAction(Request $request, AbstractTraining $training, $sessionId = null, $token = null)
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
                $em = $this->getDoctrine()->getManager();
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
}
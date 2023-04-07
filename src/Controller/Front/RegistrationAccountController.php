<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/15/16
 * Time: 10:42 AM
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;

/**
 * This controller regroup actions related to registration.
 *
 * @Route("/account")
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
class RegistrationAccountController extends AbstractController
{
    protected $inscriptionClass = Inscription::class;

    /**
     * Checkout registrations cart.
     *
     * @Route("/checkout", name="front.account.checkout")
     */
    public function checkoutAction(Request $request, ManagerRegistry $doctrine, $sessions = array())
    {
        $inscription = $doctrine->getManager()->getRepository('App\Entity\Back\Inscription')->find($request->get('inscriptionId'));
//        $this->sendCheckoutNotification($doctrine, array($inscription), $inscription->getTrainee());

        return $this->redirectToRoute('front.account.registrations');
    }

    /**
     * Registrations.
     *
     * @Route("/registrations", name="front.account.registrations")
     * @Template("Front/Account/registration/registrations.html.twig")
     * @Method("GET")
     */
    public function registrationsAction(Request $request, ManagerRegistry $doctrine)
    {
        // Recup param pour l'activation du bouton de relance au N+1
        $relanceActif = $this->getParameter('relance_actif');

        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        $inscriptions = $trainee->getInscriptions();
        $upcoming = array();
        $upcomingIds = array();
        $past = array();
        $now = new \DateTime();
        $sup = "vide";
        foreach ($inscriptions as $inscription) {
            if ($inscription->getSession()->getDatebegin() < $now) {
                $past[] = $inscription;
                $inscription->upcoming = false;
            }
            else {
                $inscription->upcoming = true;
                $upcoming[] = $inscription;
                $upcomingIds[] = $inscription->getId();
                if ($inscription->getInscriptionstatus()->getName() == "En attente") {
                    $sup = $inscription->getTrainee()->getFirstnamesup() ." ". $inscription->getTrainee()->getLastnamesup();
                }
            }
        }

        return array('user' => $trainee, 'upcoming' => $upcoming, 'past' => $past, 'upcomingIds' => implode(',', $upcomingIds), 'relance' => $relanceActif);
    }

    /**
     * Desist a registration.
     *
     * @Route("/registration/{id}/desist", name="front.account.registration.desist")
     * @Template("Front/Account/registration/registration-desist.html.twig")
     */
    public function desistAction($id, Request $request, ManagerRegistry $doctrine)
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        $registration = $doctrine->getRepository('App\Entity\Core\AbstractInscription')->find($id);
        $registration->pending = $registration->getInscriptionstatus()->getId() === 1;
        if ($request->getMethod() === "POST") {
            $em         = $doctrine->getManager();
            $repository = $em->getRepository($this->inscriptionClass);

            $inscription = $repository->findOneBy(array(
                'id'      => $id,
                'trainee' => $trainee,
            ));

            if ( ! $inscription) {
                throw new NotFoundHttpException('Unknown registration.');
            }

            // check date
            if ($inscription->getSession()->getDatebegin() < new \DateTime()) {
                throw new BadRequestHttpException('You cannot desist from a past session.');
            }

            // check status
            if ($inscription->getInscriptionstatus()->getStatus() > Inscriptionstatus::STATUS_ACCEPTED) {
                throw new BadRequestHttpException('Your registration has already been rejected.');
            }

            // ok, let's go
            if ($inscription->getInscriptionstatus()->getStatus() === Inscriptionstatus::STATUS_PENDING) {
                // if the inscription is pending, just delete it
                $em->remove($inscription);
                $em->flush();
                $this->get('session')->getFlashBag()->add('success', 'Votre désistement a bien été enregistré.');
                return $this->redirectToRoute('front.account.registrations');
            }
            else {
                // else set the status to "Desist"
                $status = $this->getDesistInscriptionStatus($doctrine, $trainee);
                $inscription->setInscriptionstatus($status);
                $this->get('session')->getFlashBag()->add('success', 'Votre désistement a bien été enregistré.');
                return $this->redirectToRoute('front.account.registrations');
            }

        }

        return array('user' => $trainee, 'registration' => $registration);
    }

    /**
     * Authorize a registration.
     *
     * @Route("/registration/{id}/authorize", name="front.account.registration.authorize")
     */
    public function authorizeAction($id, ManagerRegistry $doctrine, Request $request, VocabularyRegistry $vocabularyRegistry, MailerInterface $mailer)
    {
        $registration = $doctrine->getRepository('App\Entity\Core\AbstractInscription')->find($id);
        $registration->pending = $registration->getInscriptionstatus()->getId() === 1;

        if (!$registration->getTrainee()->getEmailSup()) {
            $this->get('session')->getFlashBag()->add('error', 'Vous ne pouvez pas relancer votre demande de validation car vous n\'avez pas renseigné de supérieur hiérarchique.');
            return $this->redirectToRoute('front.account.registrations');
        }

        // Lien vers la page d'autorisation
        $lien = "https://" . $this->getParameter('front_url') . "/account/registration/" . $id . "/valid";

        // Envoyer un mail au supérieur hiérarchique
        $templateTerm = $vocabularyRegistry->getVocabularyById(5);
        $em = $doctrine->getManager();
        $repo = $em->getRepository(get_class($templateTerm));
        /** @var Emailtemplate $template */
        $templates = $repo->findBy(array('name' => "Demande de validation d'inscription", 'organization' => $registration->getSession()->getTraining()->getOrganization()));
        $subject = $templates[0]->getSubject();
        $body = $templates[0]->getBody();
        $newbody = str_replace("[session.formation.nom]", $registration->getSession()->getTraining()->getName(), $body);
        $Texte = "";
        foreach ($registration->getSession()->getDates() as $date) {
            if ($date->getDatebegin() == $date->getDateend()) {
                $Texte .= $date->getDatebegin()->format('d/m/Y') . "        " . $date->getSchedulemorn() . "        " . $date->getScheduleafter() . "        " . $date->getPlace() . "\n";
            } else {
                $Texte .= $date->getDatebegin()->format('d/m/Y') . " au " . $date->getDateend()->format('d/m/Y') . "        " . $date->getSchedulemorn() . "        " . $date->getScheduleafter() . "        " . $date->getPlace() . "\n";
            }
        }
        $newbody = str_replace("[dates]", $Texte, $newbody);
        $newbody = str_replace("[stagiaire.prenom]", $registration->getTrainee()->getFirstname(), $newbody);
        $newbody = str_replace("[stagiaire.nom]", $registration->getTrainee()->getLastname(), $newbody);
        $newbody = str_replace("[lien]", $lien, $newbody);

        $message = (new Email())
            ->from($registration->getSession()->getTraining()->getOrganization()->getEmail())
            ->replyTo($registration->getSession()->getTraining()->getOrganization()->getEmail())
            ->to($registration->getTrainee()->getEmailSup())
            ->subject($subject)
            ->text($newbody);

        $mailer->send($message);

        $this->get('session')->getFlashBag()->add('success', 'Votre demande d\'autorisation a bien été envoyée.');
        return $this->redirectToRoute('front.account.registrations');

    }

    /**
     * Valid registration
     * @Route("/registration/{id}/valid", name="front.account.registration.valid")
     * @Template("Front/Account/registration/registration-valid.html.twig")
     *
     */
    public function validAction($id, ManagerRegistry $doctrine, Request $request, MailerInterface $mailer)
    {
        // Authentification et récup du mail retourné par Shibboleth
        $user = $this->getUser();
        $supMail = $user->getCredentials()['mail'];

        // transforme le mail en minu
        $supMail = strtolower($supMail);
        $supFirstName = $user->getCredentials()['givenName'];
        $supLastName = $user->getCredentials()['sn'];

        // Récupération des infos de l'inscription
        $registration = $doctrine->getRepository('App\Entity\Core\AbstractInscription')->find($id);
        if ($registration) {
            $dateSession = $registration->getSession()->getDatebegin()->format('d/m/Y');
            $nameTraining = $registration->getSession()->getTraining()->getName();

            // Récupération des infos du stagiaire
            $nameTrainee = $registration->getTrainee()->getFullname();
            $supMailTrainee = $registration->getTrainee()->getEmailsup();
            // transforme le mail en minu
            $supMailTrainee = strtolower($supMailTrainee);
            $supMail = strtolower($supMail);

            // Création du formulaire d'autorisation
            // Ajout du champ motif de refus
            $defaultData = array();
            $form = $this->createForm(AuthorizationType::class, $defaultData);

            $form->handleRequest($request);

            // Si la personne authentifiée est bien le supérieur hiérarchique
            if ($supMailTrainee == $supMail) {
                // On vérifie que la demande n'a pas déjà été traitée (statut de l'inscription =1 ou 2)
                if ($registration->getInscriptionstatus()->getId() < 3) {
                    // On renvoie vers le formulaire d'autorisation
                    $access = "Formulaire";

                    if ($form->isSubmitted() && $form->isValid()) {
                        // Récupération de la décision
                        $dataForm = $form->getData();
                        if (isset($dataForm)) {
                            if ($dataForm['validation'] == "ok") {
                                // Si avis favorable, on modifie le statut de l'inscription et on envoie un mail au stagiaire
                                $registration->setInscriptionstatus(
                                    $doctrine->getRepository('App\Entity\Term\Inscriptionstatus')->findOneBy(
                                        array('machinename' => 'favorable')
                                    )
                                );
                                $em = $doctrine->getManager();
                                $em->persist($registration);
                                $em->flush();

                                $body = "Bonjour,\n" .
                                    "Votre inscription à la session du " . $registration->getSession()->getDatebegin()->format('d/m/Y') . "\nde la formation intitulée '" . $registration->getSession()->getTraining()->getName() . "'\n"
                                    . "a été approuvée par " . $supFirstName . " " . $supLastName . "\n";

                                $message = (new Email())
                                    ->from($registration->getSession()->getTraining()->getOrganization()->getEmail())
                                    ->replyTo($registration->getSession()->getTraining()->getOrganization()->getEmail())
                                    ->to($registration->getTrainee()->getEmail())
                                    ->subject("Avis favorable pour inscription à une formation")
                                    ->text($body);
                                if ($registration->getTrainee()->getEmailcorr() != null)
                                    $message->cc($registration->getTrainee()->getEmailcorr());

                                $mailer->send($message);

                                $this->get('session')->getFlashBag()->add('success', 'L\'avis favorable a bien été émis.');

                            } else {
                                // Sinon, on modifie le statut de l'inscription à "avis défavorable" et on envoie un mail au stagiaire
                                // Si avis défavorable, on modifie le statut de l'inscription et on envoie un mail au stagiaire
                                $registration->setInscriptionstatus(
                                    $doctrine->getRepository('App\Entity\Term\Inscriptionstatus')->findOneBy(
                                        array('machinename' => 'defavorable')
                                    )
                                );
                                $registration->setRefuse($dataForm['refuse']);
                                $em = $doctrine->getManager();
                                $em->persist($registration);
                                $em->flush();

                                $body = "Bonjour,\n" .
                                    "Votre inscription à la session du " . $registration->getSession()->getDatebegin()->format('d/m/Y') . "\nde la formation intitulée '" . $registration->getSession()->getTraining()->getName() . "'\n"
                                    . "a été refusée par " . $supFirstName . " " . $supLastName . ", au motif de : " . $registration->getRefuse() . "\n";

                                $message = (new Email())
                                    ->from($registration->getSession()->getTraining()->getOrganization()->getEmail())
                                    ->replyTo($registration->getSession()->getTraining()->getOrganization()->getEmail())
                                    ->to($registration->getTrainee()->getEmail())
                                    ->subject("Avis défavorable pour inscription à une formation")
                                    ->text($body);
                                if ($registration->getTrainee()->getEmailcorr() != null)
                                    $message->setCc($registration->getTrainee()->getEmailcorr());

                                $mailer->send($message);

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
     * @param array $inscriptions
     * @param AbstractTrainee $trainee
     */
    protected function sendCheckoutNotification(ManagerRegistry $doctrine, EmailingBatchOperation $emailingBatchOperation, $inscriptions, $trainee)
    {
        // send a recap to the trainee
        $inscriptionIdsByOrganization = array();
        foreach ($inscriptions as $inscription) {
            $inscriptionIdsByOrganization[$inscription->getSession()
                ->getTraining()
                ->getOrganization()
                ->getId()][] = $inscription->getId();
        }

        foreach ($inscriptionIdsByOrganization as $organizationId => $inscriptionIds) {
            /** @var Emailtemplate $checkoutEmailTemplate */
            $checkoutEmailTemplate = $doctrine
                ->getRepository(Emailtemplate::class)
                ->findOneBy(array(
                    'organization' => $doctrine
                        ->getRepository(Organization::class)
                        ->find($organizationId),
                    'inscriptionStatus' => $doctrine
                        ->getRepository(Inscriptionstatus::class)
                        ->findOneBy(array('status' => Inscriptionstatus::STATUS_PENDING, 'organization' => null)
                        )));

            // generate authorization forms
            $attachments = array();

            if ($checkoutEmailTemplate) {
                $emailingBatchOperation->execute(
                    $inscriptionIds,
                    array(
                        'targetClass' => $this->inscriptionClass,
                        'preview' => FALSE,
                        'subject' => $checkoutEmailTemplate->getSubject(),
                        'message' => $checkoutEmailTemplate->getBody(),
                        'attachment' => empty($attachments) ? NULL : $attachments,
                        'typeUser' => get_class($trainee),
                    )
                );
            }
        }
    }

    /**
     * @param AbstractTrainee $trainee
     *
     * @return Inscriptionstatus|null
     */
    protected function getDesistInscriptionStatus(ManagerRegistry $doctrine, AbstractTrainee $trainee)
    {
        $em     = $doctrine->getManager();
        $status = $em->getRepository('App\Entity\Term\Inscriptionstatus')->findOneBy(array('machineName' => 'desist', 'organization' => null));
        if (!$status) {
            $status = $em->getRepository('pp\Entity\Term\Inscriptionstatus')->findOneBy(array('machineName' => 'desist', 'organization' => $trainee->getOrganization()));
        }

        return $status;
    }

    /**
     * Generate authorization forms.
     *
     * @param $trainee
     * @param $registrations
     * @param $templates
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    protected function getAuthorizationForms($doctrine,$trainee, $registrations, $templates)
    {
        $repository    = $doctrine->getManager()->getRepository($this->inscriptionClass);
        $sessionsByOrg = array();

        // verify & group sessions by organization
        /** @var AbstractInscription $registration */
        foreach ($registrations as $registration) {
            if (!($registration instanceof $this->inscriptionClass)) {
                $id           = (int) $registration;
                $registration = $repository->find($id);
                if (!$registration) {
                    throw new \InvalidArgumentException('The registration identifier is not valid : ' . $id);
                }
            }
            if ($registration->getTrainee() !== $trainee) {
                throw new \InvalidArgumentException('The registration does not belong to the trainee : ' . $registration->getId());
            }
            if ($registration->getInscriptionstatus()->getMachinename() !== 'desist') {
                $sessionsByOrg[$registration->getSession()->getTraining()->getOrganization()->getId()][] = $registration->getSession();
            }
        }

        if (is_string($templates)) {
            $templates = array($templates);
        }

        // build pages
        $forms = array();
        foreach ($sessionsByOrg as $org => $sessions) {
            // prepare pdf variables
            $organization = $sessions[0]->getTraining()->getOrganization();
            $variables    = array(
                'organization' => $organization,
                'trainee'      => $trainee,
                'sessions'     => $sessions,
            );
            foreach ($templates as $key => $template) {
                $forms[$organization->getCode()][$key] = $this->renderView($template, $variables);
            }
        }

        return $forms;
    }
}
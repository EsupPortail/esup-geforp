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

/**
 * This controller regroup actions related to registration.
 *
 */
#[Route(path: '/account')]
class RegistrationAccountController extends AbstractController
{
    protected string $inscriptionClass = Inscription::class;

    public function Index(): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            // Si l'utilisateur n'est pas authentifié pleinement, on redirige ou on lève une exception
            throw new AccessDeniedException('Vous devez être pleinement authentifié pour accéder à cette page.');
        }
            return $this->render('Front/Account/registration/registrations.html.twig');
    }
    /**
     * Checkout registrations cart.
     *
     */
    #[Route(path: '/checkout', name: 'front.account.checkout')]
    public function checkout(Request $request, ManagerRegistry $doctrine, $sessions = []): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $inscription = $doctrine->getManager()->getRepository(\App\Entity\Back\Inscription::class)->find($request->get('inscriptionId'));
//        $this->sendCheckoutNotification($doctrine, array($inscription), $inscription->getTrainee());

        return $this->redirectToRoute('front.account.registrations');
    }

    #[Route(path: '/registrations', name: 'front.account.registrations', methods: 'GET')]
    public function registrations(ManagerRegistry $doctrine): Response
    {
        // Recup param pour l'activation du bouton de relance au N+1
        $relanceActif = $this->getParameter('relance_actif');

        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        $inscriptions = $trainee->getInscriptions();
        $upcoming = [];
        $upcomingIds = [];
        $past = [];
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

        return $this->render('Front/Account/registration/registrations.html.twig',[
            'user' => $trainee,
            'upcoming' => $upcoming,
            'past' => $past, 'upcomingIds' => implode(',', $upcomingIds),
            'relance' => $relanceActif,
            ]);
    }

    /**
     * Desist a registration.
     *
     */
    #[Route(path: '/registration/{id}/desist', name: 'front.account.registration.desist')]
    public function desist($id, Request $request, ManagerRegistry $doctrine, VocabularyRegistry $vocabularyRegistry, MailerInterface $mailer): array
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        $registration = $doctrine->getRepository(\App\Entity\Core\AbstractInscription::class)->find($id);
        $registration->pending = $registration->getInscriptionstatus()->getId() === 1;
        if ($request->getMethod() === "POST") {
            $em         = $doctrine->getManager();
            $repository = $em->getRepository($this->inscriptionClass);

            $inscription = $repository->findOneBy(['id'      => $id, 'trainee' => $trainee]);

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
                return [$this->redirectToRoute('front.account.registrations')];
            }
            else {
                // else set the status to "Desist"
                $status = $this->getDesistInscriptionStatus($doctrine, $trainee);
                $inscription->setInscriptionstatus($status);
                $em->flush();

                // Envoyer un mail au supérieur hiérarchique
                $templateTerm = $vocabularyRegistry->getVocabularyById(5);
                $em = $doctrine->getManager();
                $repo = $em->getRepository($templateTerm::class);
                /** @var Emailtemplate $template */
                $templates = $repo->findBy(['name' => "Statut d'inscription : désistement", 'organization' => $registration->getSession()->getTraining()->getOrganization()]);
                $formathtml = $templates[0]->getPosition();
                if ($formathtml)
                    $newline = "<br>";
                else
                    $newline = "\n";
                $subject = $templates[0]->getSubject();
                $newsub = str_replace("[session.formation.nom]", $registration->getSession()->getTraining()->getName(), (string) $subject);
                $newsub = str_replace("[stagiaire.prenom]", $registration->getTrainee()->getFirstname(), $newsub);
                $newsub = str_replace("[stagiaire.nom]", $registration->getTrainee()->getLastname(), $newsub);
                $newsub = str_replace("[stagiaire.civilite]", $registration->getTrainee()->getTitle(), $newsub);
                $newsub = str_replace("[stagiaire.nomComplet]", $registration->getTrainee()->getFullName(), $newsub);

                $body = $templates[0]->getBody();
                $newbody = str_replace("[session.formation.nom]", $registration->getSession()->getTraining()->getName(), (string) $body);
                $Texte = "";
                foreach ($registration->getSession()->getDates() as $date) {
                    if ($date->getDatebegin() == $date->getDateend()) {
                        $Texte .= $date->getDatebegin()->format('d/m/Y') . "        " . $date->getSchedulemorn() . "        " . $date->getScheduleafter() . "        " . $date->getPlace() . $newline;
                    } else {
                        $Texte .= $date->getDatebegin()->format('d/m/Y') . " au " . $date->getDateend()->format('d/m/Y') . "        " . $date->getSchedulemorn() . "        " . $date->getScheduleafter() . "        " . $date->getPlace() . $newline;
                    }
                }
                $newbody = str_replace("[date]", $Texte, $newbody);
                $newbody = str_replace("[stagiaire.prenom]", $registration->getTrainee()->getFirstname(), $newbody);
                $newbody = str_replace("[stagiaire.nom]", $registration->getTrainee()->getLastname(), $newbody);
                $newbody = str_replace("[stagiaire.civilite]", $registration->getTrainee()->getTitle(), $newbody);
                $newbody = str_replace("[stagiaire.nomComplet]", $registration->getTrainee()->getFullName(), $newbody);

                $message = (new Email())
                    ->from($registration->getSession()->getTraining()->getOrganization()->getEmail())
                    ->replyTo($registration->getSession()->getTraining()->getOrganization()->getEmail())
                    ->to($registration->getTrainee()->getEmail())
                    ->subject($newsub);

                $flagSup = 0;
                if ($registration->getTrainee()->getEmailSup() != null) {
                    $flagSup = 1;
                    $message->cc($registration->getTrainee()->getEmailSup());
                }

                if ($registration->getTrainee()->getEmailcorr() != null) {
                    if ($flagSup == 0){
                        $message->cc($registration->getTrainee()->getEmailcorr());
                    } else {
                        $message->addCc($registration->getTrainee()->getEmailcorr());
                    }
                }

                // si Format HTML coché pour ce modèle, sinon format texte
                if ($templates[0]->getPosition() == 1) {
                    $message->html($newbody);
                } else
                    $message->text($newbody);

                $mailer->send($message);

                $this->get('session')->getFlashBag()->add('success', 'Votre désistement a bien été enregistré.');
                return [$this->redirectToRoute('front.account.registrations')];
            }

        }

        return ['user' => $trainee, 'registration' => $registration, $this->render('Front/Account/registration/registration-desist.html.twig')];
    }

    /**
     * Authorize a registration.
     *
     */
    #[Route(path: '/registration/{id}/authorize', name: 'front.account.registration.authorize')]
    public function authorize($id, ManagerRegistry $doctrine, VocabularyRegistry $vocabularyRegistry, MailerInterface $mailer): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $registration = $doctrine->getRepository(\App\Entity\Core\AbstractInscription::class)->find($id);
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
        $repo = $em->getRepository($templateTerm::class);
        /** @var Emailtemplate $template */
        $templates = $repo->findBy(['name' => "Demande de validation d'inscription", 'organization' => $registration->getSession()->getTraining()->getOrganization()]);
        $formathtml = $templates[0]->getPosition();
        if ($formathtml)
            $newline = "<br>";
        else
            $newline = "\n";
        $subject = $templates[0]->getSubject();
        $body = $templates[0]->getBody();
        $newbody = str_replace("[session.formation.nom]", $registration->getSession()->getTraining()->getName(), (string) $body);
        $Texte = "";
        foreach ($registration->getSession()->getDates() as $date) {
            if ($date->getDatebegin() == $date->getDateend()) {
                $Texte .= $date->getDatebegin()->format('d/m/Y') . "        " . $date->getSchedulemorn() . "        " . $date->getScheduleafter() . "        " . $date->getPlace() . $newline;
            } else {
                $Texte .= $date->getDatebegin()->format('d/m/Y') . " au " . $date->getDateend()->format('d/m/Y') . "        " . $date->getSchedulemorn() . "        " . $date->getScheduleafter() . "        " . $date->getPlace() . $newline;
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
            ->subject($subject);

        // si Format HTML coché pour ce modèle, sinon format texte
        if ($templates[0]->getPosition() == 1) {
            $message->html($newbody);
        } else
            $message->text($newbody);

        $mailer->send($message);

        $this->get('session')->getFlashBag()->add('success', 'Votre demande d\'autorisation a bien été envoyée.');
        return $this->redirectToRoute('front.account.registrations');

    }

    /**
     * Valid registration
     *
     */
    #[Route(path: '/registration/{id}/valid', name: 'front.account.registration.valid')]
    public function valid($id, ManagerRegistry $doctrine, VocabularyRegistry $vocRegistry, Request $request, MailerInterface $mailer): ?array
    {
        // Authentification et récup du mail retourné par Shibboleth
        $user = $this->getUser();
        // Récupération du user avec le format trainee
        $arTraineeUser = $doctrine->getRepository(\App\Entity\Back\Trainee::class)->findByEmail($user->getCredentials()['mail']);
        $traineeUser = $arTraineeUser[0];

        $supMail = $user->getCredentials()['mail'];

        // transforme le mail en minu
        $supMail = strtolower((string) $supMail);
        $supFirstName = $user->getCredentials()['givenName'];
        $supLastName = $user->getCredentials()['sn'];

        // Récupération des infos de l'inscription
        $registration = $doctrine->getRepository(\App\Entity\Core\AbstractInscription::class)->find($id);
        if ($registration) {
            $dateSession = $registration->getSession()->getDatebegin()->format('d/m/Y');
            $nameTraining = $registration->getSession()->getTraining()->getName();

            // Récupération des infos du stagiaire
            $nameTrainee = $registration->getTrainee()->getFullname();
            $supMailTrainee = $registration->getTrainee()->getEmailsup();
            // transforme le mail en minu
            $supMailTrainee = strtolower((string) $supMailTrainee);
            $supMail = strtolower($supMail);

            // Création du formulaire d'autorisation
            // Ajout du champ motif de refus
            $defaultData = [];
            $form = $this->createForm(AuthorizationType::class, $defaultData);

            $form->handleRequest($request);

            // Si la personne authentifiée est bien le supérieur hiérarchique
            if ($supMailTrainee == $supMail) {
                // On vérifie que la demande n'a pas déjà été traitée (statut de l'inscription =1 ou 2)
                if ($registration->getInscriptionstatus()->getMachinename() == 'waiting') {
                    // On renvoie vers le formulaire d'autorisation
                    $access = "Formulaire";

                    if ($form->isSubmitted() && $form->isValid()) {
                        // Récupération de la décision
                        $dataForm = $form->getData();
                        if (isset($dataForm)) {
                            if ($dataForm['validation'] == "ok") {
                                // Si avis favorable, on modifie le statut de l'inscription et on envoie un mail au stagiaire
                                $registration->setInscriptionstatus(
                                    $doctrine->getRepository(\App\Entity\Term\Inscriptionstatus::class)->findOneBy(
                                        ['machinename' => 'favorable']
                                    )
                                );
                                $em = $doctrine->getManager();
                                $em->persist($registration);
                                $em->flush();

                                // Recuperation des templates emails dans le registre des vocabulaires
                                $templateTerm = $vocRegistry->getVocabularyById(5);
                                $repo = $em->getRepository($templateTerm::class);
                                /** @var Emailtemplate $template */
                                $templates = $repo->findBy(['name' => "Statut d'inscription : avis favorable du N+1", 'organization' => $registration->getSession()->getTraining()->getOrganization()]);
                                $subject1 = $templates[0]->getSubject();
                                $subject = str_replace("[session.formation.nom]", $registration->getSession()->getTraining()->getName(), (string) $subject1);
                                $body = $templates[0]->getBody();
                                $formathtml = $templates[0]->getPosition();
                                if ($formathtml)
                                    $newline = "<br>";
                                else
                                    $newline = "\n";

                                $newbody = str_replace("[session.formation.nom]", $registration->getSession()->getTraining()->getName(), (string) $body);

                                $Texte = "";
                                foreach ($registration->getSession()->getDates() as $date) {
                                    if ($date->getDatebegin() == $date->getDateend()) {
                                        $Texte .= $date->getDatebegin()->format('d/m/Y') . "        " . $date->getSchedulemorn() . "        " . $date->getScheduleafter() . "        " . $date->getPlace() . $newline;
                                    } else {
                                        $Texte .= $date->getDatebegin()->format('d/m/Y') . " au " . $date->getDateend()->format('d/m/Y') . "        " . $date->getSchedulemorn() . "        " . $date->getScheduleafter() . "        " . $date->getPlace() . $newline;
                                    }
                                }
                                $newbody = str_replace("[dates]", $Texte, $newbody);
                                $newbody = str_replace("[stagiaire.prenom]", $registration->getTrainee()->getFirstname(), $newbody);
                                $newbody = str_replace("[stagiaire.nom]", $registration->getTrainee()->getLastname(), $newbody);
                                $newbody = str_replace("[stagiaire.nomComplet]", $registration->getTrainee()->getFullName(), $newbody);
                                $newbody = str_replace("[stagiaire.civilite]", $registration->getTrainee()->getTitle(), $newbody);
                                $newbody = str_replace("[session.dateDebut]", $registration->getSession()->getDatebegin()->format('d/m/Y'), $newbody);
                                $newbody = str_replace("[session.dateFin]", $registration->getSession()->getDateend()->format('d/m/Y'), $newbody);

                                // Envoyer un mail au stagiaire
                                $message = (new Email())
                                    ->from($registration->getSession()->getTraining()->getOrganization()->getEmail())
                                    ->replyTo($registration->getSession()->getTraining()->getOrganization()->getEmail())
                                    ->to($registration->getTrainee()->getEmail())
                                    ->subject($subject);
                                if ($registration->getTrainee()->getEmailcorr() != null)
                                    $message->cc($registration->getTrainee()->getEmailcorr());

                                // si Format HTML coché pour ce modèle, sinon format texte
                                if ($templates[0]->getPosition() == 1) {
                                    $message->html($newbody);
                                } else
                                    $message->text($newbody);

                                $mailer->send($message);

                                $this->get('session')->getFlashBag()->add('success', 'L\'avis favorable a bien été émis.');

                            } else {
                                // Sinon, on modifie le statut de l'inscription à "avis défavorable" et on envoie un mail au stagiaire
                                // Si avis défavorable, on modifie le statut de l'inscription et on envoie un mail au stagiaire
                                $registration->setInscriptionstatus(
                                    $doctrine->getRepository(\App\Entity\Term\Inscriptionstatus::class)->findOneBy(
                                        ['machinename' => 'defavorable']
                                    )
                                );
                                $registration->setRefuse($dataForm['refuse']);
                                $em = $doctrine->getManager();
                                $em->persist($registration);
                                $em->flush();

                                // Recuperation des templates emails dans le registre des vocabulaires
                                $templateTerm = $vocRegistry->getVocabularyById(5);
                                $repo = $em->getRepository($templateTerm::class);
                                /** @var Emailtemplate $template */
                                $templates = $repo->findBy(['name' => "Statut d'inscription : avis défavorable du N+1", 'organization' => $registration->getSession()->getTraining()->getOrganization()]);
                                $subject1 = $templates[0]->getSubject();
                                $subject = str_replace("[session.formation.nom]", $registration->getSession()->getTraining()->getName(), (string) $subject1);
                                $body = $templates[0]->getBody();
                                $formathtml = $templates[0]->getPosition();
                                if ($formathtml)
                                    $newline = "<br>";
                                else
                                    $newline = "\n";

                                $newbody = str_replace("[session.formation.nom]", $registration->getSession()->getTraining()->getName(), (string) $body);

                                $Texte = "";
                                foreach ($registration->getSession()->getDates() as $date) {
                                    if ($date->getDatebegin() == $date->getDateend()) {
                                        $Texte .= $date->getDatebegin()->format('d/m/Y') . "        " . $date->getSchedulemorn() . "        " . $date->getScheduleafter() . "        " . $date->getPlace() . $newline;
                                    } else {
                                        $Texte .= $date->getDatebegin()->format('d/m/Y') . " au " . $date->getDateend()->format('d/m/Y') . "        " . $date->getSchedulemorn() . "        " . $date->getScheduleafter() . "        " . $date->getPlace() . $newline;
                                    }
                                }
                                $newbody = str_replace("[dates]", $Texte, $newbody);
                                $newbody = str_replace("[stagiaire.prenom]", $registration->getTrainee()->getFirstname(), $newbody);
                                $newbody = str_replace("[stagiaire.nom]", $registration->getTrainee()->getLastname(), $newbody);
                                $newbody = str_replace("[stagiaire.nomComplet]", $registration->getTrainee()->getFullName(), $newbody);
                                $newbody = str_replace("[stagiaire.civilite]", $registration->getTrainee()->getTitle(), $newbody);
                                $newbody = str_replace("[session.dateDebut]", $registration->getSession()->getDatebegin()->format('d/m/Y'), $newbody);
                                $newbody = str_replace("[session.dateFin]", $registration->getSession()->getDateend()->format('d/m/Y'), $newbody);

                                // Envoyer un mail au stagiaire
                                $message = (new Email())
                                    ->from($registration->getSession()->getTraining()->getOrganization()->getEmail())
                                    ->replyTo($registration->getSession()->getTraining()->getOrganization()->getEmail())
                                    ->to($registration->getTrainee()->getEmail())
                                    ->subject($subject);
                                if ($registration->getTrainee()->getEmailcorr() != null)
                                    $message->cc($registration->getTrainee()->getEmailcorr());

                                // si Format HTML coché pour ce modèle, sinon format texte
                                if ($templates[0]->getPosition() == 1) {
                                    $message->html($newbody);
                                } else
                                    $message->text($newbody);

                                $mailer->send($message);

                                $this->get('session')->getFlashBag()->add('success', 'L\'avis défavorable a bien été émis.');

                            }
                        }
                        $access = "Avis émis";
                        // On renvoie sur la page d'accueil des responsables
                        return $this->redirectToRoute('front.account.team.registrations');
                    }
                } else {
                    $access = "Demande déjà traitée";
                }
            } else {
                // Sinon, on affiche un message d'erreur
                $access = "Non autorisé";
            }
            return ['form'=> $form->createView(), 'trainee' => $registration->getTrainee(), 'registration' => $registration, 'access' => $access, 'user' => $traineeUser, $this->render('Front/Account/registration/registration-valid.html.twig')];
        } else {
            // Sinon, on affiche un message d'erreur
            $access = "Inscription non trouvée";
            return ['form'=> '', 'trainee' => '', 'registration' => '', 'access' => $access, 'user' => $traineeUser, $this->render('Front/Account/registration/registration-valid.html.twig')];
        }

    }

    protected function sendCheckoutNotification(ManagerRegistry $doctrine, EmailingBatchOperation $emailingBatchOperation, array $inscriptions, AbstractTrainee $trainee): void
    {
        // send a recap to the trainee
        $inscriptionIdsByOrganization = [];
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
                ->findOneBy(['organization' => $doctrine
                    ->getRepository(Organization::class)
                    ->find($organizationId), 'inscriptionStatus' => $doctrine
                    ->getRepository(Inscriptionstatus::class)
                    ->findOneBy(['status' => Inscriptionstatus::STATUS_PENDING, 'organization' => null]
                    )]);

            // generate authorization forms
            $attachments = [];

            if ($checkoutEmailTemplate) {
                $emailingBatchOperation->execute(
                    $inscriptionIds,
                    ['targetClass' => $this->inscriptionClass, 'preview' => FALSE, 'subject' => $checkoutEmailTemplate->getSubject(), 'message' => $checkoutEmailTemplate->getBody(), 'attachment' => empty($attachments) ? NULL : $attachments, 'typeUser' => $trainee::class]
                );
            }
        }
    }

    protected function getDesistInscriptionStatus(ManagerRegistry $doctrine, AbstractTrainee $trainee): ?\App\Entity\Term\Inscriptionstatus
    {
        $em     = $doctrine->getManager();
        $status = $em->getRepository(\App\Entity\Term\Inscriptionstatus::class)->findOneBy(['machinename' => 'desist', 'organization' => null]);
        if (!$status) {
            $status = $em->getRepository(\App\Entity\Term\Inscriptionstatus::class)->findOneBy(['machinename' => 'desist', 'organization' => $trainee->getOrganization()]);
        }

        return $status;
    }

    protected function getAuthorizationForms(ManagerRegistry $doctrine,$trainee, $registrations, $templates): array
    {
        $repository    = $doctrine->getManager()->getRepository($this->inscriptionClass);
        $sessionsByOrg = [];

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
            $templates = [$templates];
        }

        // build pages
        $forms = [];
        foreach ($sessionsByOrg as $org => $sessions) {
            // prepare pdf variables
            $organization = $sessions[0]->getTraining()->getOrganization();
            $variables    = ['organization' => $organization, 'trainee'      => $trainee, 'sessions'     => $sessions];
            foreach ($templates as $key => $template) {
                $forms[$organization->getCode()][$key] = $this->renderView($template, $variables);
            }
        }

        return $forms;
    }
}
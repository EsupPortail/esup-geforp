<?php

/**
 * Created by PhpStorm.
 */
namespace App\BatchOperations\Generic;


use App\Vocabulary\VocabularyRegistry;
use Doctrine\ORM\EntityManager;
use App\BatchOperations\AbstractBatchOperation;
use App\Utils\HumanReadable\HumanReadablePropertyAccessorFactory;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\MonologBundle\SwiftMailer\MessageFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Core\AbstractTrainee;
use Symfony\Component\Mime\Message;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Jsvrcek\ICS\Model\CalendarEvent;
use Jsvrcek\ICS\Model\Calendar;
use Jsvrcek\ICS\CalendarExport;
use Jsvrcek\ICS\CalendarStream;
use Jsvrcek\ICS\Utility\Formatter;
use Symfony\Component\Mime\Part\DataPart;

final class EmailingBatchOperation extends AbstractBatchOperation
{

    protected $targetClass = AbstractTrainee::class;
    private Security $Security;

    public function __construct(protected Security $security, protected ParameterBagInterface $parameterBag, protected VocabularyRegistry $vocabularyRegistry, protected MailerInterface $mailer, protected HumanReadablePropertyAccessorFactory $humanReadablePropertyAccessorFactory)
    {
    }

    public function setSecurity(Security $security): void
    {
        $this->security = $security;
    }

    /**
     *
     * @return mixed
     */
    public function execute(array $idList = [], array $options = []): mixed
    {
        //setting alternate targetclass if provided in options
        if (isset($options['targetClass'])) {
            $this->setTargetClass($options['targetClass']);
        }

        $targetEntities = $this->getObjectList($idList);

        if (isset($options['preview']) && $options['preview']) {
            return $this->parseAndSendMail($targetEntities[0], $options['subject'] ?? '', $options['message'] ?? '', null, $preview = true);
        }

        // check if user has access
        // check trainee proxy for inscription checkout
        if (isset($options['typeUser']) && get_parent_class($options['typeUser']) !== AbstractTrainee::class) {
            foreach ($targetEntities as $key => $user) {
                if (!$this->Security->isGranted('VIEW', $user)) {
                    unset($targetEntities[$key]);
                }
            }
        }

        $this->parseAndSendMail($targetEntities, $options['subject'] ?? '', $options['message'] ?? '', $options['attachment'] ?? null, false, $options['ical'] ?? false, $options['format'] ?? 0);

        return new Response('', \Symfony\Component\HttpFoundation\Response::HTTP_NO_CONTENT);
    }

    /**
     * @return array{templates: object[]} configuration element for front-end modal window
     */
    public function getModalConfig($options = []): array
    {
        $templateTerm = $this->vocabularyRegistry->getVocabularyById(5); // vocabulary_email_template
        /** @var EntityManager $em */
        $this->doctrine->getManager();
        $repo = $this->doctrine->getRepository($templateTerm::class);

        if (!empty($options['inscriptionstatus'])) {
            $repoInscriptionStatus = $this->doctrine->getRepository(\App\Entity\Term\Inscriptionstatus::class);
            $inscriptionStatus = $repoInscriptionStatus->findById($options['inscriptionstatus']);
            $templates = $repo->findBy(['inscriptionstatus' => $inscriptionStatus, 'organization' => $this->security->getUser()->getOrganization()]);
        } elseif (!empty($options['presencestatus'])) {
            $repoPresenceStatus = $this->doctrine->getRepository(\App\Entity\Term\Presencestatus::class);
            $presenceStatus = $repoPresenceStatus->findById($options['presencestatus']);
            $templates = $repo->findBy(['presenceStatus' => $presenceStatus, 'organization' => $this->security->getUser()->getOrganization()]);
        } else {
            //if no presence/inscription status is found, we get all organization templates
            $templates = $repo->findBy(['organization' => $this->security->getUser()->getOrganization(), 'presencestatus' => null, 'inscriptionstatus' => null]);
        }

        return ['templates' => $templates];
    }

    /**
     * Parses subject and body content according to entity, and sends the mail.
     * WARNING / an $em->clear() is done if there is more than one entity.
     *
     * @param $entities
     * @param $subject
     * @param $body
     * @param array $attachments
     * @param bool $preview
     *
     * @return array
     */
    public function parseAndSendMail($entities, $subject, $body, $attachments = [], $preview = false, $ical = false, $format = 0): array
    {
        $em = null;
        $last = "";
        $doClear = true;
        if (!is_array($entities)) {
            $entities = [$entities];
            $doClear = false;
        }

        if ($entities === []) {
            return array();
        }

        if ($preview) {
            return ['email' => ['subject' => $this->replaceTokens($subject, $entities[0]), 'message' => $this->replaceTokens($body, $entities[0])]];
        }
        // foreach entity
        $i = 0;
        $em = $this->doctrine->getManager();
        if ($doClear) {
            $em->clear();
        }

        foreach ($entities as $entity) {
            try {
                // reload entity because of em clear
                $entity = $em->getRepository($entity::class)->find($entity->getId());

                if (get_parent_class($entity) === \App\Entity\Core\AbstractTrainee::class)
                    $organization = $this->security->getUser()->getOrganization();
                else
                    $organization = $entity->getOrganization();

                $hrpa = $this->humanReadablePropertyAccessorFactory->getAccessor($entity);
                $email = $hrpa->email;
                $subjectR = $this->replaceTokens($subject, $entity);
                $bodyR = $this->replaceTokens($body, $entity, $format);
                $msg = (new Email())
                    ->from($organization->getEmail())
                    ->to($email)
                    ->replyTo($organization->getEmail())
                    ->subject($subjectR);

                // si Format HTML coché pour ce modèle, sinon format texte
                if ($format == 1) {
                    $msg->html($bodyR);
                } else
                    $msg->text($bodyR);


                // attachements
                if (!empty($attachments)) {
                    if (!is_array($attachments)) {
                        $attachments = [$attachments];
                    }

                    foreach ($attachments as $attachment) {
                        $path = $attachment->getPathname();

                        if ($attachment::class == UploadedFile::class)
                            $originalName = $attachment->getClientOriginalName();
                        else
                            $originalName = $attachment->getFilename();

                        $msg->attachFromPath($path, $originalName);
                    }
                }

                // Dans le cas des stagiaires
                if ((get_parent_class($entity) === \App\Entity\Core\AbstractTrainee::class)||(get_parent_class($entity) === \App\Entity\Core\AbstractInscription::class)) {
                    $flagSup = 0;
                    if ($hrpa->emailSup != null) {
                        $emailSup = $hrpa->emailSup;
                        $flagSup = 1;
                        $msg->cc($emailSup);
                    }

                    if ($hrpa->emailCorr != null) {
                        $emailCorr = $hrpa->emailCorr;
                        if ($flagSup == 0){
                            $msg->cc($emailCorr);
                        } else {
                            $msg->addCc($emailCorr);
                        }
                    }

                    if ($ical) {
                        // AJOUT ICS CAL
                        $calendar = new Calendar();
                        $calendar->setTimezone(new \DateTimeZone('Europe/Paris'));
                        $calendar->setProdId('-//Calendrier GEFORP//');

                        $sessionName = $entity->getSession()->getTraining()->getName();

                        // Creation tableau des evenements
                        $tabDates = $entity->getSession()->getDates();
                        $tabEvent = []; $i=0;
                        foreach ($tabDates as $tabDate) {
                            $id = $tabDate->getId();
                            $tabEvent[$i] = new CalendarEvent();
                            $dateBegin = clone $tabDate->getDatebegin();
                            $dateEnd = clone $tabDate->getDateend();
                            $tabEvent[$i]->setStart($dateBegin->modify('+8 hours'))
                                ->setEnd($dateEnd->modify('+18 hours'))
                                ->setSummary($sessionName)
                                ->setUid('geforp'.$id);
                            $calendar->addEvent($tabEvent[$i]);
                            ++$i;
                        }

                        $calendarExport = new CalendarExport(new CalendarStream(), new Formatter());

                        // Fichier attaché ou demande dans outlook suivant une ou plusieurs dates pour la session
                        if ($i == 1) {
                            // une seule date -> demande d'acceptation
                            $calendar->setMethod('REQUEST'); // or PUBLISH
                            $calendarExport->addCalendar($calendar);

                            $ics = $calendarExport->getStream();
                            // inline it
                            $attachment = new DataPart($ics, 'inline.ics', 'text/calendar', 'quoted-printable');
                            $attachment->asInline();
                            $attachment->getHeaders()->addParameterizedHeader('Content-Type', 'text/calendar', ['charset' => 'utf-8', 'method' => 'REQUEST']);
                            $msg->attachPart($attachment);
                        } else {
                            // plusieurs dates -> fichier attaché
                            $calendarExport->addCalendar($calendar);

                            $ics = $calendarExport->getStream();
                            $msg->attach($ics, 'ical.ics', 'text/calendar');
                        }
                    }
                }

                // Envoi message
                $last = $this->mailer->send($msg);

                // save email in db
                $email = new \App\Entity\Core\Email();
                $email->setUserFrom($em->getRepository(\App\Entity\Core\User::class)->find($this->security->getUser()->getId()));
                $email->setEmailFrom($organization->getEmail());
                if (get_parent_class($entity) === \App\Entity\Core\AbstractTrainee::class) {
                    $email->setTrainee($entity);
                } elseif (get_parent_class($entity) === \App\Entity\Core\AbstractTrainer::class) {
                    $email->setTrainer($entity);
                } elseif (get_parent_class($entity) === \App\Entity\Core\AbstractInscription::class) {
                    $email->setTrainee($entity->getTrainee());
                    $email->setSession($entity->getSession());
                } elseif ($entity::class === \App\Entity\Back\Alert::class) {
                    $email->setTrainee($entity->getTrainee());
                    $email->setSession($entity->getSession());
                } elseif (get_parent_class($entity) === \App\Entity\Core\AbstractParticipation::class) {
                    $email->setTrainer($entity->getTrainer());
                    $email->setSession($entity->getSession());
                }

                $email->setSubject($subjectR);
                $email->setBody($bodyR);
                $email->setSendAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                $em->persist($email);
                if (++$i % 500 === 0) {
                    $em->flush();
                    $em->clear();
                }
            } catch (\Exception) {
                // continue
            }
        }

        $em->flush();
        if ($doClear) {
            $em->clear();
        }
        return $last;
    }

    /**
     * @param $content
     * @param $entity
     *
     */
    private function replaceTokens($content, $entity, $format=0): ?string
    {
        /** @var HumanReadablePropertyAccessor $HRPA */
        $HRPA = $this->humanReadablePropertyAccessorFactory->getAccessor($entity);

        return preg_replace_callback('#\[(.*?)\]#',
            function ($matches) use ($HRPA, $entity, $format) {
                $newline = $format ? "<br>" : "\n";

                $property = $matches[1];
                if ($property=="lien") {
                    return "https://" . $this->parameterBag->get('front_url') . "/account/registration/" . $HRPA->id . "/valid";
                }
            },
            (string) $content);
    }
}

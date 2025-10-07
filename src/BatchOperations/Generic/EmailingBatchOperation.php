<?php

/**
 * Created by PhpStorm.
 */
namespace App\BatchOperations\Generic;


use App\Vocabulary\VocabularyRegistry;
use Doctrine\ORM\EntityManager;
use App\BatchOperations\AbstractBatchOperation;
use App\Utils\HumanReadable\HumanReadablePropertyAccessorFactory;
use Psr\Container\ContainerInterface;
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
final class
EmailingBatchOperation extends AbstractBatchOperation
{
    use \App\BatchOperations\AttachEmailPublipostAttachment;

    protected string $targetClass = AbstractTrainee::class;
    private Security $Security;

    public function __construct(protected Security $security, protected ParameterBagInterface $parameterBag, protected VocabularyRegistry $vocabularyRegistry, protected MailerInterface $mailer, protected HumanReadablePropertyAccessorFactory $humanReadablePropertyAccessorFactory,)
    {
        parent::__construct();

    }

    public function setSecurity(Security $security): void
    {
        $this->security = $security;
    }

    /**
     *
     * @return array[]
     */
    public function execute(array $idList = [], array $options = []): array
    {
        //setting alternate targetclass if provided in options
        if (isset($options['targetClass'])) {
            $this->setTargetClass($options['targetClass']);
        }

        $targetEntities = $this->getObjectList($idList);
        if (isset($options['preview']) && $options['preview']) {
            if (empty($targetEntities)) {
                return [['error' => 'Aucune entité à prévisualiser.'], Response::HTTP_BAD_REQUEST];
            }
            return $this->parseAndSendMail($targetEntities[0], $options['subject'] ?? '', $options['message'] ?? '', [],true);
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

	$this->parseAndSendMail($targetEntities, isset($options['subject']) ? $options['subject'] : '', isset($options['message']) ? $options['message'] : '', (isset($options['attachment'])) ? $options['attachment'] : [], false, isset($options['ical']) ? $options['ical'] : false, isset($options['format']) ? $options['format'] : 0, isset($options['sendresp']) ? $options['sendresp'] : 1);

        return ['', Response::HTTP_NO_CONTENT];
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
            $inscriptionStatus = $repoInscriptionStatus->find($options['inscriptionstatus']);
            $templates = $repo->findBy(['inscriptionstatus' => $inscriptionStatus, 'organization' => $this->security->getUser()->getOrganization()]);
        } elseif (!empty($options['presencestatus'])) {
            $repoPresenceStatus = $this->doctrine->getRepository(\App\Entity\Term\Presencestatus::class);
            $presenceStatus = $repoPresenceStatus->find($options['presencestatus']);
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
     * @return array[]
     */
    public function parseAndSendMail($entities, $subject, $body, array $attachments = [], bool $preview = false, $ical = false, $format = 0, $sendresp = 1): array
    {
        $em = null;
        $last = [];
        $doClear = true;
        if (!is_array($entities)) {
            $entities = [$entities];
            $doClear = false;
        }

        if ($entities === []) {
            return [];
        }

        if ($preview) {
            return ['email' => ['subject' => $this->replaceTokens($subject, $entities[0]), 'message' => $this->replaceTokens($body, $entities[0])]];
        }
        // foreach entity
        $i = 0;
        $em = $this->doctrine->getManager();

        foreach ($entities as $entity) {
            try {
                // reload entity because of em clear
                $entity = $em->getRepository($entity::class)->find($entity->getId());

                if (get_parent_class($entity) === \App\Entity\Core\AbstractTrainee::class)
                    $organization = $this->security->getUser()->getOrganization();
                else
                    $organization = $this->security->getUser()->getOrganization();

                $hrpa = $this->humanReadablePropertyAccessorFactory->getAccessor($entity);

                $email = $hrpa->email;
                if (empty($email)) {
                    error_log("Pas d'email pour l'entité ID " . $entity->getId());
                    continue;
                }
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
                    $attachments = is_array($attachments) ? $attachments : [$attachments];
                    $validAttachments = array_filter($attachments, fn($item) => $item instanceof File);
                    foreach ($validAttachments as $attachment) {
                        $path = $attachment->getPathname();

                        $originalName = $attachment instanceof UploadedFile
                            ? $attachment->getClientOriginalName()
                            : $attachment->getFilename();

                        $msg->attachFromPath($path, $originalName);
                    }
                }


                // Dans le cas des stagiaires
                if (
                    in_array(get_parent_class($entity), [
                        \App\Entity\Core\AbstractTrainee::class,
                        \App\Entity\Core\AbstractInscription::class,
                        \App\Entity\Core\AbstractTrainer::class
                    ])
                ) {
                    $flagSup = 0;

		    // Envoyer une copie au N+1 et/ou correspondant formation si l'option est activée
                    if ($sendresp == 0) {
                        // si option à 'NON', on ne fait rien
                    } else {
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

			    $schedulemorn = $tabDate->getSchedulemorn();
                            $scheduleafter = $tabDate->getScheduleafter();

                            // Par défaut, on fixe les horaires à la journée
                            $horBegin = '+8 hours';
                            $horEnd = '+18 hours';
                            // récupération des horaires pour exploitation avec le calendrier
                            $j=0;
                            $horMod1=[];
                            $horMod2=[];
                            // Horaires matin
                            if (preg_match_all('/\b([01]?\d|2[0-3]):[0-5]\d\b/', $schedulemorn, $matchesMorn)) {
                                    foreach ($matchesMorn[0] as $hor) {
                                        $partsMorn = explode(':', $hor);
                                        $horMod1[$j] = "$partsMorn[0]h$partsMorn[1]";
                                        $horMod2[$j] = "$partsMorn[0] hours $partsMorn[1] minutes";
                                        $j++;
                                    }
                            }
                            // Horaires après-midi
                            if (preg_match_all('/\b([01]?\d|2[0-3]):[0-5]\d\b/', $scheduleafter, $matchesAfter)) {
                                    foreach ($matchesAfter[0] as $hor) {
                                        $partsAfter = explode(':', $hor);
                                        $horMod1[$j] = "$partsAfter[0]h$partsAfter[1]";
                                        $horMod2[$j] = "$partsAfter[0] hours $partsAfter[1] minutes";
                                        $j++;
                                    }
                            }
                            // au moins 2 horaires dans le tableau
                            if (sizeof($horMod1) >= 2) {
                                    // Conversion en date pour comparaison
                                    $heureBegin = \DateTime::createFromFormat('H\hi', $horMod1[0]);
                                    $heureEnd = \DateTime::createFromFormat('H\hi', end($horMod1));
                                    // Vérif l'heure de fin est bien > à l'heure de début
                                    if ($heureBegin<$heureEnd) {
                                        $horBegin = "+" . $horMod2[0];
                                        $horEnd = "+" . end($horMod2);
                                    }
                            }

                            $tabEvent[$i]->setStart($dateBegin->modify($horBegin))
                                ->setEnd($dateEnd->modify($horEnd))
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
                            $icalPart = new DataPart(
                                $ics,
                                'invite.ics',
                                        'text/calendar; method=REQUEST, charset=UTF-8',
                            );
                            $icalPart->getHeaders()->setHeaderBody('Parameterized', 'Content-Disposition', 'inline');
                            $msg->addPart($icalPart);
                        } else {
                            // plusieurs dates -> fichier attaché
                            $calendarExport->addCalendar($calendar);

                            $ics = $calendarExport->getStream();
                            $msg->attach($ics, 'ical.ics', 'text/calendar');
                        }
                    }
                }

                // Envoi message
                $last[] = $this->mailer->send($msg);

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
            } catch (\Exception $e) {
                error_log('Erreur d\'envoi email : ' . $e->getMessage());
                // throw $e; // temporaire pour voir l'erreur
                continue;
            }
        }

        $em->flush();
        if ($doClear) {
            $em->clear();
        }

        if(empty($last)){
            return [];
        }
        return array($last) ? $last : [$last];
    }

    /**
     * @param $content
     * @param $entity
     *
     */
    private function replaceTokens($content, $entity, $format = 0): ?string
    {
        $HRPA = $this->humanReadablePropertyAccessorFactory->getAccessor($entity);

	$newContent = preg_replace_callback('/\[(.*?)\]/',
            function ($matches) use ($HRPA, $entity, $format) {
                if ($format)
                    $newline = "<br>";
                else
                    $newline = "\n";
                $property = $matches[1];
                if ($property=="dates"){
                    $session = $entity->getSession();
                    $tabDatesSessions = $session->getDates();
                    $Texte = "";
                    foreach ($tabDatesSessions as $dateSession) {
                        if ($dateSession->getDateend() == $dateSession->getDatebegin()) {
                            $Texte .= $dateSession->getDatebegin()->format('d/m/Y')."        ".$dateSession->getSchedulemorn()."        ".$dateSession->getScheduleafter()."        ".$dateSession->getPlace().$newline;
                        }
                        else {
                            $Texte .= $dateSession->getDatebegin()->format('d/m/Y')." au ".$dateSession->getDateend()->format('d/m/Y')."        ".$dateSession->getSchedulemorn()."        ".$dateSession->getScheduleafter()."        ".$dateSession->getPlace().$newline;
                        }
                    }
                    return $Texte;
                }
                else {
                    if ($property=="lien") {
                        $Texte = "https://" . $this->parameterBag->get('front_url') . "/account/registration/" . $HRPA->id . "/valid";
                        return $Texte;
                    }
                    else {
                        return $HRPA->$property;
                    }
                }
            },
            $content);

        return $newContent;

/*
        return preg_replace_callback('/\[(.*?)\]',
            function ($matches) use ($HRPA, $entity, $format) {
                $newline = $format ? "<br>" : "\n";
                $property = $matches[1];

                if ($property === "lien") {
                    return "https://" . $this->parameterBag->get('front_url') . "/account/registration/" . $HRPA->id . "/valid";
                }

                // Récupérer la valeur même si elle est null
                try {
                    $value = $HRPA->$property;
                } catch (\Throwable $e) {
                    return ''; // propriété inaccessible
                }

                // Gestion des objets DateTime
                if ($value instanceof \DateTimeInterface) {
                    return $value->format('d/m/Y H');
                }

                // Gestion des tableaux ou collections
                if (is_iterable($value)) {
                    $elements = [];
                    foreach ($value as $item) {
                        if (is_object($item)) {
                            $elements[] = method_exists($item, '__toString') ? (string)$item :
                                (method_exists($item, 'getName') ? $item->getName() : 'objet');
                        } else {
                            $elements[] = $item;
                        }
                    }
                    return implode(', ', $elements);
                }

                return nl2br((string)$value);
            },
            (string) $content
        );*/
    }
}

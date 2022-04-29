<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 12/06/14
 * Time: 18:13.
 */
namespace App\BatchOperations\Generic;


use App\Vocabulary\VocabularyRegistry;
use Doctrine\ORM\EntityManager;
use App\BatchOperations\AbstractBatchOperation;
use App\Utils\HumanReadable\HumanReadablePropertyAccessorFactory;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\MonologBundle\SwiftMailer\MessageFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Core\AbstractTrainee;
use Symfony\Component\Mime\Message;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailingBatchOperation extends AbstractBatchOperation
{
    /** @var  ContainerBuilder $container */
    protected $container;

    protected $parameterBag;
    protected $security;
    protected $vocRegistry;
    protected $mailer;
    protected $hrpaf;

    protected $targetClass = AbstractTrainee::class;

    public function __construct(Security $security, ParameterBagInterface $parameterBag, VocabularyRegistry $vocRegistry, MailerInterface $mailer, HumanReadablePropertyAccessorFactory $hrpaf)
    {
        $this->parameterBag = $parameterBag;
        $this->security = $security;
        $this->vocRegistry = $vocRegistry;
        $this->mailer = $mailer;
        $this->hrpaf = $hrpaf;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $idList
     * @param array $options
     *
     * @return mixed
     */
    public function execute(array $idList = array(), array $options = array())
    {
        //setting alternate targetclass if provided in options
        if (isset($options['targetClass'])) {
            $this->setTargetClass($options['targetClass']);
        }

        $targetEntities = $this->getObjectList($idList);

        if (isset($options['preview']) && $options['preview']) {
            return $this->parseAndSendMail($targetEntities[0], isset($options['subject']) ? $options['subject'] : '', isset($options['message']) ? $options['message'] : '', null, $preview = true);
        }

        // check if user has access
        // check trainee proxy for inscription checkout
        if (isset($options['typeUser']) && get_parent_class($options['typeUser']) !== AbstractTrainee::class) {
            foreach ($targetEntities as $key => $user) {
                if (!$this->container->get('security.context')->isGranted('VIEW', $user)) {
                    unset($targetEntities[$key]);
                }
            }
        }
        $this->parseAndSendMail($targetEntities, isset($options['subject']) ? $options['subject'] : '', isset($options['message']) ? $options['message'] : '', (isset($options['attachment'])) ? $options['attachment'] : null);

        return new Response('', 204);
    }

    /**
     * @return array configuration element for front-end modal window
     */
    public function getModalConfig($options = array())
    {
        $templateTerm = $this->vocRegistry->getVocabularyById(5); // vocabulary_email_template
        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();
        $repo = $this->doctrine->getRepository(get_class($templateTerm));

        if (!empty($options['inscriptionstatus'])) {
            $repoInscriptionStatus = $this->doctrine->getRepository('App\Entity\Core\Term\Inscriptionstatus');
            $inscriptionStatus = $repoInscriptionStatus->findById($options['inscriptionstatus']);
            $templates = $repo->findBy(array('inscriptionstatus' => $inscriptionStatus, 'organization' => $this->security->getUser()->getOrganization()));
        }
        else if (!empty($options['presencestatus'])) {
            $repoPresenceStatus = $this->doctrine->getRepository('App\Entity\Core\Term\Presencestatus');
            $presenceStatus = $repoPresenceStatus->findById($options['presencestatus']);
            $templates = $repo->findBy(array('presenceStatus' => $presenceStatus, 'organization' => $this->security->getUser()->getOrganization()));
        }
        else {
            //if no presence/inscription status is found, we get all organization templates
            $templates = $repo->findBy(array('organization' => $this->security->getUser()->getOrganization(), 'presencestatus' => null, 'inscriptionstatus' => null));
        }

        return array('templates' => $templates);
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
    public function parseAndSendMail($entities, $subject, $body, $attachments = array(), $preview = false)
    {
        $last = "";
        $doClear = true;
        if (!is_array($entities)) {
            $entities = array($entities);
            $doClear = false;
        }

        if (empty($entities)) {
            return;
        }

        if ($preview) {
            return array('email' => array(
                'subject' => $this->replaceTokens($subject, $entities[0]),
                'message' => $this->replaceTokens($body, $entities[0]),
            ));
        }
        else {
            // foreach entity
            $i = 0;
            $em = $this->doctrine->getManager();
            if ($doClear) {
                $em->clear();
            }
            foreach ($entities as $entity) {
                try {
                    // reload entity because of em clear
                    $entity = $em->getRepository(get_class($entity))->find($entity->getId());
                    $organization = $entity->getOrganization();

                    /*
                    $message = \Swift_Message::newInstance();
                    $messageSup = \Swift_Message::newInstance();
                    $messageCorr = \Swift_Message::newInstance();

                    $message->setFrom($this->container->getParameter('mailer_from'), $organization->getName());
                    $message->setReplyTo($organization->getEmail());
                    $message->setContentType("text/html");

                    $messageSup->setFrom($this->container->getParameter('mailer_from'), $organization->getName());
                    $messageSup->setReplyTo($organization->getEmail());
                    $messageSup->setContentType("text/html");

                    $messageCorr->setFrom($this->container->getParameter('mailer_from'), $organization->getName());
                    $messageCorr->setReplyTo($organization->getEmail());
                    $messageCorr->setContentType("text/html");

                    // attachements
                    if (!empty($attachments)) {
                        if (!is_array($attachments)) {
                            $attachments = array($attachments);
                        }
                        foreach ($attachments as $attachment) {
                            $attached = new \Swift_Attachment(file_get_contents($attachment), (method_exists($attachment, 'getClientOriginalName')) ? $attachment->getClientOriginalName() : $attachment->getFileName());
                            $message->attach($attached);
                            $messageSup->attach($attached);
                            $messageCorr->attach($attached);
                        }
                    }

                    $hrpa = $this->container->get('sygefor_core.human_readable_property_accessor_factory')->getAccessor($entity);
                    $email = $hrpa->email;
                    $message->setTo($email);
                    $message->setSubject($this->replaceTokens($subject, $entity));
                    $message->setBody($this->replaceTokens($body, $entity));

                    // Dans le cas des stagiaires
                    if ((get_parent_class($entity) === 'App\Entity\Core\AbstractTrainee')||(get_parent_class($entity) === 'App\Entity\Core\AbstractInscription')) {
                        $emailSupCorr = [];
                        if ($hrpa->emailSup != null) {
                            $emailSup = $hrpa->emailSup;
                            $emailSupCorr[] = $emailSup;
                        }

                        if ($hrpa->emailCorr != null) {
                            $emailCorr = $hrpa->emailCorr;
                            $emailSupCorr[] = $emailCorr;
                        }
                        $message->setCc($emailSupCorr);
                    }
                    $last = $this->container->get('mailer')->send($message);*/

                    $hrpa = $this->hrpaf->getAccessor($entity);
                    $email = $hrpa->email;
                    $subject = $this->replaceTokens($subject, $entity);
                    $body = $this->replaceTokens($body, $entity);
                    $msg = (new Email())
                        ->from($organization->getEmail())
                        ->to($email)
                        //->cc('cc@example.com')
                        //->bcc('bcc@example.com')
                        ->replyTo($organization->getEmail())
                        //->priority(Email::PRIORITY_HIGH)
                        ->subject($subject)
                        ->text($body);


                    // Dans le cas des stagiaires
                    if ((get_parent_class($entity) === 'App\Entity\Core\AbstractTrainee')||(get_parent_class($entity) === 'App\Entity\Core\AbstractInscription')) {
                        $emailSupCorr = "";
                        if ($hrpa->emailSup != null) {
                            $emailSup = $hrpa->emailSup;
                            $emailSupCorr .= $emailSup;
                        }

                        if ($hrpa->emailCorr != null) {
                            $emailCorr = $hrpa->emailCorr;
                            if ($emailSupCorr == "")
                                $emailSupCorr .= $emailCorr;
                            else
                                $emailSupCorr .= "," . $emailCorr;
                        }
                        $msg->cc($emailSupCorr);
                    }

                    $last = $this->mailer->send($msg);


                    // save email in db
                    $email = new \App\Entity\Core\Email();
                    $email->setUserFrom($em->getRepository('App\Entity\Core\User')->find($this->security->getUser()->getId()));
                    $email->setEmailFrom($organization->getEmail());
                    if (get_parent_class($entity) === 'App\Entity\Core\AbstractTrainee') {
                        $email->setTrainee($entity);
                    }
                    else if (get_parent_class($entity) === 'App\Entity\Core\AbstractTrainer') {
                        $email->setTrainer($entity);
                    }
                    else if (get_parent_class($entity) === 'App\Entity\Core\AbstractInscription') {
                        $email->setTrainee($entity->getTrainee());
                        $email->setSession($entity->getSession());
                    } else if (get_class($entity) === 'App\Entity\Alert') {
                        $email->setTrainee($entity->getTrainee());
                        $email->setSession($entity->getSession());
                    } else if (get_parent_class($entity) === 'App\Entity\Core\AbstractParticipation') {
                        $email->setTrainer($entity->getTrainer());
                        $email->setSession($entity->getSession());
                    }
                    $email->setSubject($subject);
                    $email->setBody($body);
                    $email->setSendAt(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
                    $em->persist($email);
                    if (++$i % 500 === 0) {
                        $em->flush();
                        $em->clear();
                    }
                } catch (\Exception $e) {
                    // continue
                }
            }
            $em->flush();
            if ($doClear) {
                $em->clear();
            }

            return $last;
        }
    }

    /**
     * @param $content
     * @param $entity
     *
     * @return string
     */
    protected function replaceTokens($content, $entity)
    {
        /** @var HumanReadablePropertyAccessor $HRPA */
        $HRPA = $this->hrpaf->getAccessor($entity);

        $newContent = preg_replace_callback('/\[(.*?)\]/',
            function ($matches) use ($HRPA, $entity) {
                $property = $matches[1];
                if ($property=="dates"){
                    $session = $entity->getSession();
                    $Dates = $session->getDates();
                    $Texte = "";
                    foreach ($Dates as $date) {
                        if ($date->getDateend() == $date->getDatebegin()) {
                            $Texte .= $date->getDatebegin()->format('d/m/Y')."        ".$date->getSchedulemorn()."        ".$date->getScheduleafter()."        ".$date->getPlace()."\n";
                        }
                        else {
                            $Texte .= $date->getDatebegin()->format('d/m/Y')." au ".$date->getDateend()->format('d/m/Y')."        ".$date->getSchedulemorn()."        ".$date->getScheduleafter()."        ".$date->getPlace()."\n";
                        }
                    }
                    return $Texte;
                }
                else {
                    if ($property=="lien") {
                        //$Texte = "https://sygefor3.univ-amu.fr/account/registration/" .$HRPA->id  . "/valid";
                        $Texte = $this->parameterBag->get('front_url') . "/account/registration/" . $HRPA->id . "/valid";
                        $Texte = "<a href=\"$Texte\">$Texte</a>";
                        return $Texte;
                    }
                    else {
                        return $HRPA->$property;
                    }
                }
            },
            $content);

        return $newContent;
    }
}

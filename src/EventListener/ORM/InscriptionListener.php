<?php

namespace App\EventListener\ORM;

use App\BatchOperations\Generic\EmailingBatchOperation;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Term\Emailtemplate;
use App\Entity\Core\AbstractInscription;

/**
 * Inscription listener to perfom some operation on persist/update
 *  - send a mail to the trainee if the property sendInscriptionStatusMail has been set to true.
 */
#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]final readonly class InscriptionListener
{
    public function __construct(private EmailingBatchOperation $emailingBatchOperation, private MailerInterface $mailer)
    {
    }

    /**
     * Returns hash of events, that this listener is bound to.
     *
     */
    public function getSubscribedEvents(): array
    {
        return [Events::postPersist, Events::postUpdate];
    }

    /**
     * Send the inscription status mail.
     * @throws TransportExceptionInterface
     */
    public function postProcess(PostPersistEventArgs|PostUpdateEventArgs $eventArgs, $new = false): void
    {
        $object = $eventArgs->getObject();
        if ($object instanceof AbstractInscription) {
            if ($object->isSendinscriptionstatusmail()) {
                $this->sendInscriptionStatusMail($eventArgs);
            }

            // sending mail to organization manager if new inscription status is disclaimer
	        if (!$new) {
		        $this->sendMailDisclaimerInscriptionStatusMail($eventArgs);
	        }
        }
    }

    /**
     * postPersist.
     * @throws TransportExceptionInterface
     */
    public function postPersist(PostPersistEventArgs $postPersistEventArgs): void
    {
        $this->postProcess($postPersistEventArgs, true);
    }

    /**
     * postUpdate.
     * @throws TransportExceptionInterface
     */
    public function postUpdate(PostUpdateEventArgs $postUpdateEventArgs): void
    {
        $this->postProcess($postUpdateEventArgs, false);
    }

    /**
     * sendMail.
     */
    private function sendInscriptionStatusMail(PostPersistEventArgs $postPersistEventArgs): void
    {
        /** @var AbstractInscription $object */
        $object = $postPersistEventArgs->getObject();

        // find the first template for the given inscription status
        $entityRepository = $postPersistEventArgs->getObjectManager()->getRepository(\App\Entity\Term\Emailtemplate::class);

        /** @var Emailtemplate $emailtemplate */
        $emailtemplate = $entityRepository->findOneBy(['organization' => $object->getSession()->getTraining()->getOrganization(), 'inscriptionStatus' => $object->getInscriptionstatus()], ['position' => 'ASC']);

	    if ($emailtemplate) {
		    // send the mail with the batch service
		    $this->emailingBatchOperation->parseAndSendMail(
		    	$object,
			    $emailtemplate->getSubject(),
			    $emailtemplate->getCc(),
			    null,
			    $emailtemplate->getBody()
		    );
	    }
    }

    /**
     *
     *
     * @throws TransportExceptionInterface
     */
    private function sendMailDisclaimerInscriptionStatusMail(PostPersistEventArgs|PostUpdateEventArgs  $postPersistEventArgs): void
    {
	    /** @var AbstractInscription $object */
     $object = $postPersistEventArgs->getObject();

	    $unitOfWork = $postPersistEventArgs->getObjectManager()->getUnitOfWork();
	    $chgSet = $unitOfWork->getEntityChangeSet($object);

	    if (isset($chgSet['inscriptionstatus'])) {
		    $inscriptionstatus = $object->getInscriptionstatus();

		    if ($inscriptionstatus->getNotify() !== 0) {
                $Dates = $object->getSession()->getDates();
                $Texte = "";
                foreach ($Dates as $date) {
                    if ($date->getDateend() == $date->getDatebegin()) {
                        $Texte .= $date->getDatebegin()->format('d/m/Y')."        ".$date->getSchedulemorn()."        ".$date->getScheduleafter()."        ".$date->getPlace()."\n";
                    }
                    else {
                        $Texte .= $date->getDatebegin()->format('d/m/Y')." au ".$date->getDateend()->format('d/m/Y')."        ".$date->getSchedulemorn()."        ".$date->getScheduleafter()."        ".$date->getPlace()."\n";
                    }
                }

                $body = "Bonjour,\n" .
                    "Le statut de l'inscription de " . $object->getTrainee()->getFullName() . ' à la session du ' . $object->getSession()->getDateBegin()->format('d/m/Y') . "\nde la formation intitulée '" . $object->getSession()->getTraining()->getName() . "'\n"
                    . "est passé à '" . $inscriptionstatus->getName() . "'.\n"
                    . "Le calendrier de la session est le suivant : \n" . $Texte;

                $email = (new Email())
                    ->from($object->getSession()->getTraining()->getOrganization()->getEmail())
                    ->replyTo($object->getSession()->getTraining()->getOrganization()->getEmail())
                    ->to($object->getSession()->getTraining()->getOrganization()->getEmail())
                    ->subject("Changement de statut d'inscription : ". $inscriptionstatus->getName())
                    ->text($body);

                $this->mailer->send($email);

            }
	    }
    }
}
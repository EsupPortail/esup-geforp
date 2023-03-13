<?php

namespace App\EventListener\ORM;

use App\BatchOperations\Generic\EmailingBatchOperation;
use Doctrine\ORM\Events;
use Html2Text\Html2Text;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Term\Emailtemplate;
use App\Entity\Core\AbstractInscription;

/**
 * Inscription listener to perfom some operation on persist/update
 *  - send a mail to the trainee if the property sendInscriptionStatusMail has been set to true.
 */
class InscriptionListener implements EventSubscriber
{
    private $emailingBatchOp;
    private $mailer;

    /**
     * @param EmailingBatchOperation $emailingBatchOp
     * @param MailerInterface $mailer
     */
    public function __construct(EmailingBatchOperation $emailingBatchOp, MailerInterface $mailer)
    {
        $this->emailingBatchOp = $emailingBatchOp;
        $this->mailer = $mailer;
    }

    /**
     * Returns hash of events, that this listener is bound to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return array(
          Events::postPersist,
          Events::postUpdate,
        );
    }

    /**
     * Send the inscription status mail.
     */
    public function postProcess(LifecycleEventArgs $eventArgs, $new = false)
    {
        $inscription = $eventArgs->getObject();
        if ($inscription instanceof AbstractInscription) {
            if ($inscription->isSendinscriptionstatusmail()) {
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
     */
    public function postPersist(LifecycleEventArgs $eventArgs)
    {
        $this->postProcess($eventArgs, true);
    }

    /**
     * postUpdate.
     */
    public function postUpdate(LifecycleEventArgs $eventArgs)
    {
        $this->postProcess($eventArgs, false);
    }

    /**
     * sendMail.
     */
    protected function sendInscriptionStatusMail(LifecycleEventArgs $eventArgs)
    {
        /** @var AbstractInscription $inscription */
        $inscription = $eventArgs->getEntity();

        // find the first template for the given inscription status
        $repository = $eventArgs->getEntityManager()->getRepository('App\Entity\Term\Emailtemplate');

        /** @var Emailtemplate $template */
        $template = $repository->findOneBy(array(
            'organization' => $inscription->getSession()->getTraining()->getOrganization(),
            'inscriptionStatus' => $inscription->getInscriptionstatus(),
        ), array('position' => 'ASC'));

	    if ($template) {
		    // send the mail with the batch service
		    $this->emailingBatchOp->sendEmails(
		    	$inscription,
			    $template->getSubject(),
			    $template->getCc(),
			    null,
			    $template->getBody()
		    );
	    }
    }

    /**
     * @param LifecycleEventArgs $eventArgs
     *
     * @return mixed
     *
     * @throws \Exception
     */
    protected function sendMailDisclaimerInscriptionStatusMail($eventArgs)
    {
	    /** @var AbstractInscription $inscription */
	    $inscription = $eventArgs->getEntity();

	    $uow = $eventArgs->getEntityManager()->getUnitOfWork();
	    $chgSet = $uow->getEntityChangeSet($inscription);

	    if (isset($chgSet['inscriptionstatus'])) {
		    $status = $inscription->getInscriptionstatus();

		    if ($status->getNotify()) {
                $Dates = $inscription->getSession()->getDates();
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
                    "Le statut de l'inscription de " . $inscription->getTrainee()->getFullName() . ' à la session du ' . $inscription->getSession()->getDateBegin()->format('d/m/Y') . "\nde la formation intitulée '" . $inscription->getSession()->getTraining()->getName() . "'\n"
                    . "est passé à '" . $status->getName() . "'.\n"
                    . "Le calendrier de la session est le suivant : \n" . $Texte;

                $message = (new Email())
                    ->from($inscription->getSession()->getTraining()->getOrganization()->getEmail())
                    ->replyTo($inscription->getSession()->getTraining()->getOrganization()->getEmail())
                    ->to($inscription->getSession()->getTraining()->getOrganization()->getEmail())
                    ->subject("Changement de statut d'inscription : ". $status->getName())
                    ->text($body);

                $this->mailer->send($message);

            }
	    }
    }
}

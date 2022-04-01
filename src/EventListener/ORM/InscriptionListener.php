<?php

namespace App\EventListener\ORM;

use Doctrine\ORM\Events;
use Html2Text\Html2Text;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\Container;
use App\Entity\Core\Term\Emailtemplate;
use App\Entity\Core\AbstractInscription;

/**
 * Inscription listener to perfom some operation on persist/update
 *  - send a mail to the trainee if the property sendInscriptionStatusMail has been set to true.
 */
class InscriptionListener implements EventSubscriber
{
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
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
        $inscription = $eventArgs->getEntity();
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
        $repository = $eventArgs->getEntityManager()->getRepository('App\Entity\Core\Term\Emailtemplate');

        /** @var Emailtemplate $template */
        $template = $repository->findOneBy(array(
            'organization' => $inscription->getSession()->getTraining()->getOrganization(),
            'inscriptionStatus' => $inscription->getInscriptionstatus(),
        ), array('position' => 'ASC'));

	    if ($template) {
		    // send the mail with the batch service
		    $this->container->get('sygefor_core.batch.email')->sendEmails(
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

	    if (isset($chgSet['inscriptionStatus'])) {
		    $status = $inscription->getInscriptionstatus();

		    if ($status->getNotify()) {
			    return $this->container->get('notification.mailer')->send('inscription.status_changed', $inscription->getSession()->getTraining()->getOrganization(), [
				    'inscription' => $inscription,
				    'status' => $status,
			    ]);
		    }
	    }
    }
}

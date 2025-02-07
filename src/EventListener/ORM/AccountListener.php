<?php

namespace App\EventListener\ORM;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Html2Text\Html2Text;
use App\Entity\Core\AbstractTrainee;
use Symfony\Component\DependencyInjection\Container;

/**
 * This listener :
 *  - manipulate metadata
 *  - encode and save the password if a new plain password has been set
 *  - generate new password and send credentials to the trainee if the property sendCredentialsEmail has been set to true.
 */
final class AccountListener implements EventSubscriber
{
    public function __construct(protected Container $container)
    {
    }

    /**
     * Returns hash of events, that this listener is bound to.
     *
     */
    public function getSubscribedEvents(): array
    {
        return [Events::prePersist, Events::preUpdate, Events::postPersist, Events::postUpdate];
    }

    /**
     * preProcess
     * Encode the new password.
     */
    public function preProcess($trainee, $new = false): void
    {
        if ($trainee instanceof AbstractTrainee && $trainee->getPlainPassword()) {
            $factory = $this->container->get('security.encoder_factory');
            $encoder = $factory->getEncoder($trainee);
            $trainee->setPassword($encoder->encodePassword($trainee->getPlainPassword(), $trainee->getSalt()));
        }
    }

    /**
     * @param $trainee
     * @param bool $new
     *
     * postProcess
     * Send credentials to the trainee
     */
    public function postProcess($trainee, $new = false): void
    {
        if (get_parent_class($trainee) == AbstractTrainee::class) {
            // send some mails to the trainee
            if ($trainee->isSendCredentialsMail()) {
                $this->sendCredentialsMail($trainee, $new);
            }

            if ($trainee->getSendActivationMail()) {
                $this->sendActivationMail($trainee, $new);
            }
        }
    }

    /**
     * prePersist.
     */
    public function prePersist(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $this->preProcess($lifecycleEventArgs->getEntity(), true);
    }

    /**
     * preUpdate.
     */
    public function preUpdate(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $this->preProcess($lifecycleEventArgs->getEntity(), false);
    }

    /**
     * postPersist.
     */
    public function postPersist(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $this->postProcess($lifecycleEventArgs->getEntity(), true);
    }

    /**
     * postUpdate.
     */
    public function postUpdate(LifecycleEventArgs $lifecycleEventArgs): void
    {
        $this->postProcess($lifecycleEventArgs->getEntity(), false);
    }

    /**
     * sendMail.
     */
    private function sendCredentialsMail(AbstractTrainee $trainee, $new): void
    {
        // prepare the body
        $parameters = ['trainee' => $trainee, 'password' => $trainee->getPlainPassword(), 'new' => $new, 'url' => $this->container->getParameter('front_url')];

        $template = 'welcome.html.twig';
        if ($trainee->getShibbolethPersistentId()) {
            // if shibboleth, send special message
            $template = 'welcome.shibboleth.html.twig';
        }

        $body = $this->container->get('templating')->render('trainee/'.$template, $parameters);

        // send the mail
        $message = \Swift_Message::newInstance(null, null, 'text/html', null)
          ->setFrom($this->container->getParameter('mailer_from'), $trainee->getOrganization()->getName())
          ->setReplyTo($trainee->getOrganization()->getEmail())
          ->setSubject('Bienvenue sur la plateforme SYGEFOR !')
          ->setTo($trainee->getEmail())
          ->setBody($body);
        $message->addPart(Html2Text::convert($message->getBody()), 'text/plain');
        $this->container->get('mailer')->send($message);
        $trainee->setSendCredentialsMail(false);
    }

    /**
     * sendMail.
     */
    private function sendActivationMail(AbstractTrainee $trainee, $new): void
    {
        $options = $trainee->getSendActivationMail();

        // generate token & url
        $token = hash('sha256', $trainee->getId());
        $params = ['id' => $trainee->getId(), 'token' => $token, 'email' => $trainee->getEmail()];
        if (!empty($options['redirect'])) {
            $params['redirect'] = $options['redirect'];
        }

        $url = $this->container->get('router')->generate('api.account.activate', $params, true);

        // prepare the body
        $parameters = ['trainee' => $trainee, 'new' => $new, 'url' => $url];

        // generate body
        $body = $this->container->get('templating')->render('trainee/activation.html.twig', $parameters);

        // send the mail
        $message = \Swift_Message::newInstance(null, null, 'text/html', null)
          ->setFrom($this->container->getParameter('mailer_from'), $trainee->getOrganization()->getName())
          ->setReplyTo($trainee->getOrganization()->getEmail())
          ->setSubject('SYGEFOR : Activation de votre compte')
          ->setTo($trainee->getEmail())
          ->setBody($body);
        $message->addPart(Html2Text::convert($message->getBody()), 'text/plain');

        $this->container->get('mailer')->send($message);
        $trainee->setSendActivationMail(false);
    }
}

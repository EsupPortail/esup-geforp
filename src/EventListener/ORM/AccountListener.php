<?php

namespace App\EventListener\ORM;

use App\Entity\Core\AbstractTrainee;
use Doctrine\Common\EventSubscriber;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Mime\Address;
use Twig\Environment;
use Html2Text\Html2Text;

/**
 * This listener :
 *  - manipulate metadata
 *  - encode and save the password if a new plain password has been set
 *  - generate new password and send credentials to the trainee if the property sendCredentialsEmail has been set to true.
 */
final class AccountListener implements EventSubscriber
{
    public function __construct(private readonly MailerInterface $mailer,
                                private readonly Environment $twig,
                                private readonly UrlGeneratorInterface $router,
                                private readonly string $frontUrl,
                                private readonly string $mailerFrom)
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


    /**
     * preUpdate.
     */


    /**
     * postPersist.
     */


    /**
     * postUpdate.
     */


    /**
     * sendMail.
     */
    public function sendCredentialsMail(AbstractTrainee $trainee, bool $new): void
    {
        $template = $trainee->getShibbolethpersistentid()
            ? 'trainee/welcome.shibboleth.html.twig'
            : 'trainee/welcome.html.twig';

        $params = [
            'trainee' => $trainee,
            'password' => $trainee->getPlainPassword(),
            'new' => $new,
            'url' => $this->frontUrl,
        ];

        $htmlBody = $this->twig->render($template, $params);
        $textBody = (new Html2Text($htmlBody))->getText();

        $email = (new Email())
            ->from(new Address($this->mailerFrom, $trainee->getOrganization()->getName()))
            ->replyTo($trainee->getOrganization()->getEmail())
            ->to($trainee->getEmail())
            ->subject('Bienvenue sur la plateforme SYGEFOR !')
            ->text($textBody)
            ->html($htmlBody);

        $this->mailer->send($email);
        $trainee->setSendCredentialsMail(false);
    }

    /**
     * sendMail.
     */
    public function sendActivationMail(AbstractTrainee $trainee, bool $new): void
    {
        $options = $trainee->getSendActivationMail();

        $params = [
            'id' => $trainee->getId(),
            'token' => hash('sha256', $trainee->getId()),
            'email' => $trainee->getEmail(),
        ];

        if (!empty($options['redirect'])) {
            $params['redirect'] = $options['redirect'];
        }

        $url = $this->router->generate('api.account.activate', $params, UrlGeneratorInterface::ABSOLUTE_URL);

        $templateVars = [
            'trainee' => $trainee,
            'new' => $new,
            'url' => $url,
        ];

        $htmlBody = $this->twig->render('trainee/activation.html.twig', $templateVars);
        $textBody = (new Html2Text($htmlBody))->getText();

        $email = (new Email())
            ->from(new Address($this->mailerFrom, $trainee->getOrganization()->getName()))
            ->replyTo($trainee->getOrganization()->getEmail())
            ->to($trainee->getEmail())
            ->subject('SYGEFOR : Activation de votre compte')
            ->text($textBody)
            ->html($htmlBody);

        $this->mailer->send($email);
        $trainee->setSendActivationMail(false);
    }
}

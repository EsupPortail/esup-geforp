<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 12/06/14
 * Time: 18:13.
 */

namespace App\BatchOperations\Generic;

use Doctrine\ORM\EntityManager;
use App\Entity\Core\User;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Core\Term\PresenceStatus;
use App\Entity\Core\AbstractOrganization;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use App\Entity\Core\Term\InscriptionStatus;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\BatchOperations\AbstractBatchOperation;
use App\BatchOperations\AttachEmailPublipostAttachment;
use App\Utils\HumanReadable\HumanReadablePropertyAccessor;

class EmailingBatchOperation extends AbstractBatchOperation
{
    use AttachEmailPublipostAttachment;

    /** @var ContainerBuilder $container */
    protected $container;

    protected $targetClass = AbstractTrainee::class;

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
	    if (!is_array($targetEntities)) {
		    $targetEntities = [$targetEntities];
	    }
	    if (empty($targetEntities)) {
		    return null;
	    }

	    // check if user has access
	    // check trainee proxy for inscription checkout
	    if (isset($options['typeUser']) && get_parent_class($options['typeUser']) !== AbstractTrainee::class) {
		    foreach ($targetEntities as $key => $user) {
			    if (!$this->isGranted('VIEW', $user)) {
				    unset($targetEntities[$key]);
			    }
		    }
	    }

	    if (isset($options['preview']) && $options['preview']) {
		    return $this->getPreviewMessage(
			    $targetEntities,
			    isset($options['subject']) ? $options['subject'] : '',
			    isset($options['cc']) ? $options['cc'] : array(),
			    isset($options['additionalCC']) ? $options['additionalCC'] : array(),
			    isset($options['message']) ? $options['message'] : ''
		    );
	    }

	    return $this->sendEmails(
		    $targetEntities,
		    isset($options['subject']) ? $options['subject'] : '',
		    isset($options['cc']) ? $options['cc'] : array(),
		    isset($options['additionalCC']) ? $options['additionalCC'] : array(),
		    isset($options['message']) ? $options['message'] : '',
		    isset($options['forceEmailSending']) ? $options['forceEmailSending'] : false,
		    isset($options['templateAttachments']) ? $options['templateAttachments'] : null,
		    (isset($options['attachment'])) ? $options['attachment'] : null,
		    isset($options['organization']) ? $options['organization'] : null,
		    isset($options['notification_template']) ? $options['notification_template'] : 'batch.email',
		    isset($options['additionalParams']) ? $options['additionalParams'] : []
	    );
    }

	/**
	 * Parses subject and body content according to entity, and sends the mail.
	 * WARNING / an $em->clear() is done if there is more than one entity.
	 *
	 * @param $entities
	 * @param $subject
	 * @param $cc
	 * @param $additionalCC
	 * @param $body
	 * @param $forceEmailSending
	 * @param $templateAttachments
	 * @param $attachments
	 * @param $organization
	 * @param $notificationTemplate
	 * @param $additionalParams
	 */
	public function sendEmails($entities, $subject, $cc, $additionalCC, $body, $forceEmailSending, $templateAttachments, $attachments, $organization, $notificationTemplate = 'batch.email', $additionalParams = [])
	{
		/** @var EntityManager $em */
		$em = $this->container->get('doctrine.orm.entity_manager');
		$doClear = isset($additionalParams['storeEmail']) ? $additionalParams['storeEmail'] : true;
		$nbrEmails = 0;
		$batch = 500;
		if ($doClear) {
			$em->clear();
		}
		$userOrg = ($organization ? (is_int($organization) ? $organization : $organization->getId()) : ($this->getUser() ? $this->getUser()->getOrganization()->getId() : null));
		if ($userOrg) {
			$organization = $em->getRepository(AbstractOrganization::class)->find($userOrg);
		}
		foreach ($entities as $key => $entity) {
			$entity = $em->getRepository(get_class($entity))->find($entity->getId());

			// ignore for trainers and inscriptions
			$sendEmail = true;

			// used only from trainee list
			if ($entity instanceof AbstractTrainee) {
				$sendEmail = $forceEmailSending ? true : $entity->getNewsletter();
			}

			// Find email BCC
			$copies = $this->findCCRecipients($entity, $cc);
			if (!empty($copies) || !empty($additionalCC)) {
				$additionalParams['CC'] = [];
				foreach ($copies[0] as $keyCopy => $copy) {
					$additionalParams['CC'][] = [
						'Name' => isset($copies[1][$keyCopy]) ? $copies[1][$keyCopy] : null,
						'To' => $copy
					];
				}
				foreach ($this->additionalCCToArray($additionalCC) as $email => $send) {
					if ($send && filter_var($email, FILTER_VALIDATE_EMAIL)) {
						$additionalParams['CC'][] = [
							'Name' => null,
							'To' => $email
						];
					}
				}
			}

			if (method_exists($entity, 'isArchived')) {
				$sendEmail = $sendEmail ? !$entity->isArchived() : $sendEmail;
			}

			$sentEmails = 0;
			if ($sendEmail) {
				$additionalParams = array_merge($additionalParams, [
					'organization' => ($organization ? $organization->getId() : null),
					'templateAttachments' => $templateAttachments,
					'attachments' => $attachments,
					'send' => (($nbrEmails !== 0 && $nbrEmails % $batch === 0) || count($entities) === $key + 1),
				]) ;
				$sentEmails = $this->container->get('notification.mailer')->send(
					$notificationTemplate,
					$entity,
					[
						'subject' => $this->replaceTokens($subject, $entity),
						'body' => $this->replaceTokens($body, $entity),
						'additionalParams' => $additionalParams,
					]
				);
				$nbrEmails += $sentEmails;
			}
			if ($doClear && $sentEmails > 0) {
				$em->clear();
				$organization = ($userOrg ? $em->getRepository(AbstractOrganization::class)->find($userOrg) : null);
			}
		}
		if ($doClear) {
			$em->flush();
			$em->clear();
		}

		return $nbrEmails;
	}

	/**
	 * @param array $options
	 *
	 * @return array configuration element for scss-end modal window
	 *
	 * @throws \Exception
	 */
	public function getModalConfig($options = array())
	{
		$templateTerm = $this->container->get('sygefor_core.vocabulary_registry')->getVocabularyById('sygefor_core.vocabulary_email_template');
		/** @var EntityManager $em */
		$em = $this->container->get('doctrine.orm.entity_manager');
		$repo = $em->getRepository(get_class($templateTerm));

		if (!empty($options['inscriptionStatus'])) {
			$repoInscriptionStatus = $em->getRepository(InscriptionStatus::class);
			$inscriptionStatus = $repoInscriptionStatus->findById($options['inscriptionStatus']);
			$templates = $repo->findBy([
				'inscriptionStatus' => $inscriptionStatus,
				'organization' => $this->getUser()->getOrganization(),
			]);
		}
		else if (!empty($options['presenceStatus'])) {
			$repoPresenceStatus = $em->getRepository(PresenceStatus::class);
			$presenceStatus = $repoPresenceStatus->findById($options['presenceStatus']);
			$templates = $repo->findBy([
				'presenceStatus' => $presenceStatus,
				'organization' => $this->getUser()->getOrganization(),
			]);
		}
		else { // if no presence/inscription status is found, we get all organization templates
			$templates = $repo->findBy([
				'organization' => $this->getUser()->getOrganization(),
				'presenceStatus' => null,
				'inscriptionStatus' => null,
			]);
		}

		return ['templates' => $templates];
	}

	/**
	 * @param $entities
	 * @param $subject
	 * @param $body
	 * @param $templateAttachments
	 * @param $attachments
	 *
	 * @return array
	 */
	protected function getPreviewMessage($entities, $subject, $body, $templateAttachments, $attachments)
	{
		return [
			'email' => [
				'subject' => $this->replaceTokens($subject, $entities[0]),
				'message' => $this->replaceTokens($body, $entities[0]),
				'templateAttachments' => is_array($templateAttachments) && !empty($templateAttachments) ? array_map(function ($attachment) {
					return $attachment['name'];
				}, $templateAttachments) : null,
				'attachments' => $attachments,
			],
		];
	}

	/**
	 * @param $content
	 * @param $entity
	 *
	 * @return mixed
	 *
	 * @throws \Exception
	 */
    protected function replaceTokens($content, $entity)
    {
        /** @var HumanReadablePropertyAccessor $HRPA */
        $HRPA = $this->container->get('sygefor_core.human_readable_property_accessor_factory')->getAccessor($entity);

        $newContent = preg_replace_callback('/\[(.*?)\]/',
            function ($matches) use ($HRPA) {
                $property = $matches[1];

                return $HRPA->$property;
            },
            $content);

        return $newContent;
    }

	/**
	 * Get recipients email and name.
	 *
	 * @param $entity
	 * @param $ccResolvers
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
    protected function findCCRecipients($entity, $ccResolvers)
    {
        $emails = array();
        $names = array();
        $ccResolverRegistry = $this->container->get('sygefor_core.registry.email_cc_resolver');
        // guess cc emails and names
        foreach ($ccResolvers as $resolver => $send) {
            if ($send) {
                $name = $ccResolverRegistry->resolveName($resolver, $entity);
                $email = $ccResolverRegistry->resolveEmail($resolver, $entity);
                if ($email) {
                    if (is_string($email)) {
                        $emails[] = $email;
                    }
                    else if (is_array($email)) {
                        foreach ($email as $cc) {
                            $emails[] = $cc;
                        }
                    }
                    if ($name) {
                        if (is_string($name)) {
                            $names[] = $name;
                        }
                        else if (is_array($name)) {
                            foreach ($name as $cc) {
                                $names[] = $cc;
                            }
                        }
                    }
                }
            }
        }

        // do not send to bad fullName
        if (count($names) !== count($emails)) {
            $names = array();
        }

        return array($emails, $names);
    }

    /**
     * @param $additionalCC
     *
     * @return array
     */
    protected function additionalCCToArray($additionalCC)
    {
        if (is_array($additionalCC)) {
            $additionalCC = implode(';', $additionalCC);
        }

        $additionalCC = str_replace(array(' ', ','), ';', $additionalCC);
        $ccParts = explode(';', $additionalCC);
        $ccParts = array_unique($ccParts);
        $ccParts = array_filter($ccParts, function ($cc) {
            return !empty($cc);
        });

        $cc = array();
        foreach ($ccParts as $email) {
            $cc[$email] = true;
        }

        return $cc;
    }

	/**
	 * @return mixed
	 */
	protected function getUser()
	{
		return $this->container->get('doctrine.orm.entity_manager')->getRepository(User::class)->find(
			$this->container->get('security.token_storage')->getToken()->getUser()
		);
	}

	/**
	 * @param $access
	 * @param $entity
	 *
	 * @return mixed
	 */
	protected function isGranted($access, $entity)
	{
		return $this->container->get('security.context')->isGranted($access, $entity);
	}
}

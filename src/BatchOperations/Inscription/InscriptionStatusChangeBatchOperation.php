<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 28/04/14
 * Time: 10:41.
 */

namespace App\BatchOperations\Inscription;

use App\Vocabulary\VocabularyRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use App\BatchOperations\AbstractBatchOperation;
use App\Entity\Core\AbstractInscription;
use App\Entity\Core\Term\Inscriptionstatus;
use App\Entity\Core\Term\Presencestatus;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class MailingBatchOperation.
 */
class InscriptionStatusChangeBatchOperation extends AbstractBatchOperation implements ContainerAwareInterface
{
    /** @var ContainerBuilder $container */
    private $container;

    private $security;
    private $vocRegistry;

    /**
     * @var string
     */
    protected $targetClass = AbstractInscription::class;

    public function __construct(Security $security, VocabularyRegistry $vocRegistry)
    {
        $this->security = $security;
        $this->vocRegistry =$vocRegistry;
    }


    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
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
        $inscriptions = $this->getObjectList($idList);
        //$em = $this->container->get('doctrine.orm.entity_manager');
        $repoInscriptionStatus = $this->doctrine->getRepository(Inscriptionstatus::class);
        $repoPresenceStatus = $this->doctrine->getRepository(Presencestatus::class);

        $inscriptionStatus = (empty($options['inscriptionstatus'])) ? null : $repoInscriptionStatus->find($options['inscriptionstatus']);
        $presenceStatus = (empty($options['presencestatus'])) ? null : $repoPresenceStatus->find($options['presencestatus']);

        //changing status
        $arrayInscriptionsGranted = array();
        /** @var AbstractInscription $inscription */
        foreach ($inscriptions as $inscription) {
//            if ($this->container->get('security.context')->isGranted('EDIT', $inscription)) {
                //setting new inscription status
                if ($inscriptionStatus) {
                    $inscription->setInscriptionstatus($inscriptionStatus);
                } elseif ($presenceStatus) {
                    $inscription->setPresencestatus($presenceStatus);
                }
                $arrayInscriptionsGranted[] = $inscription;
//            }
        }
        $this->doctrine->getManager()->flush();

	    // if asked, a mail sent to user
	    if (isset($options['sendMail']) && ($options['sendMail'] === true) && (count($arrayInscriptionsGranted) > 0)) {
		    return $this->container->get('sygefor_core.batch.email')->sendEmails(
			    $arrayInscriptionsGranted,
			    $options['subject'],
			    isset($options['cc']) ? $options['cc'] : null,
			    isset($options['additionalCC']) ? $options['additionalCC'] : null,
			    $options['message'],
			    true,
			    isset($options['templateAttachments']) ? $options['templateAttachments'] : array(),
			    isset($options['attachment']) ? $options['attachment'] : array(),
			    null
		    );
	    }

	    return count($arrayInscriptionsGranted);
    }

    /**
     * @param $options
     *
     * @return array
     */
    public function getModalConfig($options = array())
    {
        $userOrg = $this->security->getUser()->getOrganization();
        $templateTerm = $this->vocRegistry->getVocabularyById(5); // vocabulary_email_template
        $attachmentTerm = $this->vocRegistry->getVocabularyById(1); //vocabulary_publipost_template

        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        /** @var EntityRepository $repo */
        $repo = $em->getRepository(get_class($templateTerm));
        $attRepo = $em->getRepository(get_class($attachmentTerm));

        if (!empty($options['inscriptionstatus'])) {
            $repoInscriptionStatus = $em->getRepository(Inscriptionstatus::class);
            $inscriptionStatus = $repoInscriptionStatus->findById($options['inscriptionstatus']);
            $findCriteria = array('inscriptionstatus' => $inscriptionStatus);
            if ($userOrg) {
                $findCriteria['organization'] = $userOrg;
            }
            $templates = $repo->findBy($findCriteria);
        }
        else if (!empty($options['presencestatus'])) {
            $repoInscriptionStatus = $em->getRepository(Presencestatus::class);
            $presenceStatus = $repoInscriptionStatus->findById($options['presencestatus']);
            $findCriteria = array('presencestatus' => $presenceStatus);
            if ($userOrg) {
                $findCriteria['organization'] = $userOrg;
            }
            $templates = $repo->findBy($findCriteria);
        }
        else {
            $templates = $repo->findBy(array('inscriptionstatus' => null, 'presencestatus' => null));
        }
        $attTemplates = $attRepo->findBy(array('organization' => $userOrg ? $userOrg : ''));

        return array(
            'ccResolvers' => null, //$this->container->get('sygefor_core.registry.email_cc_resolver')->getSupportedResolvers($options['targetClass']),
            'templates' => $templates,
            'attachmentTemplates' => $attTemplates,
        );
    }
}

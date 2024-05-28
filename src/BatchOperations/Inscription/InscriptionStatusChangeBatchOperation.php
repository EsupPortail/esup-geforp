<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 28/04/14
 * Time: 10:41.
 */

namespace App\BatchOperations\Inscription;

use App\BatchOperations\BatchOperationRegistry;
use App\BatchOperations\Generic\EmailingBatchOperation;
use App\BatchOperations\Generic\MailingBatchOperation;
use App\Vocabulary\VocabularyRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use App\BatchOperations\AbstractBatchOperation;
use App\Entity\Core\AbstractInscription;
use App\Entity\Term\Inscriptionstatus;
use App\Entity\Term\Presencestatus;
use App\Entity\Back\DateSession;
use App\Entity\Back\Presence;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\File;
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
    private $emailBatch;
    private $mailingBatch;

    /**
     * @var string
     */
    protected $targetClass = AbstractInscription::class;

    public function __construct(Security $security, VocabularyRegistry $vocRegistry, EmailingBatchOperation $emailBatch, MailingBatchOperation $mailingBatch)
    {
        $this->security = $security;
        $this->vocRegistry =$vocRegistry;
        $this->emailBatch = $emailBatch;
        $this->mailingBatch = $mailingBatch;
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
                $inscription->setUpdatedAt(new \DateTime('now'));
            } elseif ($presenceStatus) {
                $inscription->setPresencestatus($presenceStatus);
                $inscription->setUpdatedAt(new \DateTime('now'));
            }

            $arrayInscriptionsGranted[] = $inscription;
//            }
        }

        if ($presenceStatus || isset($options['presencestatus'])) {
            // Si le statut de présence est passé à Absent, Présent ou Partiel, on remplit automatiquement le tableau des présences
            if (($presenceStatus->getName() == "Présent") || ($presenceStatus->getName() == "Absent") || ($presenceStatus->getName() == "Partiel")) {
                foreach ($inscriptions as $inscription) {
                    $session = $inscription->getSession();
                    $nbPres = count($inscription->getPresences());

                    // Si on a déjà rempli un tableau de présence, on met à jour seulement les statuts, sinon on crée le tableau complet
                    if ($nbPres < 1) {
                        foreach ($session->getDates() as $date) {
                            // Test sur le nombre de jours à afficher
                            $dateDeb = $date->getDatebegin();
                            $dateFin = $date->getDateend();
                            $diff = $dateDeb->diff($dateFin)->format('%a');

                            for ($j = 0; $j < $diff + 1; $j++) {
                                $dateDeb2 = clone $dateDeb;
                                $dateDeb2->modify("+$j days");
                                $presence = new Presence();
                                $presence->setDatebegin($dateDeb2);
                                if ($date->getSchedulemorn() != null)
                                    if ($presenceStatus->getName() != "Partiel") {
                                        $presence->setMorning($presenceStatus->getName());
                                    } else {
                                        $presence->setMorning("Présent");
                                    }
                                else
                                    $presence->setMorning("");
                                if ($date->getScheduleafter() != null)
                                    if ($presenceStatus->getName() != "Partiel") {
                                        $presence->setAfternoon($presenceStatus->getName());
                                    } else {
                                        $presence->setAfternoon("Présent");
                                    }
                                else
                                    $presence->setAfternoon("");
                                $presence->setInscription($inscription);
                                $inscription->addPresence($presence);
                                $inscription->setUpdatedAt(new \DateTime('now'));
                            }
                        }
                    } else {
                        // Récupération des présences et modif des statuts
                        foreach ($inscription->getPresences() as $presence) {
                            if ($presence->getMorning() != "")
                                if ($presenceStatus->getName() != "Partiel") {
                                    $presence->setMorning($presenceStatus->getName());
                                } else {
                                    $presence->setMorning("Présent");
                                }
                            else
                                $presence->setMorning("");
                            if ($presence->getAfternoon() != "")
                                if ($presenceStatus->getName() != "Partiel") {
                                    $presence->setAfternoon($presenceStatus->getName());
                                } else {
                                    $presence->setAfternoon("Présent");
                                }
                            else
                                $presence->setAfternoon("");
                        }
                        $inscription->setUpdatedAt(new \DateTime('now'));
                    }
                }

            }
        }
        $this->doctrine->getManager()->flush();

        //if asked, a mail sent to user
        if (isset($options['sendMail']) && ($options['sendMail'] === true) && (count($arrayInscriptionsGranted) > 0)) {
            //managing attachments
            $tablAllAttach = array();
            foreach ($arrayInscriptionsGranted as $inscription) {
                $attachments = array();

                if (isset($options['attachment'])) {
                    $attachments = $options['attachment'];
                }

                if ($options['attachmentTemplates']) {
                    $repo = $this->doctrine->getRepository('App\Entity\Term\Publiposttemplate');
                    foreach ($options['attachmentTemplates'] as $tplId) {
                        $tpl           = $repo->find($tplId);
                        $attachments[] = $this->mailingBatch->parseFile($tpl->getFile(), array($inscription), true, $tpl->getFileName(), true);
                    }
                }
                foreach($attachments as $att)
                    $tabAllAttach[] = $att;

                //sending with e-mail service
                $this->emailBatch->parseAndSendMail($inscription, $options['subject'], $options['message'], $attachments, (isset($options['preview'])) ? $options['preview'] : false, isset($options['ical']) ? $options['ical'] : false, isset($options['format']) ? $options['format'] : 0);

            }

            if ((isset($tabAllAttach)) && ($tabAllAttach != null)) {
                foreach ($tabAllAttach as $att) {
                    if (file_exists($att->getPathname()))
                        unlink($att->getPathname());
                }
            }

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
        $repo = $this->doctrine->getRepository(get_class($templateTerm));
        $attRepo = $this->doctrine->getRepository(get_class($attachmentTerm));

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

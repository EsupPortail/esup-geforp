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
use JetBrains\PhpStorm\NoReturn;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use function PHPUnit\Framework\isEmpty;

/**
 * Class MailingBatchOperation.
 */
final class InscriptionStatusChangeBatchOperation extends AbstractBatchOperation
{
    /**
     * @var string
     */
    protected string $targetClass = AbstractInscription::class;

    public function __construct(private readonly Security $Security, private readonly VocabularyRegistry $vocabularyRegistry, private readonly EmailingBatchOperation $emailingBatchOperation, private readonly MailingBatchOperation $mailingBatchOperation)
    {
        parent::__construct();
    }

    public function execute(array $idList = [], array $options = []): int
    {
        $inscriptions = $this->getObjectList($idList);
        //$em = $this->container->get('doctrine.orm.entity_manager');
        $repoInscriptionStatus = $this->doctrine->getRepository(Inscriptionstatus::class);
        $repoPresenceStatus = $this->doctrine->getRepository(Presencestatus::class);

        $inscriptionStatus = (empty($options['inscriptionstatus'])) ? null : $repoInscriptionStatus->find($options['inscriptionstatus']);
        $presenceStatus = (empty($options['presencestatus'])) ? null : $repoPresenceStatus->find($options['presencestatus']);

        //changing status
        $arrayInscriptionsGranted = [];
        /** @var AbstractInscription $inscription */
        foreach ($inscriptions as $inscription) {
//            if ($this->container->get('security.context')->isGranted('EDIT', $inscription)) {
                //setting new inscription status
            if ($inscriptionStatus instanceof \App\Entity\Term\Inscriptionstatus) {
                $inscription->setInscriptionstatus($inscriptionStatus);
                $inscription->setUpdatedAt(new \DateTime('now'));
            } elseif ($presenceStatus instanceof \App\Entity\Term\Presencestatus) {
                $inscription->setPresencestatus($presenceStatus);
                $inscription->setUpdatedAt(new \DateTime('now'));
            }

            $arrayInscriptionsGranted[] = $inscription;
//            }
        }

        // Si le statut de présence est passé à Absent, Présent ou Partiel, on remplit automatiquement le tableau des présences
        if (($presenceStatus instanceof \App\Entity\Term\Presencestatus || isset($options['presencestatus'])) && (($presenceStatus->getName() == "Présent") || ($presenceStatus->getName() == "Absent") || ($presenceStatus->getName() == "Partiel"))) {
            foreach ($inscriptions as $inscription) {
                $session = $inscription->getSession();
                $nbPres = is_countable($inscription->getPresences()) ? count($inscription->getPresences()) : 0;

                // Si on a déjà rempli un tableau de présence, on met à jour seulement les statuts, sinon on crée le tableau complet
                if ($nbPres < 1) {
                    foreach ($session->getDates() as $date) {
                        // Test sur le nombre de jours à afficher
                        $dateDeb = $date->getDatebegin();
                        $dateFin = $date->getDateend();
                        $diff = $dateDeb->diff($dateFin)->format('%a');

                        for ($j = 0; $j < $diff + 1; ++$j) {
                            $dateDeb2 = clone $dateDeb;
                            $dateDeb2->modify(sprintf('+%d days', $j));
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

        $this->doctrine->getManager()->flush();

        //if asked, a mail sent to user
        if (isset($options['sendMail']) && ($options['sendMail'] === true) && ($arrayInscriptionsGranted !== [])) {
            foreach ($arrayInscriptionsGranted as $arrayInscriptionGranted) {
                $attachments = [];

                if (isset($options['attachment'])) {
                    $attachments = $options['attachment'];
                }

                if ($options['attachmentTemplates']) {
                    $repo = $this->doctrine->getRepository(\App\Entity\Term\Publiposttemplate::class);
                    foreach ($options['attachmentTemplates'] as $tplId) {
                        $tpl           = $repo->find($tplId);
                        $attachments[] = $this->mailingBatchOperation->parseFile($tpl->getFile(), [$arrayInscriptionGranted], true, $tpl->getFileName(), true);
                    }
                }

                foreach($attachments as $att)
                    $tabAllAttach[] = $att;

                //sending with e-mail service
                $this->emailingBatchOperation->parseAndSendMail($arrayInscriptionGranted, $options['subject'], $options['message'], $attachments, $options['preview'] ?? false, $options['ical'] ?? false, $options['format'] ?? 0);

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
     * @param array $options
     *
     * @return array{ccResolvers: null, templates: object[], attachmentTemplates: \App\Vocabulary\VocabularyInterface[]}
     */
    public function getModalConfig($options = []): array
    {
        $token = $this->Security->getToken();
        $user = $token?->getUser();
        $userOrg = null;

        if ($user && method_exists($user, 'getOrganization')) {
            $userOrg = $user->getOrganization()->getId();
        }

        $templateTerm = $this->vocabularyRegistry->getVocabularyById(5); // vocabulary_email_template
        $attachmentTerm = $this->vocabularyRegistry->getVocabularyById(1); //vocabulary_publipost_template

        /** @var EntityManager $em */
        $em = $this->doctrine->getManager();

        /** @var EntityRepository $repo */
        $repo = $this->doctrine->getRepository($templateTerm::class);
        $attRepo = $this->doctrine->getRepository($attachmentTerm::class);

        if (!empty($options['inscriptionstatus'])) {
            $repoInscriptionStatus = $em->getRepository(Inscriptionstatus::class);
            $inscriptionStatus = $repoInscriptionStatus->find($options['inscriptionstatus']);
            $findCriteria = ['inscriptionstatus' => $inscriptionStatus];
            if (!isEmpty($userOrg)) {
                $findCriteria['organization'] = $userOrg;
            }

            $templates = $repo->findBy($findCriteria);
        } elseif (!empty($options['presencestatus'])) {
            $repoInscriptionStatus = $em->getRepository(Presencestatus::class);
            $presenceStatus = $repoInscriptionStatus->find($options['presencestatus']);
            $findCriteria = ['presencestatus' => $presenceStatus];
            if (!isEmpty($userOrg)) {
                $findCriteria['organization'] = $userOrg;
            }

            $templates = $repo->findBy($findCriteria);
        } else {
            $templates = $repo->findBy(['inscriptionstatus' => null, 'presencestatus' => null]);
        }

        $attTemplates = $attRepo->findBy(['organization' => $userOrg ?: '']);

        return [
            'ccResolvers' => null,
            //$this->container->get('sygefor_core.registry.email_cc_resolver')->getSupportedResolvers($options['targetClass']),
            'templates' => $templates,
            'attachmentTemplates' => $attTemplates,
        ];
    }

}

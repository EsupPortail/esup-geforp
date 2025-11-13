<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 07/04/14
 * Time: 16:56.
 */

namespace App\BatchOperations\Generic;

use App\Entity\Back\Session;
use Doctrine\ORM\EntityManager;
use App\BatchOperations\AbstractBatchOperation;
use App\Entity\Core\User;
use Doctrine\ORM\Query\ResultSetMapping;
use App\Entity\Core\AbstractSession;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Bundle\SecurityBundle\Security;
use League\Csv\Writer;
use League\Csv\CharsetConverter;

/**
 * Class CSVBatchOperation.
 */
class CSVBatchOperation extends AbstractBatchOperation
{
    /**
     * @var array
     */
    protected array $options;

    // Création de la requête de récupération des tags
    /**
     * @var string
     */
    private const string SQL = <<<SQL
        SELECT t.name
FROM tag t
JOIN training__training_tag i_t ON i_t.tag_id = t.id
JOIN training train ON train.id = i_t.training_id
WHERE train.id = :trainingId 
SQL;

    public function __construct(protected Security $security)
    {
        parent::__construct();
        $this->options['volcanus_config'] = ['delimiter' => ';', 'enclose' => true, 'enclosure' => '"', 'escape' => '"', 'inputEncoding' => 'UTF-8', 'outputEncoding' => 'ISO-8859-1', 'writeHeaderLine' => true, 'responseFilename' => 'export.csv'];
        $this->options['tempDir'] = sys_get_temp_dir() . '/sygefor/';
        if (!file_exists($this->options['tempDir'])) {
            mkdir($this->options['tempDir'], 0777);
        }
    }

    /**
     *
     * @return array{fileUrl: string}
     */
    public function execute(array $idList = [], array $options = []): array
    {
        $entities = $this->getObjectList($idList);
        // accessor
        $propertyAccessor = PropertyAccess::createPropertyAccessor();


        // lines
        $lines = [];
        foreach ($entities as $entity) {
//            if (!$this->securityContext->getToken()->getUser() instanceof User || $this->securityContext->isGranted('VIEW', $entity)) {
            $data = [];
            foreach ($this->options['fields'] as $key => $value) {
                try {
                    // Cas particuliers
                    // Calcul des stats d'inscriptions pour les sessions
                    if ($key == "inscription.listeatt") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', (string) $key);

                        $statsListe = [];
                        /** @var EntityManager $em */
                        $em    = $this->doctrine->getManager();
                        $session = $entity;
                        if($session->getRegistration() > AbstractSession::REGISTRATION_DEACTIVATED) {
                            $query = $em
                                ->createQuery('SELECT s, count(i) FROM App\Entity\Term\Inscriptionstatus s
                    JOIN App\Entity\Core\AbstractInscription i WITH i.inscriptionstatus = s
                    WHERE i.session = :session and s.machinename = :status
                    GROUP BY s.id')
                                ->setParameter('session', $session)
                                ->setParameter('status', "waitinglist");

                            $result = $query->getResult();
                            foreach($result as $status) {
                                $statsListe[] = ['id'     => $status[0]->getId(), 'name'   => $status[0]->getName(), 'status' => $status[0]->getStatus(), 'count'  => (int) $status[1]];
                            }

                            $data[$key] = $statsListe[0]['count'] ?? '';
                        } else {
                            $data[$key] = '';
                        }
                    } elseif ($key == "inscription.refus") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', (string) $key);

                        $statsRefus = [];
                        /** @var EntityManager $em */
                        $em    = $this->doctrine->getManager();
                        $session = $entity;
                        if($session->getRegistration() > AbstractSession::REGISTRATION_DEACTIVATED) {
                            $query = $em
                                ->createQuery('SELECT s, count(i) FROM App\Entity\Term\Inscriptionstatus s
                    JOIN App\Entity\Core\AbstractInscription i WITH i.inscriptionstatus = s
                    WHERE i.session = :session and s.machinename = :status
                    GROUP BY s.id')
                                ->setParameter('session', $session)
                                ->setParameter('status', "refuse");

                            $result = $query->getResult();
                            foreach($result as $status) {
                                $statsRefus[] = ['id'     => $status[0]->getId(), 'name'   => $status[0]->getName(), 'status' => $status[0]->getStatus(), 'count'  => (int) $status[1]];
                            }

                            $data[$key] = $statsRefus[0]['count'] ?? '';
                        } else {
                            $data[$key] = '';
                        }
                    } elseif ($key == "inscription.desist") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', (string) $key);

                        $statsListe = [];
                        /** @var EntityManager $em */
                        $em    = $this->doctrine->getManager();
                        $session = $entity;
                        if($session->getRegistration() > AbstractSession::REGISTRATION_DEACTIVATED) {
                            $query = $em
                                ->createQuery('SELECT s, count(i) FROM App\Entity\Term\Inscriptionstatus s
                    JOIN App\Entity\Core\AbstractInscription i WITH i.inscriptionstatus = s
                    WHERE i.session = :session and s.machinename = :status
                    GROUP BY s.id')
                                ->setParameter('session', $session)
                                ->setParameter('status', "desist");

                            $result = $query->getResult();
                            foreach($result as $status) {
                                $statsListe[] = ['id'     => $status[0]->getId(), 'name'   => $status[0]->getName(), 'status' => $status[0]->getStatus(), 'count'  => (int) $status[1]];
                            }

                            $data[$key] = $statsListe[0]['count'] ?? '';
                        } else {
                            $data[$key] = '';
                        }
                    } elseif ($key == "inscription.convoke") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', (string) $key);

                        $statsRefus = [];
                        /** @var EntityManager $em */
                        $em    = $this->doctrine->getManager();
                        $session = $entity;
                        if($session->getRegistration() > AbstractSession::REGISTRATION_DEACTIVATED) {
                            $query = $em
                                ->createQuery('SELECT s, count(i) FROM App\Entity\Term\Inscriptionstatus s
                    JOIN App\Entity\Back\Inscription i WITH i.inscriptionstatus = s
                    WHERE i.session = :session and s.machinename = :status
                    GROUP BY s.id')
                                ->setParameter('session', $session)
                                ->setParameter('status', "convoke");

                            $result = $query->getResult();

                            foreach($result as $status) {
                                $statsRefus[] = ['id'     => $status[0]->getId(), 'name'   => $status[0]->getName(), 'status' => $status[0]->getStatus(), 'count'  => (int) $status[1]];
                            }

                            $data[$key] = $statsRefus[0]['count'] ?? '';
                        } else {
                            $data[$key] = '';
                        }
                    } elseif ($key == "inscription.presence.nbheures") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', (string) $key);

                        $statsNbHeures = [];
                        /** @var EntityManager $em */
                        $em = $this->doctrine->getManager();
                        $session = $entity;
                        if ($session->getRegistration() > AbstractSession::REGISTRATION_DEACTIVATED) {

                            // On commence par recuperer les inscriptions de la session avec le statut "Convoqué"
/*                            $inscConv = array();
                            $query = $em
                                ->createQuery('SELECT i, count(s) FROM App\Entity\Core\AbstractInscription i
                    JOIN App\Entity\Core\Term\Inscriptionstatus s WITH s = i.inscriptionstatus
                    WHERE i.session = :session and s.machinename = :status
                    GROUP BY i.id')
                                ->setParameter('session', $session)
                                ->setParameter('status', "convoke");
                            $tabInsc = $query->getResult();*/

                            // On commence par recuperer les inscriptions de la session avec le statut "Présent" ou "Présence partielle"
                            $query = $em
                                ->createQuery('SELECT i, count(s) FROM App\Entity\Core\AbstractInscription i
                    JOIN App\Entity\Term\Presencestatus s WITH s = i.presencestatus
                    WHERE i.session = :session and (s.machinename = :statuspres or s.machinename = :statusprespart)
                    GROUP BY i.id')
                                ->setParameter('session', $session)
                                ->setParameter('statuspres', "present")
                                ->setParameter('statusprespart', "partiel");
                            $tabInsc = $query->getResult();

                            // On recupere le tableau des dates de la session avec le nombre d'heures matin et après-midi
                            $query = $em
                                ->createQuery('SELECT d FROM App\Entity\Back\DateSession d
                    WHERE d.session = :session')
                                ->setParameter('session', $session);
                            $tabDatesSes = $query->getResult();

                            // On initialise le nombre d'heures de présence
                            $nbHeuresPresence = 0;

                            // On parcourt le tableau des inscriptions
                            foreach ($tabInsc as $insc) {
                                // On récupère le tableau des présences pour chaque inscription
                                $query = $em
                                    ->createQuery('SELECT p FROM App\Entity\Back\Presence p
                    WHERE p.inscription = :inscription')
                                    ->setParameter('inscription', $insc[0]);
                                $tabPresences = $query->getResult();

                                // Pour chaque presence, on compare avec le tableau des dates et on calcule le nombre d'heures
                                foreach ($tabPresences as $pres) {
                                    foreach ($tabDatesSes as $dateSes)
                                        if ($pres->getDatebegin() == $dateSes->getDatebegin()) {
                                            if ($pres->getMorning() == "Présent") {
                                                $nbHeuresPresence += $dateSes->getHournumbermorn();
                                            }

                                            if ($pres->getAfternoon() == "Présent") {
                                                $nbHeuresPresence += $dateSes->getHournumberafter();
                                            }

                                            break;
                                        }
                                }
                            }

                            // On recupere seulement le compteur du nombre d'heures de présence
                            $data[$key] = $nbHeuresPresence;
                        } else {
                            $data[$key] = '';
                        }
                    }elseif ($key == "session.presence.nbheures") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', (string) $key);

                        $statsPresPart = [];
                        /** @var EntityManager $em */
                        $em    = $this->doctrine->getManager();
                        $session = $entity;
                        if($session->getRegistration() > AbstractSession::REGISTRATION_DEACTIVATED) {
                            $query = $em
                                ->createQuery('SELECT s, count(i) FROM App\Entity\Term\Presencestatus s
                    JOIN App\Entity\Core\AbstractInscription i WITH i.presencestatus = s
                    WHERE i.session = :session and (s.machinename = :present or s.machinename = :partiel)
                    GROUP BY s.id')
                                ->setParameter('session', $session)
                                ->setParameter('present', "present")
                                ->setParameter('partiel', "partiel");

                            $nbPresPart = 0;
                            $result = $query->getResult();
                            foreach($result as $status) {
                                $statsPresPart[] = ['id'     => $status[0]->getId(), 'name'   => $status[0]->getName(), 'status' => $status[0]->getStatus(), 'count'  => (int) $status[1]];
                                $nbPresPart +=  (int) $status[1];
                            }

                            // On recupere le nombre de présences totales et partielles * nombre d'heures théoriques
                            $data[$key] = $nbPresPart * $propertyAccessor->getValue($session, 'hournumber');

                            // Transformation '.' en ',' pour faciliter Excel
                            $data[$key] = str_replace('.', ',', $data[$key]);
                        } else {
                            $data[$key] = '';
                        }
                    } elseif ($key == "presence.nbheures") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', (string) $key);

                        $statsNbHeures = [];
                        /** @var EntityManager $em */
                        $em = $this->doctrine->getManager();
                        $inscription = $entity;
                        $session = $inscription->getSession();

                        // on regarde si l'inscription est bien dans un statut convoqué // DEMANDE DRH : NE PLUS VERIFIER
//                        if($inscription->getInscriptionstatus()->getMachinename() == "convoke") {

                        // on regarde si l'inscription est bien dans un statut présent ou partiel
                        if (($inscription->getPresencestatus()) && (($inscription->getPresencestatus()->getMachinename() == "present") || ($inscription->getPresencestatus()->getMachinename() == "partiel"))) {
                            // On recupere le tableau des dates de la session avec le nombre d'heures matin et après-midi
                            $query = $em
                                ->createQuery('SELECT d FROM App\Entity\Back\DateSession d
                    WHERE d.session = :session')
                                ->setParameter('session', $session);
                            $tabDatesSes = $query->getResult();

                            // On initialise le nombre d'heures de présence
                            $nbHeuresPresence = 0;

                            // On récupère le tableau des présences pour chaque inscription
                            $query = $em
                                ->createQuery('SELECT p FROM App\Entity\Back\Presence p
                    WHERE p.inscription = :inscription')
                                ->setParameter('inscription', $inscription);
                            $tabPresences = $query->getResult();

                            // Pour chaque presence, on compare avec le tableau des dates et on calcule le nombre d'heures
                            foreach($tabPresences as $tabPresence) {
                                foreach($tabDatesSes as $tabDateSe) {
                                    if ($tabPresence->getDatebegin() == $tabDateSe->getDatebegin()) {
                                        if ($tabPresence->getMorning() == "Présent") {
                                            $nbHeuresPresence += $tabDateSe->getHournumbermorn();
                                        }

                                        if ($tabPresence->getAfternoon() == "Présent") {
                                            $nbHeuresPresence += $tabDateSe->getHournumberafter();
                                        }

                                        break;
                                    }
                                }
                            }

                            // On recupere seulement le compteur du nombre d'heures de présence
                            $data[$key] = $nbHeuresPresence;

                            // Transformation '.' en ',' pour faciliter Excel
                            $data[$key] = str_replace('.', ',', $data[$key]);
                        } else {
                            $data[$key] = '';
                        }
                    } elseif ($key == "presence.partiel") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', (string) $key);

                        $statsPartiel = [];
                        /** @var EntityManager $em */
                        $em    = $this->doctrine->getManager();
                        $session = $entity;
                        if($session->getRegistration() > AbstractSession::REGISTRATION_DEACTIVATED) {
                            $query = $em
                                ->createQuery('SELECT s, count(i) FROM App\Entity\Term\Presencestatus s
                    JOIN App\Entity\Core\AbstractInscription i WITH i.presencestatus = s
                    WHERE i.session = :session and s.machinename = :status
                    GROUP BY s.id')
                                ->setParameter('session', $session)
                                ->setParameter('status', "partiel");

                            $result = $query->getResult();
                            foreach($result as $status) {
                                $statsPartiel[] = ['id'     => $status[0]->getId(), 'name'   => $status[0]->getName(), 'status' => $status[0]->getStatus(), 'count'  => (int) $status[1]];
                            }

                            $data[$key] = $statsPartiel[0]['count'] ?? '';
                        } else {
                            $data[$key] = '';
                        }
                    } elseif ($key == "presence.absent") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', (string) $key);

                        $statsAbsent = [];
                        /** @var EntityManager $em */
                        $em    = $this->doctrine->getManager();
                        $session = $entity;
                        if($session->getRegistration() > AbstractSession::REGISTRATION_DEACTIVATED) {
                            $query = $em
                                ->createQuery('SELECT s, count(i) FROM App\Entity\Term\Presencestatus s
                    JOIN App\Entity\Core\AbstractInscription i WITH i.presencestatus = s
                    WHERE i.session = :session and s.machinename = :status
                    GROUP BY s.id')
                                ->setParameter('session', $session)
                                ->setParameter('status', "absent");

                            $result = $query->getResult();
                            foreach($result as $status) {
                                $statsAbsent[] = ['id'     => $status[0]->getId(), 'name'   => $status[0]->getName(), 'status' => $status[0]->getStatus(), 'count'  => (int) $status[1]];
                            }

                            $data[$key] = $statsAbsent[0]['count'] ?? '';
                        } else {
                            $data[$key] = '';
                        }
                    } elseif ($key == "presence.excuse") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', (string) $key);

                        $statsExcuse = [];
                        /** @var EntityManager $em */
                        $em    = $this->doctrine->getManager();
                        $session = $entity;
                        if($session->getRegistration() > AbstractSession::REGISTRATION_DEACTIVATED) {
                            $query = $em
                                ->createQuery('SELECT s, count(i) FROM App\Entity\Term\Presencestatus s
                    JOIN App\Entity\Core\AbstractInscription i WITH i.presencestatus = s
                    WHERE i.session = :session and s.machinename = :status
                    GROUP BY s.id')
                                ->setParameter('session', $session)
                                ->setParameter('status', "excuse");

                            $result = $query->getResult();
                            foreach($result as $status) {
                                $statsExcuse[] = ['id'     => $status[0]->getId(), 'name'   => $status[0]->getName(), 'status' => $status[0]->getStatus(), 'count'  => (int) $status[1]];
                            }

                            $data[$key] = $statsExcuse[0]['count'] ?? '';
                        } else {
                            $data[$key] = '';
                        }
                    } elseif ($key == "trainers.fullName") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', (string) $key);

                        $statsRefus = [];
                        $liste = "";
                        /** @var EntityManager $em */
                        $em    = $this->doctrine->getManager();
                        $session = $entity;

                        $query = $em
                            ->createQuery('SELECT t FROM App\Entity\Core\AbstractTrainer t
                JOIN App\Entity\Core\AbstractParticipation p WITH p.trainer = t
                WHERE p.session = :session 
                GROUP BY t.id')
                            ->setParameter('session', $session);

                        $result = $query->getResult();
                        foreach($result as $trainer) {
                            $statsTrainer[] = ['id'     => $trainer->getId(), 'first'   => $trainer->getFirstName(), 'last' => $trainer->getLastName()];
                            $liste = $liste . $trainer->getFirstName() ." " . $trainer->getLastName() . " (". ($trainer->getIsorganization() ? "interne" : "externe") . "), ";
                        }

                        // On recupere la liste des formateurs
                        $data[$key] = $liste;

                    } elseif ($key == "totalCost") {
                        $rvalue = $propertyAccessor->getValue($entity, 'teachingcost')
                            + $propertyAccessor->getValue($entity, 'vacationcost')
                            + $propertyAccessor->getValue($entity, 'accommodationcost')
                            + $propertyAccessor->getValue($entity, 'mealcost')
                            + $propertyAccessor->getValue($entity, 'transportcost')
                            + $propertyAccessor->getValue($entity, 'materialcost');

                        $data[$key] = $rvalue ?: '';
                        // Transformation '.' en ',' pour faciliter Excel
                        $data[$key] = str_replace('.', ',', $data[$key]);
                    } elseif ($key == "session.totalCost") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', (string) $key);

                        /** @var EntityManager $em */
                        $em = $this->doctrine->getManager();
                        $inscription = $entity;
                        $session = $inscription->getSession();

                        $rvalue = $propertyAccessor->getValue($session, 'teachingcost')
                            + $propertyAccessor->getValue($session, 'vacationcost')
                            + $propertyAccessor->getValue($session, 'accommodationcost')
                            + $propertyAccessor->getValue($session, 'mealcost')
                            + $propertyAccessor->getValue($session, 'transportcost')
                            + $propertyAccessor->getValue($session, 'materialcost');

                        $data[$key] = $rvalue ?: '';
                        // Transformation '.' en ',' pour faciliter Excel
                        $data[$key] = str_replace('.', ',', (string)$data[$key]);

                    } elseif ($key == "individualcost") {
                        // Coût total
                        $totalCost = $propertyAccessor->getValue($entity, 'teachingcost')
                            + $propertyAccessor->getValue($entity, 'vacationcost')
                            + $propertyAccessor->getValue($entity, 'accommodationcost')
                            + $propertyAccessor->getValue($entity, 'mealcost')
                            + $propertyAccessor->getValue($entity, 'transportcost')
                            + $propertyAccessor->getValue($entity, 'materialcost');

                        // Nombre de stagiaires en présence partielle ou totale
                        $statsPresGlobale = array();
                        /** @var EntityManager $em */
                        $em    = $this->doctrine->getManager();
                        $session = $entity;
                        if($session->getRegistration() > AbstractSession::REGISTRATION_DEACTIVATED) {
                            $query = $em
                                ->createQuery('SELECT s, count(i) FROM App\Entity\Term\Presencestatus s
                    JOIN App\Entity\Core\AbstractInscription i WITH i.presencestatus = s
                    WHERE i.session = :session and (s.machinename = :present or s.machinename = :partiel)
                    GROUP BY s.id')
                                ->setParameter('session', $session)
                                ->setParameter('present', "present")
                                ->setParameter('partiel', "partiel");

                            $result = $query->getResult();
                            foreach($result as $status) {
                                $statsPresGlobale[] = array(
                                    'id'     => $status[0]->getId(),
                                    'name'   => $status[0]->getName(),
                                    'status' => $status[0]->getStatus(),
                                    'count'  => (int) $status[1],
                                );
                            }
                            // On recupere seulement le compteur
                            if (isset($statsPresGlobale[0]['count']))
                                $nbPresentsGlob = $statsPresGlobale[0]['count'];
                            else
                                $nbPresentsGlob = 0;
                        } else {
                            $nbPresentsGlob = 0;
                        }

                        // si on a des présents, on calcule le coût par stagiare
                        if (($nbPresentsGlob > 0) && $totalCost) {
                            $rvalue =  $totalCost/$nbPresentsGlob;
                        }

                        $data[$key] = ($rvalue) ? $rvalue : '';
                        // Transformation '.' en ',' pour faciliter Excel
                        $data[$key] = str_replace('.', ',', $data[$key]);

                    } elseif ($key == "training.tags") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', (string) $key);

                        /** @var EntityManager $em */
                        $em = $this->doctrine->getManager();
                        $session = $entity;
                        $training = $session->getTraining();
                        if (!$entity instanceof Session) {
                            $data[$key] = '';
                            continue;
                        }
                        $trainingId = $training->getId();
                        $rsm = new ResultSetMapping();
                        $rsm->addScalarResult('name', 'name');
                        $query = $em->createNativeQuery(self::SQL, $rsm);
                        $query->setParameter('trainingId', $trainingId);
                        $result = $query->getResult();

                        $tags = "";
                        foreach($result as $tag) {
                            $tags .= $tag['name'] . " ; ";
                        }

                        $rvalue = $tags;

                        $data[$key] = $rvalue ?: '';
                    } elseif ($key == "date.lieu") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', ',', (string) $key);

                        $statsNbHeures = [];
                        /** @var EntityManager $em */
                        $em = $this->doctrine->getManager();
                        $session = $entity;

                        // On recupere le tableau des dates de la session avec le nombre d'heures matin et après-midi
                        $query = $em
                            ->createQuery('SELECT d FROM App\Entity\Back\DateSession d
                                WHERE d.session = :session')
                            ->setParameter('session', $session);
                        $tabDatesSes = $query->getResult();

                        // on récupère le lieu de la première ligne du tableau de dates
                        $data[$key] = (isset($tabDatesSes[0])) &&(null !== $tabDatesSes[0]) ? $tabDatesSes[0]->getPlace() ?? '' : '';

                    } elseif ($key == "evalResume") {
                        // Get the average for a criterion
                        $nb=0;
                        /** @var EntityManager $em */
                        $em = $this->doctrine->getManager();
                        $session = $entity;
                        // On recupere les inscriptions de la session
                        $query = $em
                            ->createQuery('SELECT i FROM App\Entity\Core\AbstractInscription i
                                WHERE i.session = :session')
                            ->setParameter('session', $session);
                        $tabInsc = $query->getResult();

                        // On recupere les critères d'évaluations
                        $query = $em
				            ->createQuery('SELECT ec FROM App\Entity\Term\Evaluationcriterion ec
                                WHERE ec.organization is NULL or ec.organization = :org')
                            ->setParameter('org', $session->getTraining()->getOrganization());

                        $tabCrit = $query->getResult();

                        // On initialise les variables pour la moyenne
                        $tabAv = []; $nb=0;
                        foreach ($tabCrit as $crit) {
                            $tabAv[$crit->getId()]['sum'] = 0;
                            $tabAv[$crit->getId()]['nb'] = 0;
                            $tabAv[$crit->getId()]['av'] = 0;
			                $tabAv[$crit->getId()]['1et'] = 0;
                            $tabAv[$crit->getId()]['2et'] = 0;
                            $tabAv[$crit->getId()]['3et'] = 0;
                            $tabAv[$crit->getId()]['4et'] = 0;
                        }

                        $evalsMsg = '';
			            $nbEvals=0;

                        // On parcourt le tableau des inscriptions
                        foreach ($tabInsc as $insc) {
                            // On recupere le tableau des critères d'évaluation et intitulés
                            $query = $em
                                ->createQuery('SELECT s FROM App\Entity\Back\EvaluationNotedCriterion s
                                    WHERE s.inscription = :inscription 
                                    GROUP BY s.id')
                                ->setParameter('inscription', $insc);
                            $tabCritNot = $query->getResult();

                            if (!empty($tabCritNot))
                            $nbEvals++;

                            // Pour chaque critère, on calcule le total des notes
                            foreach ($tabCritNot as $critNot) {
                                if ($critNot->getNote() != 0) {
                                    $tabAv[$critNot->getCriterion()->getId()]['sum'] += $critNot->getNote();
                                    ++$tabAv[$critNot->getCriterion()->getId()]['nb'];

				                    if ($critNot->getNote() == 1)
                                        $tabAv[$critNot->getCriterion()->getId()]['1et']++;
                                    if ($critNot->getNote() == 2)
                                        $tabAv[$critNot->getCriterion()->getId()]['2et']++;
                                    if ($critNot->getNote() == 3)
                                        $tabAv[$critNot->getCriterion()->getId()]['3et']++;
                                    if ($critNot->getNote() == 4)
                                        $tabAv[$critNot->getCriterion()->getId()]['4et']++;
                                }
                            }

                            if ($insc->getMessage() != '') {
                                // Suppression retour chariot
                                $fixMsg = str_replace( ["\n", "\r"], [' ', ''], (string) $insc->getMessage() );
                                $evalsMsg .= $fixMsg . '// ';
                            }
                        }

                        // Calcul moyenne
                        foreach ($tabCrit as $crit) {
                            if ($tabAv[$crit->getId()]['nb']>0){
                                $tabAv[$crit->getId()]['av'] = $tabAv[$crit->getId()]['sum'] / $tabAv[$crit->getId()]['nb'];
                            } else
                                $tabAv[$crit->getId()]['av'] = 0;
                        }

                        // Mise en forme string pour sortie csv
                        $rvalue = '';
                        // Moyenne des critères
                        foreach ($tabCrit as $crit) {
			                $rvalue .= $crit->getName() . ' : 1*:' . $tabAv[$crit->getId()]['1et'] . ' -2*:' . $tabAv[$crit->getId()]['2et'] . ' -3*:' . $tabAv[$crit->getId()]['3et'] . ' -4*:' . $tabAv[$crit->getId()]['4et'] . ' -moy:' . $tabAv[$crit->getId()]['av'] . ' | ';
                        }

                        // Remarques evals
                        $rvalue .= 'Remarques: ' . $evalsMsg . ' | ';

                        // Nb d'éval
                        $rvalue .= sprintf('Nb evals : %s ', $nbEvals);

                        $data[$key] = $rvalue ?: '';

                    } else {
                        $rvalue = $propertyAccessor->getValue($entity, $key);
                        // reformat values
                        if (!empty($value['type'])) {
                            if ($value['type'] === 'date') {
                                if ($rvalue) {
                                    $rvalue = $rvalue->format('d/m/Y');
                                }
                            } elseif ($value['type'] === 'boolean') {
                                $rvalue = ($rvalue) ? 'Oui' : 'Non';
                            } elseif ($value['type'] === "typinsc") {
                                switch ($rvalue) {
                                    case 0:
                                        $rvalue = "Désactivées";
                                        break;
                                    case 1:
                                        $rvalue = "Fermées";
                                        break;
                                    case 2:
                                        $rvalue = "Privées";
                                        break;
                                    case 3:
                                        $rvalue = "Publiques";
                                        break;

                                }
                            } elseif ($value['type'] === "statut") {
                                switch ($rvalue) {
                                    case 0:
                                        $rvalue = "Ouverte";
                                        break;
                                    case 1:
                                        $rvalue = "Reportée";
                                        break;
                                    case 2:
                                        $rvalue = "Annulée";

                                }
                            }
                        }

                        // Petite mise en forme pour faciliter les manips avec Excel
                        if (stripos((string) $key,  'cost') !== false) {
                            // traitement specifique valeur nulle
                            if ($rvalue == '0.0') {
                                $rvalue = '0,0';
                            }

                            // Transformation '.' en ',' pour faciliter Excel
                            $rvalue = str_replace('.', ',', (string) $rvalue);
                        }

                        // Transformation '\n' en '|' pour affichage avec saut de ligne
                        $rvalue = str_replace("\n", "|", (string) $rvalue);

                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '__', (string) $key);
                        $data[$key] = $rvalue ?: '';
                    }
                } catch (UnexpectedTypeException) {
                    $key = str_replace('.', '', (string) $key);
                    $data[$key] = '';
                }
            }

            $lines[$entity->getId()] = $data;
//            }
        }
        
        // fields
        $fields = [];
        $heads = [];

        foreach ($this->options['fields'] as $label => $value) {
            ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
            $label = str_replace('.', '', (string) $label);

            if (isset($value['label'])) {
                $fields[] = [$label, $value['label']];
                $heads[] = $value['label'];
            } else {
                $fields[] = [$label, $value];
                $heads[] = $value;
            }
        }

        //setting filename
        if (!empty($this->options['filename'])) {
            $this->options['volcanus_config']['responseFilename'] = $this->options['filename'];
        }

	    $fileName = str_replace('.csv', '_' . uniqid() . '.csv', $this->options['volcanus_config']['responseFilename']);

        $volcanusConfig = $this->options['volcanus_config'];
        // encodage fichier
        $charsetConverter = (new CharsetConverter())
            ->inputEncoding($volcanusConfig['inputEncoding'] )
            ->outputEncoding($volcanusConfig['outputEncoding'] );

        $writer = Writer::createFromPath($this->options['tempDir'] . $fileName, 'w+');
        // Mise en forme fichier
        $writer->setDelimiter($volcanusConfig['delimiter'] );
        $writer->setEnclosure($volcanusConfig['enclosure'] );
        $writer->setEscape($volcanusConfig['escape'] );
        $writer->addFormatter($charsetConverter);

        $writer->getInputBOM();

        // Insertion
        $writer->insertOne($heads);
        $writer->insertAll($lines);

        return ['fileUrl' => $fileName];
    }

    /**
     * Gets a file from module's temp dir if exists, and send it to client.
     *
     * @param $fileName
     *
     * @return string|Response
     */
    public function sendFile($fileName): string|Response
    {
        if (file_exists($this->options['tempDir'] . $fileName)) {
            //security check first : if requested file path doesn't correspond to temp dir,
            //triggering error
            $path_parts = pathinfo($this->options['tempDir'] . $fileName);
            $response = new Response();
            if (realpath($path_parts['dirname']) !== $this->options['tempDir']) {
                $response->setContent('Accès non autorisé :' . $path_parts['dirname']);
            }

            //if pdf file is asked

            $fp = $this->options['tempDir'] . $fileName;

            // Set headers
            $response->headers->set('Cache-Control', 'private');
            $response->headers->set('Content-type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '";');
            $response->headers->set('Content-length', filesize($fp));
            $response->sendHeaders();
            $response->setContent(readfile($fp));
            $response->sendContent();

            //file is then deleted
            unlink($fp);

            return $response;
        }

        return $this->options['tempDir'] . $fileName;
    }
}

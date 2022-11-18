<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 07/04/14
 * Time: 16:56.
 */

namespace App\BatchOperations\Generic;

use Doctrine\ORM\EntityManager;
use App\BatchOperations\AbstractBatchOperation;
use App\Entity\Core\User;
use Doctrine\ORM\Query\ResultSetMapping;
use App\Entity\Core\AbstractSession;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Security;
use Volcanus\Csv\Writer;

/**
 * Class CSVBatchOperation.
 */
class CSVBatchOperation extends AbstractBatchOperation
{
    /**
     * @var EntityManager
     */
    protected $security;

    /**
     * @var array
     */
    protected $options = array(
        'volcanus_config' => array(
            'delimiter' => ';',
            'enclose' => true,
            'enclosure' => '"',
            'escape' => '"',
            'inputEncoding' => 'UTF-8',
            'outputEncoding' => 'ISO-8859-1',
            'writeHeaderLine' => true,
            'responseFilename' => 'export.csv',
        ),
    );

    /**
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
        $this->options['tempDir'] = sys_get_temp_dir() . '/sygefor/';
        if (!file_exists($this->options['tempDir'])) {
            mkdir($this->options['tempDir'], 0777);
        }
    }

    /**
     * @param array $idList
     * @param array $options
     *
     * @return mixed
     */
    public function execute(array $idList = array(), array $options = array())
    {
        $entities = $this->getObjectList($idList);

        // accessor
        $accessor = PropertyAccess::createPropertyAccessor();
        // lines
        $lines = array();
        foreach ($entities as $entity) {
//            if (!$this->securityContext->getToken()->getUser() instanceof User || $this->securityContext->isGranted('VIEW', $entity)) {
            $data = array();
            foreach ($this->options['fields'] as $key => $value) {
                try {
                    // Cas particuliers
                    // Calcul des stats d'inscriptions pour les sessions
                    if ($key == "inscription.listeatt") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', $key);

                        $statsListe = array();
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
                                $statsListe[] = array(
                                    'id'     => $status[0]->getId(),
                                    'name'   => $status[0]->getName(),
                                    'status' => $status[0]->getStatus(),
                                    'count'  => (int) $status[1],
                                );
                            }
                            // On recupere seulement le compteur
                            if (isset($statsListe[0]['count']))
                                $data[$key] = $statsListe[0]['count'];
                            else
                                $data[$key] = '';
                        } else {
                            $data[$key] = '';
                        }
                    } elseif ($key == "inscription.refus") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', $key);

                        $statsRefus = array();
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
                                $statsRefus[] = array(
                                    'id'     => $status[0]->getId(),
                                    'name'   => $status[0]->getName(),
                                    'status' => $status[0]->getStatus(),
                                    'count'  => (int) $status[1],
                                );
                            }
                            // On recupere seulement le compteur
                            if (isset($statsRefus[0]['count']))
                                $data[$key] = $statsRefus[0]['count'];
                            else
                                $data[$key] = '';
                        } else {
                            $data[$key] = '';
                        }
                    } elseif ($key == "inscription.convoke") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', $key);

                        $statsRefus = array();
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
                                $statsRefus[] = array(
                                    'id'     => $status[0]->getId(),
                                    'name'   => $status[0]->getName(),
                                    'status' => $status[0]->getStatus(),
                                    'count'  => (int) $status[1],
                                );
                            }
                            // On recupere seulement le compteur
                            if (isset($statsRefus[0]['count']))
                                $data[$key] = $statsRefus[0]['count'];
                            else
                                $data[$key] = '';
                        } else {
                            $data[$key] = '';
                        }
                    } elseif ($key == "inscription.presence.nbheures") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', $key);

                        $statsNbHeures = array();
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
                        $key = str_replace('.', '', $key);

                        $statsPresPart = array();
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
                                $statsPresPart[] = array(
                                    'id'     => $status[0]->getId(),
                                    'name'   => $status[0]->getName(),
                                    'status' => $status[0]->getStatus(),
                                    'count'  => (int) $status[1],
                                );
                                $nbPresPart +=  (int) $status[1];
                            }
                            // On recupere le nombre de présences totales et partielles * nombre d'heures théoriques
                            $data[$key] = $nbPresPart * $accessor->getValue($session, 'hournumber');

                            // Transformation '.' en ',' pour faciliter Excel
                            $data[$key] = str_replace('.', ',', $data[$key]);
                        } else {
                            $data[$key] = '';
                        }
                    } elseif ($key == "presence.nbheures") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', $key);

                        $statsNbHeures = array();
                        /** @var EntityManager $em */
                        $em = $this->doctrine->getManager();
                        $inscription = $entity;
                        $session = $inscription->getSession();

                        // on regarde si l'inscription est bien dans un statut convoqué // DEMANDE DRH : NE PLUS VERIFIER
//                        if($inscription->getInscriptionstatus()->getMachinename() == "convoke") {
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
                            foreach($tabPresences as $pres) {
                                foreach($tabDatesSes as $dateSes) {
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

                            // Transformation '.' en ',' pour faciliter Excel
                            $data[$key] = str_replace('.', ',', $data[$key]);
//                        } else {
//                            $data[$key] = '';
//                        }
                    } elseif ($key == "presence.partiel") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', $key);

                        $statsPartiel = array();
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
                                $statsPartiel[] = array(
                                    'id'     => $status[0]->getId(),
                                    'name'   => $status[0]->getName(),
                                    'status' => $status[0]->getStatus(),
                                    'count'  => (int) $status[1],
                                );
                            }
                            // On recupere seulement le compteur
                            if (isset($statsPartiel[0]['count']))
                                $data[$key] = $statsPartiel[0]['count'];
                            else
                                $data[$key] = '';
                        } else {
                            $data[$key] = '';
                        }
                    } elseif ($key == "presence.absent") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', $key);

                        $statsAbsent = array();
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
                                $statsAbsent[] = array(
                                    'id'     => $status[0]->getId(),
                                    'name'   => $status[0]->getName(),
                                    'status' => $status[0]->getStatus(),
                                    'count'  => (int) $status[1],
                                );
                            }
                            // On recupere seulement le compteur
                            if (isset($statsAbsent[0]['count']))
                                $data[$key] = $statsAbsent[0]['count'];
                            else
                                $data[$key] = '';
                        } else {
                            $data[$key] = '';
                        }
                    } elseif ($key == "presence.excuse") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', $key);

                        $statsExcuse = array();
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
                                $statsExcuse[] = array(
                                    'id'     => $status[0]->getId(),
                                    'name'   => $status[0]->getName(),
                                    'status' => $status[0]->getStatus(),
                                    'count'  => (int) $status[1],
                                );
                            }
                            // On recupere seulement le compteur
                            if (isset($statsExcuse[0]['count']))
                                $data[$key] = $statsExcuse[0]['count'];
                            else
                                $data[$key] = '';
                        } else {
                            $data[$key] = '';
                        }
                    } elseif ($key == "trainers.fullName") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', $key);

                        $statsRefus = array();
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
                            $statsTrainer[] = array(
                                'id'     => $trainer->getId(),
                                'first'   => $trainer->getFirstName(),
                                'last' => $trainer->getLastName(),
                            );
                            $liste = $liste . $trainer->getFirstName() ." " . $trainer->getLastName() . ", ";
                        }
                        // On recupere la liste des formateurs
                        $data[$key] = $liste;

                    } elseif ($key == "totalCost") {
                        $rvalue = $accessor->getValue($entity, 'teachingcost')
                            + $accessor->getValue($entity, 'vacationcost')
                            + $accessor->getValue($entity, 'accommodationcost')
                            + $accessor->getValue($entity, 'mealcost')
                            + $accessor->getValue($entity, 'transportcost')
                            + $accessor->getValue($entity, 'materialcost');

                        $data[$key] = ($rvalue) ? $rvalue : '';
                        // Transformation '.' en ',' pour faciliter Excel
                        $data[$key] = str_replace('.', ',', $data[$key]);
                    }elseif ($key == "session.totalCost") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', $key);

                        /** @var EntityManager $em */
                        $em = $this->doctrine->getManager();
                        $inscription = $entity;
                        $session = $inscription->getSession();

                        $rvalue = $accessor->getValue($session, 'teachingcost')
                            + $accessor->getValue($session, 'vacationcost')
                            + $accessor->getValue($session, 'accommodationcost')
                            + $accessor->getValue($session, 'mealcost')
                            + $accessor->getValue($session, 'transportcost')
                            + $accessor->getValue($session, 'materialcost');

                        $data[$key] = ($rvalue) ? $rvalue : '';
                        // Transformation '.' en ',' pour faciliter Excel
                        $data[$key] = str_replace('.', ',', $data[$key]);

                    }elseif ($key == "training.tags") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', $key);

                        /** @var EntityManager $em */
                        $em = $this->doctrine->getManager();
                        $session = $entity;
                        $training = $session->getTraining();
                        $trainingId = $training->getId();

                        // Création de la requête de récupération des tags
                        $sql = <<<SQL
        SELECT t.name FROM tag t, training__training_tag i_t, training train WHERE train.id = :trainingId and i_t.training_id = train.id and i_t.tag_id = t.id 
SQL;
                        $rsm = new ResultSetMapping();
                        $rsm->addScalarResult('name', 'name');
                        $query = $em->createNativeQuery($sql, $rsm);
                        $query->setParameter('trainingId', $trainingId);
                        $result = $query->getResult();

                        $tags = "";
                        foreach($result as $tag) {
                            $tags .= $tag['name'] . " ; ";
                        }

                        $rvalue = $tags;

                        $data[$key] = ($rvalue) ? $rvalue : '';
                    } elseif ($key == "date.lieu") {
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', $key);

                        $statsNbHeures = array();
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
                        if ((isset($tabDatesSes[0])) &&(null !== $tabDatesSes[0])) {
                            if (null !== $tabDatesSes[0]->getPlace()) {
                                $data[$key] = $tabDatesSes[0]->getPlace();
                            } else {
                                $data[$key] = '';
                            }
                        } else {
                            $data[$key] = '';
                        }

                    } elseif ($key == "evalResume") {
                        // Get the average for a criterion
                        $nb=0;
                        $average=0;
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
                            ->createQuery('SELECT ec FROM App\Entity\Term\Evaluationcriterion ec');
                        $tabCrit = $query->getResult();

                        // On initialise les variables pour la moyenne
                        $tabAv = array(); $nb=0;
                        foreach ($tabCrit as $crit) {
                            $tabAv[$crit->getId()]['sum'] = 0;
                            $tabAv[$crit->getId()]['nb'] = 0;
                            $tabAv[$crit->getId()]['av'] = 0;
                        }
                        $evalsMsg = '';

                        // On parcourt le tableau des inscriptions
                        foreach ($tabInsc as $insc) {
                            // On recupere le tableau des critères d'évaluation et intitulés
                            $query = $em
                                ->createQuery('SELECT s FROM App\Entity\Back\EvaluationNotedCriterion s
                    WHERE s.inscription = :inscription 
                    GROUP BY s.id')
                                ->setParameter('inscription', $insc);
                            $tabCritNot = $query->getResult();

                            // Pour chaque critère, on calcule le total des notes
                            foreach ($tabCritNot as $critNot) {
                                if ($critNot->getNote() != 0) {
                                    $tabAv[$critNot->getCriterion()->getId()]['sum'] += $critNot->getNote();
                                    $tabAv[$critNot->getCriterion()->getId()]['nb']++;
                                }
                            }
                            if ($insc->getMessage() != '')
                                $evalsMsg .= $insc->getMessage() . '; ';
                        }
                        // Calcul moyenne
                        foreach ($tabCrit as $crit) {
                            $nbEvals = $tabAv[$crit->getId()]['nb'];
                            if ($tabAv[$crit->getId()]['nb']>0){
                                $tabAv[$crit->getId()]['av'] = $tabAv[$crit->getId()]['sum'] / $tabAv[$crit->getId()]['nb'];
                            } else
                                $tabAv[$crit->getId()]['av'] = 0;
                        }
                        // Mise en forme string pour sortie csv
                        $rvalue = '';
                        // Moyenne des critères
                        foreach ($tabCrit as $crit) {
                            $rvalue .= $crit->getName() . ' : ' . $tabAv[$crit->getId()]['av'] . ' | ';
                        }

                        // Remarques evals
                        $rvalue .= 'Remarques: ' . $evalsMsg . ' | ';

                        // Nb d'éval
                        $rvalue .= "Nb evals : $nbEvals ";

                        $data[$key] = ($rvalue) ? $rvalue : '';

                    } else {
                        $rvalue = $accessor->getValue($entity, $key);
                        // reformat values
                        if (!empty($value['type'])) {
                            if ($value['type'] === 'date') {
                                if ($rvalue) {
                                    $rvalue = $rvalue->format('d/m/Y');
                                }
                            } else if ($value['type'] === 'boolean') {
                                $rvalue = ($rvalue) ? 'Oui' : 'Non';
                            } else if ($value['type'] === "typinsc") {
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
                            } else if ($value['type'] === "statut") {
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
                        if (stripos($key,  'cost') !== false) {
                            // Transformation '.' en ',' pour faciliter Excel
                            $rvalue = str_replace('.', ',', $rvalue);
                        }
                        ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
                        $key = str_replace('.', '', $key);
                        $data[$key] = ($rvalue) ? $rvalue : '';
                    }
                } catch (UnexpectedTypeException $e) {
                }
            }
            $lines[$entity->getId()] = $data;
//            }
        }

        // reorder
//        $this->reorderByKeys($lines, $idList);

        // fields
        $fields = array();

        foreach ($this->options['fields'] as $label => $value) {
            ///// PATCH : modif nom des labels car ne fonctionne plus avec '.'
            $label = str_replace('.', '', $label);

            if (isset($value['label'])) {
                $fields[] = array($label, $value['label']);
            } else {
                $fields[] = array($label, $value);
            }
        }

        //setting filename
        if (!empty($this->options['filename'])) {
            $this->options['volcanus_config']['responseFilename'] = $this->options['filename'];
        }
        $fileName = str_replace('.csv', '_' . uniqid() . '.csv', $this->options['volcanus_config']['responseFilename']);
        $writer = new Writer($this->options['volcanus_config']);
        $writer->fields($fields);
        $file = new \SplFileObject($this->options['tempDir'] . $fileName, 'w+');
        $writer->setFile($file);
        $writer->write($lines);
        //$writer->send();
        return array('fileUrl' => $fileName);
    }

    /**
     * Gets a file from module's temp dir if exists, and send it to client.
     *
     * @param $fileName
     *
     * @return string|Response
     */
    public function sendFile($fileName)
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

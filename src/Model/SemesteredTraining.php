<?php

/**
 * Class used to create objects
 * Created by PhpStorm.
 * User: maxime
 * Date: 16/04/14
 * Time: 11:13.
 */

namespace App\Model;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTraining;
use Doctrine\Persistence\ObjectManager;

final class SemesteredTraining
{
    /**
     * @var \App\Entity\Core\AbstractSession[]|null
     */
    private ?array $sessions = null;

    /**
     * @param int $year
     * @param int $semester
     * @param AbstractTraining $training
     */
    public function __construct(private int $year, private int $semester, private AbstractTraining $training, ?array $sessions = null)
    {
        $this->setSessions($sessions);
    }

    /**
     * @param int $semester
     */
    public function setSemester(int $semester): void
    {
        $this->semester = $semester;
    }

    /**
     * @return int
     */
    public function getSemester(): int
    {
        return $this->semester;
    }

    /**
     * @param mixed[]|null $sessions
     */
    public function setSessions(?array $sessions = null): void
    {
        if (is_array($sessions) && $sessions !== []) {
            $this->sessions = $sessions;
            $this->orderSessions();
        } else {
            $this->setSessionsFromTrainingAndDate();
        }
    }

    /**
     * @return mixed[]|null
     */
    public function getSessions(): ?array
    {
        return $this->sessions;
    }

    public function setTraining(mixed $training): void
    {
        $this->training = $training;
    }

    /**
     * @return AbstractTraining
     */
    public function getTraining(): AbstractTraining
    {
        return $this->training;
    }

    /**
     * @param int $year
     */
    public function setYear(int $year): void
    {
        $this->year = $year;
    }

    /**
     * @return int
     */
    public function getYear(): int
    {
        return $this->year;
    }

    public function getId(): string
    {
        return $this->training->getId().'_'.$this->year.'_'.$this->semester;
    }

    /**
     * @return AbstractSession
     */
    public function getLastSession(): AbstractSession|null
    {
        if (empty($this->sessions)) {
            return null;
        }

        $dateTime = new \DateTime();

        $result = null;
        $maxdif = 9_999_999_999;
        foreach ($this->sessions as $session) {
            $dif = $dateTime->getTimestamp() - $session->getDatebegin()->getTimeStamp();
            if (($dif > 0) && ($dif < $maxdif)) {
                $result = $session;
                $maxdif = $dif;
            }
        }

        return $result;
    }

    /**
     * @return AbstractSession|null
     */
    public function getNextSession(): AbstractSession|null
    {
        if (empty($this->sessions)) {
            return null;
        }

        $dateTime = new \DateTime();

        $result = null;
        $maxdif = 9_999_999_999;

            foreach ($this->sessions as $session) {
                $dif = $session->getDatebegin()->getTimestamp() - $dateTime->getTimeStamp();
                if (($dif > 0) && ($dif < $maxdif)) {
                    $result = $session;
                    $maxdif = $dif;
                }
            }

        return $result;
    }

    /**
     * Returns the number of sessions belonging to semesteredtraining.
     *
     */
    public function getSessionscount(): int
    {
        if (empty($this->sessions)) {
            return 0;
        }

        return count($this->sessions);
    }

    /**
     * sets the sessions list given the current objects training and year/semester values.
     */
    private function setSessionsFromTrainingAndDate(): void
    {
        $sessions = $this->training->getSessions();

        $tmpSessions = [];
        foreach ($sessions as $session) {
            /** @var \DateTime $date */
            $date = $session->getDatebegin();

            $year = $date->format('Y');
            $semester = ($date->format('m') <= 6) ? 1 : 2;

            if ($year == $this->year && $semester === $this->semester) {
                $tmpSessions[] = $session;
            }
        }
        $this->sessions = $tmpSessions;
        $this->orderSessions();
    }

    /**
     * Get array of trainer.
     *
     * @return array<int|string, mixed>
     */
    public function getTrainers(): array
    {
        $trainers = [];
        if ($this->sessions) {
            foreach ($this->sessions as $session) {
                if (!$session->getParticipations()) {
                    continue;
                }
                if ($session->getParticipations()->count() <= 0) {
                    continue;
                }
                foreach ($session->getParticipations() as $participation) {
                    // do not add several times the same trainer
                    $trainers[$participation->getTrainer()->getId()] = $participation->getTrainer();
                }
            }
        }

        return $trainers;
    }

    /**
     * builds and returns SemesteredTraining objects array corresponding to Training object.
     *
     *
     */
    public static function getSemesteredTrainingsForTraining(AbstractTraining $training): array
    {
        /** @var AbstractSession[] $sessions */
        $sessions = $training->getSessions();

        //sorting sessions per year/semester
        $orderedSessions = [];
        if ($sessions !== []) {
            foreach ($sessions as $session) {
                //if (!$session ){die();}
                /** @var \DateTime $date */
                $date = $session->getDatebegin();

                $year = $date->format('Y');
                $semester = ($date->format('m') <= 6) ? 1 : 2;

                if (!isset($orderedSessions[$year])) {
                    $orderedSessions[$year] = [];
                }

                if (!isset($orderedSessions[$year][$semester])) {
                    $orderedSessions[$year][$semester] = [];
                }

                $orderedSessions[$year][$semester][] = $session;
            }

            $semTrainings = [];

            //SemesteredTrainings objects are built around each sessions list
            foreach ($orderedSessions as $year => $semesters) {
                foreach ($semesters as $sem => $sessions) {
                    $tempSemTraining = new self($year, $sem, $training);
                    $tempSemTraining->setSessions($sessions);

                    $semTrainings[] = $tempSemTraining;
                }
            }

            return $semTrainings;
        }
        // no session found : we build a single semestered training on first session year/semester.
        $year = $training->getFirstSessionPeriodYear();
        $semester = $training->getFirstSessionPeriodSemester();
        if ($year === null || $semester === null) {
            $year = date('Y');
            $semester = (date('n') <= 6) ? 1 : 2;
        }
        $semTraining = new self($year, $semester, $training, []);
        return [$semTraining];
    }

    /**
     * Return an array of training and remove duplicates from semestered training list.
     *
     * @param array $excludedTypes
     */
    public static function getTrainingsByIds(array $idList, ObjectManager $entityManager, array $excludedTypes): array
    {
        $arrayIds = [];
        foreach ($idList as $semesteredTrainingId) {
            $arrayIds[] = explode('_', (string) $semesteredTrainingId)[0];
        }

        $arrayIds = array_unique($arrayIds);

        $allEntities = $entityManager->getRepository(AbstractTraining::class)
            ->findBy(['id' => $arrayIds]);

        $notMeetingEntities = [];
        foreach ($allEntities as $allEntity) {
            if (!in_array($allEntity->getType(), $excludedTypes, true)) {
                $notMeetingEntities[] = $allEntity;
            }
        }

        return $notMeetingEntities;
    }

    /**
     * Returns an array of semestered trainings corresponding to given list of ids.
     *
     *
     * @return SemesteredTraining[]
     */
    public static function getSemesteredTrainingsByIds(array $idList, ObjectManager $entityManager): array
    {
        //building DQL query to get needed sessions objects
        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('s')
            ->from(AbstractTraining::class, 't')
                ->leftJoin(AbstractSession::class, 's', Join::WITH, 't = s.training');

        $paramCount = 0;
        $parameters = [];
        foreach ($idList as $tId) {
            //var_dump($tId);
            $params = explode('_', (string) $tId);

            if (count($params) === 3) {
                $dateFrom = ($params[2] === 2) ? $params[1].'-01-07 00:00:00' : $params[1].'-01-01 00:00:00';
                $dateTo = ($params[2] === 2) ? $params[1].'-31-12 23:59:59' : $params[1].'-30-06 23:59:59';

                $queryBuilder->orWhere('( t.id = :id'.$paramCount.' AND s.datebegin < :dateTo'.$paramCount.' AND s.datebegin > :dateFrom'.$paramCount.')');
                $parameters = array_merge($parameters, ['id'.$paramCount => $params[0], 'dateTo'.$paramCount => $dateTo, 'dateFrom'.$paramCount => $dateFrom]);
                ++$paramCount;
            }
        }

        //echo $qb->getQuery()->getDQL(); die();
        $queryBuilder->setParameters($parameters);
        $tmpArray = $queryBuilder->getQuery()->getResult();

        //objects are grouped by training / year / semester
        $sessions = [];
        foreach ($tmpArray as $re) {
            if (!empty($re)) {
                $ys = self::getYearAndSemesterFromDate($re->getDatebegin());
                $tId = $re->getTraining()->getId();
                if (!isset($sessions[$tId])) {
                    $sessions[$tId] = [];
                }

                if (!isset($sessions[$tId][$ys[0]])) {
                    $sessions[$tId][$ys[0]] = [];
                }

                if (!isset($sessions[$tId][$ys[0]][$ys[1]])) {
                    $sessions[$tId][$ys[0]][$ys[1]] = [];
                }

                $sessions[$tId][$ys[0]][$ys[1]][] = $re;
            }
        }

        $semTrains = [];
        //for each training / year / semester, a SemesteredTraining object is built
        foreach ($idList as $id) {
            $params = explode('_', (string) $id);


            if (count($params) === 3) {
                $trainingId = $params[0];
                $year = $params[1];
                $semester = $params[2];

                if (!empty($sessions[$trainingId][$year][$semester])) {
                    //getting sessions
                    $tmpSessions = $sessions[$trainingId][$year][$semester];
                    $semTrains[] = new self($year, $semester, $tmpSessions[0]->getTraining(), $tmpSessions);
                } else {
                    $train = $entityManager->getRepository(AbstractTraining::class)->find($trainingId);

                    if($train){
                        $semTrains[] = new self($year, $semester, $train, []);
                    }
                }
            }

        }

        //var_dump($qb->getQuery());
        return $semTrains;
    }

    /**
     * helper for getting year+ semester.
     *
     *
     */
    public static function getYearAndSemesterFromDate(\DateTime $dateTime): array
    {
        $year = $dateTime->format('Y');
        $semester = ($dateTime->format('m') <= 6) ? 1 : 2;

        return [$year, $semester];
    }

    /**
     * ordering.
     */
    private function orderSessions(): void
    {
        @usort($this->sessions, static function ($a, $b) : int {
            $ad = $a->getDatebegin();
            $bd = $b->getDatebegin();
            return $bd <=> $ad;
        });
    }
}

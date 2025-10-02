<?php

namespace App\Model;

use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTraining;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ObjectManager;

final class SemesteredTraining
{
    /** @var AbstractSession[]|null */
    private ?array $sessions = [];

    public function __construct(
        private int $year,
        private int $semester,
        private AbstractTraining $training,
        ?array $sessions = null
    ) {
        $this->setSessions($sessions);
    }


    public function getDateBegin(): ?\DateTimeInterface
    {
        if (empty($this->sessions)) {
            return null;
        }

        // Retourne la date la plus petite parmi toutes les sessions
        $dates = array_map(fn($s) => $s->getDateBegin(), $this->sessions);
        sort($dates);
        return $dates[0];
    }

    public function getDateEnd(): ?\DateTimeInterface
    {
        if (empty($this->sessions)) {
            return null;
        }

        // Retourne la date la plus grande parmi toutes les sessions
        $dates = array_map(fn($s) => $s->getDateEnd(), $this->sessions);
        rsort($dates);
        return $dates[0];
    }

    public function getId(): string
    {
        return sprintf('%s_%d_%d', $this->training->getId(), $this->year, $this->semester);
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): void
    {
        $this->year = $year;
    }

    public function getSemester(): int
    {
        return $this->semester;
    }

    public function setSemester(int $semester): void
    {
        $this->semester = $semester;
    }

    public function getTraining(): AbstractTraining
    {
        return $this->training;
    }

    public function setTraining(AbstractTraining $training): void
    {
        $this->training = $training;
    }

    /**
     * @return AbstractSession[]|null
     */
    public function getSessions(): ?array
    {
        return $this->sessions;
    }

    /**
     * @param AbstractSession[]|null $sessions
     */
    public function setSessions(?array $sessions = null): void
    {
        if (!empty($sessions)) {
            $this->sessions = $sessions;
        } else {
            $this->sessions = array_filter($this->training->getSessions()->toArray(), function (AbstractSession $session) {
                $date = $session->getDatebegin();
                return $date->format('Y') == $this->year &&
                    (($date->format('m') <= 6 && $this->semester === 1) ||
                        ($date->format('m') > 6 && $this->semester === 2));
            });
        }

        usort($this->sessions, fn($a, $b) => $a->getDatebegin() <=> $b->getDatebegin());
    }

    public function getSessionsCount(): int
    {
        return count($this->sessions ?? []);
    }

    public function getLastSession(): ?AbstractSession
    {
        if (empty($this->sessions)) {
            return null;
        }

        $now = new \DateTime();
        return array_reduce($this->sessions, function (?AbstractSession $carry, AbstractSession $session) use ($now) {
            $diff = $now->getTimestamp() - $session->getDatebegin()->getTimestamp();
            return ($diff > 0 && ($carry === null || $diff < $now->getTimestamp() - $carry->getDatebegin()->getTimestamp()))
                ? $session
                : $carry;
        }, null);
    }

    public function getNextSession(): ?AbstractSession
    {
        if (empty($this->sessions)) {
            return null;
        }

        $now = new \DateTime();
        return array_reduce($this->sessions, function (?AbstractSession $carry, AbstractSession $session) use ($now) {
            $diff = $session->getDatebegin()->getTimestamp() - $now->getTimestamp();
            return ($diff > 0 && ($carry === null || $diff < $carry->getDatebegin()->getTimestamp() - $now->getTimestamp()))
                ? $session
                : $carry;
        }, null);
    }

    public function getTrainers(): array
    {
        $trainers = [];
        foreach ($this->sessions ?? [] as $session) {
            foreach ($session->getParticipations() ?? [] as $participation) {
                $trainer = $participation->getTrainer();
                $trainers[$trainer->getId()] = $trainer;
            }
        }
        return $trainers;
    }

    public static function getSemesteredTrainingsForTraining(AbstractTraining $training): array
    {
        $sessions = $training->getSessions();
        $grouped = [];

        foreach ($sessions as $session) {
            $date = $session->getDatebegin();
            $year = (int)$date->format('Y');
            $semester = ((int)$date->format('m') <= 6) ? 1 : 2;
            $grouped[$year][$semester][] = $session;
        }

        $results = [];
        foreach ($grouped as $year => $semesters) {
            foreach ($semesters as $semester => $sessions) {
                $results[] = new self($year, $semester, $training, $sessions);
            }
        }

        if (empty($results)) {
            $year = $training->getFirstSessionPeriodYear() ?? (int)date('Y');
            $semester = $training->getFirstSessionPeriodSemester() ?? ((int)date('n') <= 6 ? 1 : 2);
            $results[] = new self($year, $semester, $training, []);
        }

        return $results;
    }

    public static function getTrainingsByIds(array $ids, ObjectManager $em, array $excludedTypes): array
    {
        $trainingIds = array_unique(array_map(fn($id) => explode('_', (string)$id)[0] ?? null, $ids));
        $trainings = $em->getRepository(AbstractTraining::class)->findBy(['id' => $trainingIds]);

        return array_filter($trainings, fn($training) => !in_array($training->getType(), $excludedTypes, true));
    }

    /**
     * @return SemesteredTraining[]
     */
    public static function getSemesteredTrainingsByIds(array $idList, ObjectManager $em): array
    {
        if (empty($idList)) {
            return [];
        }

        $qb = $em->createQueryBuilder()
            ->select('s', 't')
            ->from(AbstractSession::class, 's')
            ->join('s.training', 't');

        $orX = $qb->expr()->orX();
        $parameters = [];
        $normalizedIds = [];
        $now = new \DateTime();

        foreach ($idList as $rawId) {
            $id = trim(preg_replace('/"{2,}/', '"', trim($rawId, "\"' ")), "\"' ");
            $parts = explode('_', $id);

            if (count($parts) === 1 && ctype_digit($parts[0])) {
                $year = (int)$now->format('Y');
                $semester = ((int)$now->format('m') <= 6) ? 1 : 2;
                $id = sprintf('%d_%d_%d', $parts[0], $year, $semester);
                $parts = explode('_', $id);
            }

            if (count($parts) !== 3) continue;

            [$trainingId, $year, $semester] = $parts;

            if (!ctype_digit($trainingId) || !ctype_digit($year) || !in_array($semester, ['1', '2'], true)) {
                continue;
            }

            $normalizedIds[] = $id;

            $dateFrom = new \DateTime(sprintf('%d-%02d-01', $year, $semester === '1' ? 1 : 7));
            $dateTo   = new \DateTime(sprintf('%d-%02d-%d', $year, $semester === '1' ? 6 : 12, $semester === '1' ? 30 : 31));

            $paramKey = "id_{$trainingId}_{$year}_{$semester}";
            $orX->add(
                $qb->expr()->andX(
                    $qb->expr()->eq('t.id', ":{$paramKey}_t"),
                    $qb->expr()->between('s.datebegin', ":{$paramKey}_from", ":{$paramKey}_to")
                )
            );

            $parameters["{$paramKey}_t"] = (int)$trainingId;
            $parameters["{$paramKey}_from"] = $dateFrom;
            $parameters["{$paramKey}_to"] = $dateTo;
        }

        if ($orX->count() === 0) return [];

        $sessions = $qb->where($orX)
            ->setParameters($parameters)
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($sessions as $session) {
            $training = $session->getTraining();
            $year = (int)$session->getDatebegin()->format('Y');
            $semester = ((int)$session->getDatebegin()->format('m') <= 6) ? 1 : 2;
            $key = sprintf('%d_%d_%d', $training->getId(), $year, $semester);
            $grouped[$key][] = $session;
        }

        $results = [];
        foreach ($normalizedIds as $id) {
            [$trainingId, $year, $semester] = explode('_', $id);
            $training = $em->getRepository(AbstractTraining::class)->find($trainingId);
            if (!$training) continue;

            // Toutes les sessions du semestre ciblé
            $sessionsForThisSemester = $grouped[$id] ?? [];

            // Toutes les sessions de ce training, tous semestres confondus
            $allSessionsForTraining = $em->getRepository(AbstractSession::class)
                ->findBy(['training' => $training], ['datebegin' => 'ASC']);

            $defaultDateBegin = new \DateTime(sprintf('%d-%02d-01', $year, $semester === '1' ? 1 : 7));

            // Première session du semestre
            $dateBegin = !empty($sessionsForThisSemester)
                ? $sessionsForThisSemester[0]->getDatebegin() ?? $defaultDateBegin
                : $defaultDateBegin;

            // Dernière session de l'entraînement (n'importe quel semestre)
            $lastSessiondateBegin = !empty($allSessionsForTraining)
                ? end($allSessionsForTraining)->getDatebegin() ?? $dateBegin
                : $dateBegin;

            $results[] = new self(
                (int)$year,
                (int)$semester,
                $training,
                $sessionsForThisSemester,
                $dateBegin,
                $lastSessiondateBegin
            );
        }

        return $results;
    }

}

<?php

namespace App\Service;


use App\Entity\Back\Session;
use App\Entity\Back\Trainee;
use App\Entity\Core\AbstractInscription;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Core\AbstractTraining;
use App\Repository\TrainingRepository;
use Doctrine\Persistence\ManagerRegistry;
use http\Env\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Serializer\SerializerInterface;
use App\Service\TrainingBalanceRow;


readonly class TrainingBalanceSheet
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    public function getCsvResponse(AbstractTraining $training): \Symfony\Component\HttpFoundation\Response
    {
        $sessions = $this->doctrine
            ->getRepository(Session::class)
            ->findBy(['training' => $training]);

        $participants = [];
        $trainer = [];

        foreach ($sessions as $session) {
            foreach ($session->getTraining() as $summary) {
                $trainer[] = $summary;
            }
        }

        foreach ($sessions as $session) {
            foreach ($session->getParticipantsSummaries() as $summary) {
                $participants[] = $summary;
            }
        }

        $response = new StreamedResponse(function () use ($training, $participants, $sessions, $trainer) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            $delimiter = ';';
            fputcsv($handle, [
                'Numéro',
                'Nom de la formation',
                'Thématique',
                'Nombre de participants',
                'Nombre d\'inscriptions',
                'Total heures de formation',
                'Total jours de formation',
                'Superviseur(se)'
            ], $delimiter);

            // les valeurs
            fputcsv($handle, [
                $training->getNumber(),
                $training->getName(),
                $training->getTheme()?->getName() ?? 'Non définie',
                array_sum(array_map(fn($s) => $s->getNumberofparticipants(), $sessions)),
                array_sum(array_map(fn($s) => $s->getNumberofregistrations(), $sessions)),
                array_sum(array_map(fn($s) => $s->getHournumber(), $sessions)),
                array_sum(array_map(fn($s) => $s->getDaynumber(), $sessions)),
                $training->getSupervisor()->getFullName() ?? 'Non définie',
            ], $delimiter);

            fclose($handle);
        });


        $filename = 'bilan_formation_' . $training->getId() . '.csv';
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');


        return $response;
    }

}
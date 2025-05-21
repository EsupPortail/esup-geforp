<?php

namespace App\Service;


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
            ->getRepository(AbstractSession::class)
            ->findBy(['training' => $training]);

        $participants = [];

        foreach ($sessions as $session) {
            foreach ($session->getParticipantsSummaries() as $summary) {
                $participants[] = $summary;
            }
        }

        $response = new StreamedResponse(function () use ($participants) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Nom', 'Email', 'Statut']);

            foreach ($participants as $participantSummary) {
                $trainee = $participantSummary->getTrainee();
                fputcsv($handle, [
                    $trainee?->getFullName() ?? 'Inconnu',
                    $trainee?->getEmail() ?? 'Inconnu',
                    $participantSummary->getStatus() ?? 'Non défini',
                ]);
            }
            fclose($handle);

        });


        $filename = 'bilan_formation_' . $training->getId() . '.csv';
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->sendHeaders();


        return $response;
    }

}
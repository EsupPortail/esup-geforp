<?php

namespace App\Controller\Back;

use App\Controller\Core\AbstractParticipationController;
use App\Entity\Back\Participation;
use Symfony\Component\Routing\Attribute\Route;

 #[Route("/participation")]final class ParticipationController extends AbstractParticipationController
{
    protected string $participationClass = Participation::class;
}
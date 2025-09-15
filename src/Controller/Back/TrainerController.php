<?php

namespace App\Controller\Back;

use App\Controller\Core\AbstractTrainerController;
use App\Entity\Back\Trainer;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/trainer')]final class TrainerController extends AbstractTrainerController
{
    protected $trainerClass = Trainer::class;
}
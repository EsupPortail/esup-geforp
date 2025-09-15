<?php

namespace App\Controller\Back;


use App\Entity\Back\Trainee;
use App\Controller\Core\AbstractTraineeController;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/trainee')]final class TraineeController extends AbstractTraineeController
{
    protected $traineeClass = Trainee::class;
}

<?php

namespace App\Controller;


use App\Entity\Trainee;
use App\Controller\Core\AbstractTraineeController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/trainee")
 */
class TraineeController extends AbstractTraineeController
{
    protected $traineeClass = Trainee::class;
}

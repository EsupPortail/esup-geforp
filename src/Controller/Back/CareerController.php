<?php

namespace App\Controller\Back;


use App\Entity\Back\Career;
use App\Controller\Core\AbstractCareerController;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;


#[Route(path: '/career')]final class CareerController extends AbstractCareerController
{
    protected $careerClass = Career::class;
}

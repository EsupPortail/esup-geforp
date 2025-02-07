<?php

namespace App\Controller\Back;

use App\Entity\Back\Institution;
use App\Controller\Core\AbstractInstitutionController;
use Symfony\Component\Routing\Attribute\Route;





#[Route("/institution")]
final class InstitutionController extends AbstractInstitutionController
{
    protected string $institutionClass = Institution::class;
}
<?php

namespace App\Controller\Back;


use App\Entity\Core\Material;
use App\Controller\Core\AbstractMaterialController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/material")
 */
class MaterialController extends AbstractMaterialController
{
    protected $materialClass = Material::class;
}

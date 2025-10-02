<?php

namespace App\Controller\Back;


use App\Entity\Core\Material;
use App\Controller\Core\AbstractMaterialController;
use Symfony\Component\Routing\Annotation\Route;

  #[Route("/material")]final class MaterialController extends AbstractMaterialController
{
    /**
     * @var class-string<\App\Entity\Core\Material>
     */
    private const MATERIAL_CLASS = Material::class;
}

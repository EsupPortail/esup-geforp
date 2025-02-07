<?php

namespace App\Controller\Back;


use App\Entity\Back\Session;
use App\Controller\Core\AbstractTrainingController;
use App\Entity\Core\AbstractTraining;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;


#[Route(path: '/training')]final class TrainingController extends AbstractTrainingController
{
    protected $sessionClass = Session::class;

    private readonly ManagerRegistry $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        parent::__construct($managerRegistry);
        $this->managerRegistry = $managerRegistry;
    }


    /**
 * @param AbstractTraining $dest
 * @param AbstractTraining $source
 */
    protected function mergeArrayCollectionsAndFlush($dest, $source): void
    {
        $objectManager = $this->managerRegistry->getManager();

        // clone common arrayCollections
        if (method_exists($source, 'getTags')) {
            $dest->duplicateArrayCollection('addTag', $source->getTags());
        }

        // clone duplicate materials
        $tmpMaterials = $source->getMaterials();
        if (!empty($tmpMaterials)) {
            foreach ($tmpMaterials as $tmpMaterial) {
                $newMat = clone $tmpMaterial;
                $dest->addMaterial($newMat);
            }
        }

        $objectManager->persist($dest);
        $objectManager->flush();
    }
}

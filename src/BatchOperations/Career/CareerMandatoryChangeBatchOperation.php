<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 23/06/14
 * Time: 10:13.
 */
namespace App\BatchOperations\Career;

use App\BatchOperations\AbstractBatchOperation;
use App\Entity\Core\AbstractCareer;
use App\Entity\Core\AbstractCareerTraining;
use App\Entity\Core\AbstractTraining;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class CareerMandatoryChangeBatchOperation
 */

final class CareerMandatoryChangeBatchOperation extends AbstractBatchOperation
{

    /**
     * @var string
     */
    protected string $targetClass = AbstractCareerTraining::class;
    private ManagerRegistry $managerRegistry;
    private Security $security;

    public function __construct(ManagerRegistry $managerRegistry, Security $security)
    {
        parent::__construct();
        $this->managerRegistry = $managerRegistry;
        $this->security = $security;
    }

    /**
     *
     * @return mixed
     */
    public function execute(array $idList = [], array $options = []): bool
    {
        $em = $this->managerRegistry->getManager();
        /* @var AbstractCareerTraining[] $careerTrainings */
        $careerTrainings     = $this->getObjectList($idList);

        $mandatory = $options['mandatory'] ?? null;
        if ($mandatory === null) {
            // Vous pouvez lever une exception ou retourner false selon votre logique
            return false;
        }

        //changing mandatory
        /** @var AbstractCareerTraining $careerTraining */
        foreach ($careerTrainings as $careerTraining) {
            $careerTraining->setMandatory($mandatory);
        }

        $em->flush();

        return true;
    }
}

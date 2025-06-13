<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 23/06/14
 * Time: 10:13.
 */
namespace App\BatchOperations\Session;

use App\BatchOperations\AbstractBatchOperation;
use App\Entity\Core\AbstractInscription;
use App\Entity\Core\AbstractSession;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class InscriptionStatusChangeBatchOperation.
 */

final class SessionRegistrationChangeBatchOperation extends AbstractBatchOperation
{

    /**
     * @var string
     */
    protected string $targetClass = AbstractSession::class;
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
        /* @var AbstractInscription[] $inscriptions */
        $sessions     = $this->getObjectList($idList);

        $registration = $options['registration'] ?? null;
        if ($registration === null) {
            // Vous pouvez lever une exception ou retourner false selon votre logique
            return false;
        }
        //changing status
        /** @var AbstractSession $session */
        foreach ($sessions as $session) {
            if($this->security->isGranted('EDIT', $session->getTraining())) {
                $session->setRegistration($registration);
            }
        }

        $em->flush();

        return true;
    }
}

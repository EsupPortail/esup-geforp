<?php

namespace App\Controller\Back;

use App\Entity\Back\Session;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Back\Inscription;
use App\Entity\Back\Presence;
use App\Form\Type\PresenceType;
use App\Controller\Core\AbstractInscriptionController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;


#[Route("/inscription")]final class InscriptionController extends AbstractInscriptionController
{
    protected string $inscriptionClass = Inscription::class;

    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    #[Rest\View(serializerGroups: ['Default', 'inscription'], serializerEnableMaxDepthChecks: true)]
     #[Route("/editpresence/{presence}", name: "presence.edit", options: ["expose" => true], defaults: ["_format" => "json"])]
     #[Groups(["Default", "inscription"])]
    public function editpresence(SerializerInterface $serializer, Presence $presence,ManagerRegistry $managerRegistry, Request $request, int $id = null ): array
    {
        $presence = $managerRegistry->getRepository(Presence::class)->find($presence);
        if (!$presence) {
            throw $this->createNotFoundException();
        }
        $form = $this->createForm(PresenceType::class, $presence);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                //Mise à jour presence
                $objectManager = $managerRegistry->getManager();
                $objectManager->flush();
            }
        }

        return ['form' => $form->createView(), 'presence' => $presence];

    }

    #[Route("load-actiontype/{id}", name: "inscription.loadActionType", methods: ["get"])]
    public function loadActionType(int $id, ManagerRegistry $managerRegistry): JsonResponse
    {
        $repository = $managerRegistry->getRepository(Inscription::class)->find($id);
        $inscription = $repository->find($id);

        if (!$inscription) {
            return new JsonResponse(["error" => 'Inscription non trouvée'], 400);
        }
        $entityManager = $managerRegistry->getManager();
        $inscription->checkAndLoadActionType($entityManager);

        return new JsonResponse(["sucess" => 'ActionType chargée']);
    }

}

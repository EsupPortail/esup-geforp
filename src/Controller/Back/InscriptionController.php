<?php

namespace App\Controller\Back;

use App\Entity\Back\Session;
use Doctrine\Persistence\ManagerRegistry;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Back\Inscription;
use App\Entity\Back\Presence;
use App\Form\Type\PresenceType;
use App\Controller\Core\AbstractInscriptionController;
use Symfony\Component\HttpFoundation\Request;


 #[Route("/inscription")]final class InscriptionController extends AbstractInscriptionController
{
    protected $inscriptionClass = Inscription::class;

    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

     #[Route("/editpresence/{presence}", name: "presence.edit", options: ["expose" => true], defaults: ["_format" => "json"])]
     #[Groups(["Default", "inscription"])]

    public function editpresence(Presence $presence,ManagerRegistry $managerRegistry, Request $request, int $id ): array
    {
        $presence = $managerRegistry->getRepository(Presence::class, $id);
        if (!$presence) {
            throw $this->createNotFoundException();
        }
        $form = $this->createForm(PresenceType::class, $presence);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                //Mise à jour presence
                $objectManager = $this->managerRegistry->getManager();
                $objectManager->flush();
            }
        }

        return ['form' => $form->createView(), 'presence' => $presence];

    }

}

<?php

namespace App\Controller\Back;

use App\Controller\Core\AbstractSessionController;
use FOS\RestBundle\Controller\Annotations as Rest;
use JMS\SecurityExtraBundle\Annotation\SecureParam;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Back\Inscription;
use App\Entity\Back\Presence;
use App\Form\Type\PresenceType;
use App\Controller\Core\AbstractInscriptionController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormError;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Route("/inscription")
 */
class InscriptionController extends AbstractInscriptionController
{
    protected $inscriptionClass = Inscription::class;

    /**
     * This action attach a form to the return array when the user has the permission to edit the training.
     *
     * @Route("/editpresence/{presence}", name="presence.edit", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("presence", class="App\Entity\Back\Presence", options={"id" = "presence"})
     * @Rest\View(serializerGroups={"Default", "inscription"}, serializerEnableMaxDepthChecks=true)
     */
    public function editpresenceAction(Presence $presence, Request $request )
    {
        $inscription = $presence->getInscription();
        $form        = $this->createForm(PresenceType::class, $presence);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                //Mise à jour presence
                $em = $this->getDoctrine()->getManager();
                $em->flush();
            }
        }

        return array('form' => $form->createView(), 'presence' => $presence);

    }

}

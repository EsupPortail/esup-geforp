<?php

/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 25/08/2015
 * Time: 12:30.
 */

namespace App\Controller\Core;

use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\Core\Email;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EmailController.
 *
 * @Route("/email")
 */
class EmailController extends AbstractController
{
    /**
     * @Route("/search", name="email.search", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View(serializerGroups={"Default", "email"}, serializerEnableMaxDepthChecks=true)
     */
    public function searchAction(Request $request)
    {
        $search = $this->get('sygefor_email.search');
        $search->handleRequest($request);
        $requestFilters = $request->request->get('filters');

        // security check
        if (isset($requestFilters['trainee.id']) && !$this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_core.access_right.trainee.all.view')) {
            $search->addTermFilter('trainee.organization.id', $this->getUser()->getOrganization()->getId());
        }
        if (isset($requestFilters['session.id']) && !$this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_core.access_right.training.all.view')) {
            $search->addTermFilter('session.training.organization.id', $this->getUser()->getOrganization()->getId());
        }
        if (isset($requestFilters['trainer.id']) && !$this->get('sygefor_core.access_right_registry')->hasAccessRight('sygefor_core.access_right.trainer.all.view')) {
            $search->addTermFilter('trainer.organization.id', $this->getUser()->getOrganization()->getId());
        }

        return $search->search();
    }

    /**
     * @Route("/view/{id}", requirements={"id" = "\d+"}, name="email.view", options={"expose"=true}, defaults={"_format" = "json"})
     * @ParamConverter("email", class="SygeforCoreBundle:Email", options={"id" = "id"})
     * @Rest\View(serializerGroups={"Default", "session", "user"}, serializerEnableMaxDepthChecks=true)
     */
    public function viewAction(Email $email)
    {
        return array('email' => $email);
    }
}

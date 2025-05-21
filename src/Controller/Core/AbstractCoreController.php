<?php

namespace App\Controller\Core;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\View;
use FOS\RestBundle\Context\Context;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Core\AbstractTraining;
use App\Entity\Back\Organization;
use App\Form\Type\OrganizationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractCoreController extends AbstractController
{
    public function __construct(private readonly \Doctrine\Persistence\ManagerRegistry $managerRegistry)
    {
    }

    #[Route(path: '/', name: 'core.index')]
    #[Template("Core/index.html.twig")]
    public function index(): Response
    {
        return $this->render('Core/index.html.twig');
    }

    /**
     * @Rest\View(serializerEnableMaxDepthChecks=true)
     *
     * @todo : blaise, security
     */
    #[Rest\View(serializerEnableMaxDepthChecks: true)]
    #[Route(path: '/search', name: 'core.search', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function search(Request $request)
    {
        $search = $this->get('sygefor.search');
        $search->handleRequest($request);

        return $search->search();
    }

    /**
     * @Rest\View(serializerEnableMaxDepthChecks=true)
     */
    #[Rest\View(serializerEnableMaxDepthChecks: true)]
    #[Route(path: '/entity', name: 'core.entity', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function entity(Request $request): View
    {
        // retrieve the entity
        $objectManager = $this->managerRegistry->getManager();
        $class = $request->get('class');
        $id = $request->get('id');
        $entity = $objectManager->getRepository($class)->find($id);
        if ($entity === null) {
            throw new NotFoundHttpException();
        }

        // security
/*        $security = $this->get('security.context');
        if (!$security->isGranted('VIEW', $entity)) {
            throw new AccessDeniedHttpException();
        }*/

        // determine the serialization groups
        $groups = ['Default'];
        if ($entity instanceof AbstractTraining) {
            $groups[] = 'training';
        }

        $reflectionClass = new \ReflectionClass($entity);
        $groups[] = strtolower($reflectionClass->getShortName());

        // return the view
        $view = new View($entity);
//        $view->setSerializationContext(SerializationContext::create()->setGroups($groups));
        $context = new Context();
        $context->setGroups($groups);

        $view->setContext($context);


        return $view;
    }
}

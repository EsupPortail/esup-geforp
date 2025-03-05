<?php

/**
 * Created by PhpStorm.
 * Organization: erwan
 * Date: 5/30/16
 * Time: 5:41 PM.
 */

namespace App\Controller\Core;

use MongoDB\Driver\Manager;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Back\Organization;
use App\Form\Type\OrganizationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Persistence\ManagerRegistry;
/**
 * Class OrganizationController.
 *
 */
#[Route(path: '/admin/organizations')]final class OrganizationController extends AbstractController
{
    private static string $ORGANIZATION_CLASS = Organization::class;

    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    #[Route(path: '/', name: 'organization.index')]
    public function index(ManagerRegistry $doctrine): \Symfony\Component\HttpFoundation\Response
    {
        $organizations = $doctrine->getManager()
            ->getRepository(self::$ORGANIZATION_CLASS)->findBy([], ['name' => 'ASC'])
        ;

        return $this->render('Core/views/Organization/index.html.twig', ['organizations' => $organizations]);
    }

    #[Route(path: '/add', name: 'organization.add')]
    public function add(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $organization = new self::$ORGANIZATION_CLASS();
        $form = $this->createForm(OrganizationType::class, $organization);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->managerRegistry->getManager();
                $em->persist($organization);
                $em->flush();

                $this->get('session')->getFlashBag()->add('success', 'Le centre a bien été ajouté.');

                return $this->redirectToRoute('organization.index');
            }
        }

        return $this->render('Core/views/Organization/edit.html.twig', ['form' => $form->createView(), 'organization' => $organization]);
    }

    /**
     * @param AbstractOrganization $organization
     *
     *
     */
    #[Route(path: '/{id}/edit', name: 'organization.edit', requirements: ['id' => '\d+'], options: ['expose' => true])]
    public function edit(Request $request, Organization $organization, ManagerRegistry $managerRegistry, int $id): \Symfony\Component\HttpFoundation\Response
    {
        $organization = $managerRegistry->getRepository(Organization::class)->find($id);
        if (!$organization){
            throw $this->createNotFoundException();
        }
        $form = $this->createForm(OrganizationType::class, $organization);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->managerRegistry->getManager()->flush();
                $this->get('session')->getFlashBag()->add('success', 'Le centre a bien été mis à jour.');

                return $this->redirectToRoute('organization.index');
            }
        }

        return $this->render('Core/views/Organization/edit.html.twig', ['form' => $form->createView(), 'organization' => $organization]);
    }
}

<?php

/**
 * Created by PhpStorm.
 * Organization: erwan
 * Date: 5/30/16
 * Time: 5:41 PM.
 */

namespace App\Controller\Core;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\Organization;
use App\Form\Type\OrganizationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class OrganizationController.
 *
 * @Route("/admin/organizations")
 */
class OrganizationController extends AbstractController
{
    protected $organizationClass = Organization::class;

    /**
     * @Route("/", name="organization.index")
     */
    public function indexAction()
    {
        $organizations = $this->get('doctrine')->getManager()
            ->getRepository($this->organizationClass)->findBy(array(), array('name' => 'ASC'))
        ;

        return $this->render('Core/views/Organization/index.html.twig', array(
            'organizations' => $organizations,
        ));
    }

    /**
     * @param Request $request
     *
     * @Route("/add", name="organization.add")
     *
     * @return array|RedirectResponse
     */
    public function addAction(Request $request)
    {
        $organization = new $this->organizationClass();
        $form = $this->createForm(OrganizationType::class, $organization);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($organization);
                $em->flush();

                $this->get('session')->getFlashBag()->add('success', 'Le centre a bien été ajouté.');

                return $this->redirect($this->generateUrl('organization.index'));
            }
        }

        return $this->render('Core/views/Organization/edit.html.twig', array(
            'form' => $form->createView(),
            'organization' => $organization,
        ));
    }

    /**
     * @param Request              $request
     * @param AbstractOrganization $organization
     *
     * @Route("/{id}/edit", requirements={"id" = "\d+"}, name="organization.edit", options={"expose"=true})
     * @ParamConverter("organization", class="App\Entity\Organization", options={"id" = "id"})
     *
     * @return array|RedirectResponse
     */
    public function editAction(Request $request, Organization $organization)
    {
        $form = $this->createForm(OrganizationType::class, $organization);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $this->getDoctrine()->getManager()->flush();
                $this->get('session')->getFlashBag()->add('success', 'Le centre a bien été mis à jour.');

                return $this->redirect($this->generateUrl('organization.index'));
            }
        }

        return $this->render('Core/views/Organization/edit.html.twig', array(
            'form' => $form->createView(),
            'organization' => $organization,
        ));
    }
}

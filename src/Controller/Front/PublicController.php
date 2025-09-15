<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/15/16
 * Time: 11:00 AM
 */

namespace App\Controller\Front;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use http\Env\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Back\Alert;
use App\Entity\Back\MultipleAlert;
use App\Entity\Back\SingleAlert;
use App\Form\Type\ProgramAlertType;

use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/')]
class PublicController extends AbstractController
{

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route(path: '/{page}', name: 'front.public.index', requirements: ['page' => '\d+'])]
    public function index(Request $request, ManagerRegistry $doctrine, int $page = 1): \Symfony\Component\HttpFoundation\Response
    {
        if ($request->get('shibboleth') == 1) {
            if ($request->get('error') == "activation") {
                $this->get('session')->getFlashBag()->add('warning', "Votre compte doit être activé par un administrateur avant de pouvoir vous connecter.");
            }
        }
        
        return $this->render('Front/Public/index.html.twig', ['user' => $this->getUser(), 'page' => $page]);
    }

    /**
     * @return array{user: \Symfony\Component\Security\Core\User\UserInterface|null}
     */
    #[Route(path: '/login', name: 'front.public.login')]
    public function login(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('Front/Public/login.html.twig',['user' => $this->getUser()]);
    }

    /**
     * @return array{etablissements: \App\Entity\Back\Institution[]}
     */
    #[Route(path: '/contact', name: 'front.public.contact')]
    public function contact(ManagerRegistry $doctrine): \Symfony\Component\HttpFoundation\Response
    {
        // Récupération des établissements de la plate-forme
        $institutions = $doctrine->getRepository(\App\Entity\Back\Institution::class)->findBy([], ['name' => 'ASC']);
        $instContacts = [];
        foreach ($institutions as $institution) {
            if ($institution->getEmail() !== null)
                $instContacts[] = $institution;
        }
        return $this->render('Front/Public/contact.html.twig', ['etablissements' => $instContacts, ]);
    }

    /**
     * @return array{contact_mail: mixed[]|bool|float|int|string|\UnitEnum|null, front_url: mixed[]|bool|float|int|string|\UnitEnum|null}
     */
    #[Route(path: '/faq', name: 'front.public.faq')]
    public function faq(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('Front/Public/faq.html.twig', ['contact_mail' => $this->getParameter('contact_mail'), 'front_url' => $this->getParameter('front_url')]);
    }

    /**
     * @return array{user: \Symfony\Component\Security\Core\User\UserInterface|null}
     */
    #[Route(path: '/about', name: 'front.public.about')]
    public function about(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('Front/Public/about.html.twig', ['user' => $this->getUser()]);
    }

    /**
     * @return array{user: \Symfony\Component\Security\Core\User\UserInterface|null}
     */
    #[Route(path: '/legalNotice', name: 'front.public.legalNotice')]
    public function legalNotice(): \Symfony\Component\HttpFoundation\Response
    {
        return $this->render('Front/Public/legalNotice.html.twig',['user' => $this->getUser()]);
    }

}
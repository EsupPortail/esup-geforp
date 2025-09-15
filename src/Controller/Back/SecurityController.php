<?php

namespace App\Controller\Back;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
{
 #[Route("/login", name: "app_login")]

    public function login(AuthenticationUtils $authenticationUtils): Response
    {
         if ($this->getUser() instanceof \Symfony\Component\Security\Core\User\UserInterface) {
             return $this->redirectToRoute('target_path');
         }

        // get the login error if there is one
        $authenticationException = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('Security/login.html.twig', ['last_username' => $lastUsername, 'error' => $authenticationException]);
    }

    #[Route("/logout", name: "app_logout")]
    public function logout(): never
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

}

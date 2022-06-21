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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Alert;
use App\Entity\MultipleAlert;
use App\Entity\SingleAlert;
use App\Form\Type\ProgramAlertType;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Route("/")
 */
class PublicController extends AbstractController
{

    /**
     * @Route("/{page}", name="front.public.index", requirements={"page": "\d+"})
     * @Template("Front/Public/index.html.twig")
     */
    public function indexAction(Request $request, ManagerRegistry $doctrine, $page = 1)
    {
        if ($request->get('shibboleth') == 1) {
            if ($request->get('error') == "activation") {
                $this->get('session')->getFlashBag()->add('warning', "Votre compte doit être activé par un administrateur avant de pouvoir vous connecter.");
            }
        }

        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];

        return array('user' => $trainee);
    }

    /**
     * @Route("/login", name="front.public.login")
     * @Template("Front/Public/login.html.twig")
     */
    public function loginAction(Request $request, ManagerRegistry $doctrine)
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];
        return array('user' => $trainee);
    }

    /**
     * @Route("/contact", name="front.public.contact")
     * @Template("Front/Public/contact.html.twig")
     */
    public function contactAction(Request $request, ManagerRegistry $doctrine)
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];
        return array('user' => $trainee, 'contact_mail' => $this->getParameter('contact_mail'));
    }

    /**
     * @Route("/faq", name="front.public.faq")
     * @Template("Front/Public/faq.html.twig")
     */
    public function faqAction(Request $request, ManagerRegistry $doctrine)
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];
        return array('user' => $trainee, 'contact_mail' => $this->getParameter('contact_mail'));
    }

    /**
     * @Route("/about", name="front.public.about")
     * @Template("Front/Public/about.html.twig")
     */
    public function aboutAction(Request $request, ManagerRegistry $doctrine)
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];
        return array('user' => $trainee);
    }

    /**
     * @Route("/legalNotice", name="front.public.legalNotice")
     * @Template("Front/Public/legalNotice.html.twig")
     */
    public function legalNoticeAction(Request $request, ManagerRegistry $doctrine)
    {
        $user = $this->getUser();
        $arTrainee = $doctrine->getRepository('App\Entity\Trainee')->findByEmail($user->getCredentials()['mail']);
        $trainee = $arTrainee[0];
        return array('user' => $trainee);
    }

}
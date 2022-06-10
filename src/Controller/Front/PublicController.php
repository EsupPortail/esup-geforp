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
    public function indexAction(Request $request, $page = 1)
    {
        if ($request->get('shibboleth') == 1) {
            if ($request->get('error') == "activation") {
                $this->get('session')->getFlashBag()->add('warning', "Votre compte doit être activé par un administrateur avant de pouvoir vous connecter.");
            }
        }

        return array('user' => $this->getUser());
    }

    /**
     * @Route("/login", name="front.public.login")
     * @Template()
     */
    public function loginAction(Request $request)
    {
        return array('user' => $this->getUser());
    }

    /**
     * @Route("/contact", name="front.public.contact")
     * @Template()
     */
    public function contactAction(Request $request)
    {
        return array('user' => $this->getUser());
    }

    /**
     * @Route("/faq", name="front.public.faq")
     * @Template()
     */
    public function faqAction(Request $request)
    {
        return array('user' => $this->getUser());
    }

    /**
     * @Route("/about", name="front.public.about")
     * @Template()
     */
    public function aboutAction(Request $request)
    {
        return array('user' => $this->getUser());
    }

    /**
     * @Route("/legalNotice", name="front.public.legalNotice")
     * @Template()
     */
    public function legalNoticeAction(Request $request)
    {
        return array('user' => $this->getUser());
    }

}
<?php

namespace App\Controller\Api;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/api")
 */
class ProxyController extends AbstractController
{
    /**
     * @Route("/proxy.html", name="api.xdomain.proxy")
     */
    public function proxyAction(Request $request)
    {
        $front_url = $this->container->getParameter('front_url');
        $front_url = preg_replace('/#$/', '', $front_url);
        preg_match('%^((?:http://|https://)[A-Za-z0-9.-]+(?!.*\|\w*$)(?::\d+)?)(.*)%sim', $front_url, $matches);
        $host = $matches[1];
        $path = $matches[2];

        return $this->render('SygeforApiBundle:Proxy:proxy.html.twig', array(
            'host' => $host,
            'path' => $path,
        ));
    }
}

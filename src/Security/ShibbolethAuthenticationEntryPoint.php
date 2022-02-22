<?php

namespace App\Security;

use KULeuven\ShibbolethBundle\Security\ShibbolethAuthenticationEntryPoint as BaseShibbolethAuthenticationEntryPoint;
use KULeuven\ShibbolethBundle\Service\Shibboleth;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * Class ShibbolethAuthenticationEntryPoint.
 */
class ShibbolethAuthenticationEntryPoint extends BaseShibbolethAuthenticationEntryPoint
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param Shibboleth $shibboleth
     * @param $container
     */
    public function __construct(Shibboleth $shibboleth, ContainerInterface $container)
    {
        parent::__construct($shibboleth);

        $this->container = $container;
    }

    /**
     * Il there is a BadCredentialsException, redirect user to FO.
     *
     * @param Request                 $request
     * @param AuthenticationException $authException
     *
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        if ($authException && $authException instanceof BadCredentialsException) {
            //throw new PreconditionFailedHttpException($authException->getMessage());
            // redirect user to registration form
            $front_url = $this->container->getParameter('front_url');
            $qs = $request->getQueryString();
            $url = $front_url.'/login?shibboleth=1&error=1'.($qs ? '&'.$qs : '');

            return new RedirectResponse($url);
        }

        return parent::start($request, $authException);
    }
}

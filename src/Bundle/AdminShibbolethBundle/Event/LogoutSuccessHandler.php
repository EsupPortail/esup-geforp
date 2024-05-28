<?php

namespace App\Bundle\AdminShibbolethBundle\Event;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    private $router;

    private $logout_path;
    private $logout_target;

    public function __construct($config, Router $router)
    {
        $this->router = $router;
        $this->logout_path = $config['logout_path'];
        $this->logout_target = $config['logout_target'];
    }

    public function onLogoutSuccess(Request $request)
    {
        return new RedirectResponse("{$request->getSchemeAndHttpHost()}/".trim($this->logout_path, '/')."?target=".(empty($this->logout_target)? $request->getUri() : "{$request->getSchemeAndHttpHost()}{$this->router->generate($this->logout_target)}"));
    }
}
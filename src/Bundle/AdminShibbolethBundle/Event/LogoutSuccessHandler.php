<?php

namespace App\Bundle\AdminShibbolethBundle\Event;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;

final class LogoutSuccessHandler
{
    private $logout_path;

    private $logout_target;

    public function __construct($config, private readonly Router $router)
    {
        $this->logout_path = $config['logout_path'];
        $this->logout_target = $config['logout_target'];
    }

    public function onLogoutSuccess(Request $request): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return new RedirectResponse(sprintf('%s/', $request->getSchemeAndHttpHost()).trim((string) $this->logout_path, '/')."?target=".(empty($this->logout_target)? $request->getUri() : $request->getSchemeAndHttpHost() . $this->router->generate($this->logout_target)));
    }
}
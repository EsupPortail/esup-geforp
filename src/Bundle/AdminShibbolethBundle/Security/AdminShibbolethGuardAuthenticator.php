<?php

namespace App\Bundle\AdminShibbolethBundle\Security;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use App\Bundle\AdminShibbolethBundle\Security\User\AdminShibbolethUserProviderInterface;

/**
 * Class AdminShibbolethGuardAuthenticator
 * @package App\Bundle\AdminShibbolethBundle\Security
 */
class AdminShibbolethGuardAuthenticator extends AbstractGuardAuthenticator
{

    /**
     * @var array
     */
    private $config;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var string
     */
    private $login_path;

    /**
     * @var string
     */
    private $logout_path;

    /**
     * @var string
     */
    private $login_target;

    /**
     * @var string
     */
    private $session_id;

    /**
     * @var string
     */
    private $username;

    /**
     * @var array
     */
    private $attributes;

    /**
     * ShibbolethGuardAuthenticator constructor.
     * @param array $config
     * @param Router $router
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(array $config, Router $router, TokenStorageInterface $tokenStorage)
    {
        $this->config = $config;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
        $this->login_path = $config['login_path'];
        $this->logout_path = $config['logout_path'];
        $this->login_target = $config['login_target'];
        $this->session_id = $config['session_id'];
        $this->username = $config['username'];
        $this->attributes = $config['attributes'];
        if(!in_array($this->username, $this->attributes))
            throw new InvalidConfigurationException("Shibboleth configuration error : the value of username parameter must be in attributes list parameter");
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function supports(Request $request){
        if (!empty($this->getAttribute($request, $this->session_id))) {
            return true;
        }
        return false;
    }

    /**
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return RedirectResponse
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new RedirectResponse("{$request->getSchemeAndHttpHost()}/".trim($this->login_path, '/')."?target=".(empty($this->login_target)? $request->getUri() : "{$request->getSchemeAndHttpHost()}{$this->router->generate($this->login_target)}"));
    }

    /**
     * @param Request $request
     * @return array|null
     */
    public function getCredentials(Request $request)
    {
        $credentials = array();
        $credentials['username'] = $this->getAttribute($request, $this->username);
        foreach($this->attributes as $attribute){
            $credentials[$attribute] = $this->getAttribute($request, $attribute);
        }
        return $credentials;
    }

    /**
     * @param mixed $credentials
     * @param UserProviderInterface $userProvider
     * @return UserInterface
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if(empty($credentials['username']))
            throw new UsernameNotFoundException("The username attribute is empty");
        if($userProvider instanceof ShibbolethUserProviderInterface)
            return $userProvider->loadUser($credentials);
        else if($userProvider instanceof  UserProviderInterface)
            return $userProvider->loadUserByUsername($credentials['username']);
        return null;

    }

    /**
     * @param mixed $credentials
     * @param UserInterface $user
     * @return bool
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return JsonResponse
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
//        return new JsonResponse(array('message' => $exception->getMessageKey()), Response::HTTP_FORBIDDEN);
        return new JsonResponse(array('message' => "Vous n'avez pas les droits pour accéder à cette application"), Response::HTTP_FORBIDDEN);
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $providerKey
     * @return null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return null;
    }

    /**
     * @return bool
     */
    public function supportsRememberMe()
    {
        return false;
    }

    /**
     * @param Request $request
     * @param $name
     * @return mixed
     */
    private function getAttribute(Request $request, $name){
        $attributes = array($name, strtoupper($name), "HTTP_".strtoupper($name), "REDIRECT_{$name}");
        foreach($attributes as $attribute)
            if(!empty($request->server->has($attribute))) return $request->server->get($attribute);
    }
}

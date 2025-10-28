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
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use App\Bundle\AdminShibbolethBundle\Security\User\AdminShibbolethUserProviderInterface;
use App\Bundle\AdminShibbolethBundle\Security\User\AdminShibbolethUserProvider;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * Class AdminShibbolethGuardAuthenticator
 * @package App\Bundle\AdminShibbolethBundle\Security
 */
final class AdminShibbolethGuardAuthenticator extends  AbstractAuthenticator
{

    /**
     * @var string
     */
    private $login_path;

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
    private $attributes = [];


    /**
     * ShibbolethGuardAuthenticator constructor.
     */
    public function __construct(array $config, private readonly Router $router, private readonly AdminShibbolethUserProvider $shibUserProvider)
    {
        $this->login_path = $config['login_path'];
        $this->login_target = $config['login_target'];
        $this->session_id = $config['session_id'];
        $this->username = $config['username'];
        $this->attributes = $config['attributes'];
        if(!in_array($this->username, $this->attributes))
            throw new InvalidConfigurationException("Shibboleth configuration error : the value of username parameter must be in attributes list parameter");
    }

    public function supports(Request $request): bool{
        if (!empty($this->getAttribute($request, $this->session_id))) {
            return true;
        }

        return false;
    }

    /**
     * @param AuthenticationException|null $authenticationException
     */
    public function start(Request $request, AuthenticationException $authenticationException = null): Response
    {
        return new RedirectResponse(sprintf('%s/', $request->getSchemeAndHttpHost()).trim($this->login_path, '/')."?target=".(empty($this->login_target)? $request->getUri() : $request->getSchemeAndHttpHost() . $this->router->generate($this->login_target)));
    }

    public function getCredentials(Request $request): ?array
    {
        $credentials = [];
        $credentials['username'] = $this->getAttribute($request, $this->username);
        foreach($this->attributes as $attribute){
            $credentials[$attribute] = $this->getAttribute($request, $attribute);
        }

        return $credentials;
    }

    public function getUser(mixed $credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        if(empty($credentials['username'])) {
            throw new UserNotFoundException("The username attribute is empty");
        }
        if($userProvider instanceof AdminShibbolethUserProviderInterface) {
            return $userProvider->loadUser($credentials);
        }
        return $userProvider->loadUserByIdentifier($credentials['username']);

        return null;

    }

    public function checkCredentials(mixed $credentials, UserInterface $user): bool
    {
        return true;
    }

    /**
     * @return JsonResponse
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $authenticationException): Response
    {
//        return new JsonResponse(array('message' => $exception->getMessageKey()), Response::HTTP_FORBIDDEN);
        return new JsonResponse(['message' => "Vous n'avez pas les droits pour accéder à cette application"], Response::HTTP_FORBIDDEN);
    }

    /**
     * @param string $providerKey
     * @return null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey): ?Response
    {
        return null;
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    /**
     * @param $name
     * @return mixed
     */
    private function getAttribute(Request $request, string $name){
        $attributes = [$name, strtoupper($name), "HTTP_".strtoupper($name), sprintf('REDIRECT_%s', $name)];
        foreach($attributes as $attribute)
            if(!empty($request->server->has($attribute))) return $request->server->get($attribute);

            return null;
    }

    public function authenticate(Request $request): Passport
    {
        $credentials = $this->getCredentials($request);
        
	if (empty($credentials ['username'])) {
            throw new UserNotFoundException("The username attribute is empty");
        }

        $userBadge = new UserBadge($credentials['username'], function ($userIdentifier) {
                // optionally pass a callback to load the User manually
                $user = $this->shibUserProvider->loadUserByIdentifier($userIdentifier);
                if (!$user) {
                    throw new UserNotFoundException();
                }

                return $user;
            });
	$pass = new Passport($userBadge, new CustomCredentials(fn($credentials, $user) => true, $credentials['username']));
        return $pass;
    }
}

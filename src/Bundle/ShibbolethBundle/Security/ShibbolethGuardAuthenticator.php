<?php

namespace App\Bundle\ShibbolethBundle\Security;

use Doctrine\Persistence\ManagerRegistry;
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
use App\Bundle\ShibbolethBundle\Security\User\ShibbolethUserProviderInterface;
use App\Bundle\ShibbolethBundle\Security\User\ShibbolethUserProvider;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * Class ShibbolethGuardAuthenticator
 * @package App\Bundle\ShibbolethBundle\Security
 */
final class ShibbolethGuardAuthenticator extends AbstractAuthenticator
{

    /**
     * @var string
     */
    private mixed $login_path;

    /**
     * @var string
     */
    private mixed $login_target;

    /**
     * @var string
     */
    private mixed $session_id;

    /**
     * @var string
     */
    private mixed $username;

    /**
     * @var array
     */
    private mixed $attributes = [];

    private $request;

    /**
     * ShibbolethGuardAuthenticator constructor.
     */
    public function __construct(array $config, private readonly Router $router, private readonly ShibbolethUserProvider $shibUserProvider)
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
    public function start(Request $request, AuthenticationException $authenticationException = null): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return new RedirectResponse(sprintf('%s/', $request->getSchemeAndHttpHost()) . trim($this->login_path, '/')
            . "?target=" . (empty($this->login_target) ? $request->getUri() : $request->getSchemeAndHttpHost() . $this->router->generate($this->login_target)));
    }

    /**
     * @return mixed[]
     */
    public function getCredentials(Request $request): array
    {
        $credentials = [];
        $credentials['username'] = $this->getAttribute($request, $this->username);
        foreach($this->attributes as $attribute){
            $credentials[$attribute] = $this->getAttribute($request, $attribute);
        }
        if (empty($credentials['username'])) {
            throw new UserNotFoundException("Le nom d'utilisateur est manquant ou vide.");
        }
        return $credentials;
    }

    public function getUser(mixed $credentials, UserProviderInterface $userProvider): ?UserInterface
    {
        if(empty($credentials['username']))
            throw new UserNotFoundException("The username attribute is empty");

        if($userProvider instanceof ShibbolethUserProviderInterface) {
            $us =  $userProvider->loadUser($credentials);

            // test responsable N+1 et ajout role
            $tabUs = $this->doctrine->getRepository('App\Entity\Back\Trainee')->findBy(array('emailsup' => $us->getCredentials()['mail']));
            if (!empty($tabUs))
                $us->setRoles('ROLE_RESP');

            return($us);
        }
        else if($userProvider instanceof  UserProviderInterface) {
            $us = $userProvider->loadUserByUsername($credentials['username']);
            return($us);
        }

        return null;

    }

    public function checkCredentials(mixed $credentials, UserInterface $user): bool
    {
        return true;
    }

    /**
     * @return JsonResponse
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $authenticationException): JsonResponse
    {
        return new JsonResponse(['message' => $authenticationException->getMessageKey()], Response::HTTP_FORBIDDEN);
    }

    /**
     * @return null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token,string $firewallName): null
    {
        return null;
    }

    public function supportsRememberMe(Request $request): bool
    {
        if (!empty($this->getAttribute($request, $this->session_id))) {
            return true;
        }
        return false;
    }

    /**
     * @param $name
     * @return mixed
     */
    private function getAttribute(Request $request, $name): mixed
    {
        $attributes = [$name, strtoupper((string) $name), "HTTP_" . strtoupper((string) $name), sprintf('REDIRECT_%s', $name)];
        foreach ($attributes as $attribute)
            if ($request->server->has($attribute))
                return $request->server->get($attribute);
        return null;
    }

    /**
     * @param $request
     */
    public function authenticate(Request $request): Passport
    {
        $credentials = $this->getCredentials($request);
	$this->request = $request;
        if (empty($credentials ['username'])) {
            throw new UserNotFoundException("The username attribute is empty");
        }
        $userBadge = new UserBadge($credentials['username'],  
		function ($username) {
			$credentials = $this->getCredentials($this->request);
                	$user = $this->shibUserProvider->loadUser($credentials);
	                if (!$user) {
        	            throw new UserNotFoundException();
                	}
	                return $user;
		}
            );

	$pass = new Passport($userBadge, new CustomCredentials(fn($credentials, $user) => true, $credentials['username']));
        return $pass;
    }
}

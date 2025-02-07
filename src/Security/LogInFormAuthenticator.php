<?php
namespace App\Security;

use App\Entity\Core\User;
use Doctrine\ORM\EntityManagerInterface;
use http\Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

final class LogInFormAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;

    /**
     * @var string
     */
    public const string LOGIN_ROUTE = 'app_login';
    private TokenStorageInterface $tokenStorage;


    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly UrlGeneratorInterface $urlGenerator, private readonly CsrfTokenManagerInterface $csrfTokenManager, private readonly UserPasswordHasherInterface $userPasswordEncoder, TokenStorageInterface $tokenStorage)
    {
    }

    public function supports(Request $request): ?bool
    {
        if (self::LOGIN_ROUTE !== $request->attributes->get('_route')) {
            return false;
        }
        return $request->isMethod('POST');
    }

    /**
     * @return array{username: bool|float|int|string|null, password: bool|float|int|string|null, csrf_token: bool|float|int|string|null}
     */
    public function getCredentials(Request $request): array
    {
        $credentials = [
            'username' => $request->request->get('username'),
            'password' => $request->request->get('password'),
            'csrf_token' => $request->request->get('_csrf_token'),
        ];
        $this->tokenStorage->getToken();
        $request->getSession()->set('last_username', $credentials['username']);

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $csrfToken = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($csrfToken)) {
            throw new InvalidCsrfTokenException();
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $credentials['username']]);

        if (!$user instanceof \App\Entity\Core\User) {
            throw new UserNotFoundException('Username could not be found.');
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return $this->userPasswordEncoder->isPasswordValid($user, $credentials['password']);
    }

    public function authenticate(Request $request): Passport
    {
        $credentials = $this->getCredentials($request);
        $user = $this->getUser($credentials, $this->getUserProvider());

        return new Passport(
            $user, // L'utilisateur authentifié
            new PasswordCredentials($credentials['password']) // Les informations d'identification
        );
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Gestion des erreurs d'authentification
        return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Redirection vers la page d'origine ou page d'accueil
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('core.index'));
    }
}

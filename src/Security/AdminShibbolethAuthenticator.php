<?php

namespace App\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\CustomCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class AdminShibbolethAuthenticator extends AbstractAuthenticator implements EventSubscriberInterface
{

    private $idpUrl;

    public function __construct(
        private readonly AdminShibbolethUserProvider $shibbolethUserProvider,
    )
        //private readonly  EntityManagerInterface $entityManager,)
    {
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->idpUrl);
    }

    public function onAuthenticationFailure(
        Request                 $request,
        AuthenticationException $exception): JsonResponse|RedirectResponse
    {
        $redirectTo = $this->getRedirectUrl();
        if (in_array('application/json', $request->getAcceptableContentTypes())) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Authentication failed.',
                'redirect' => $redirectTo,
            ], Response::HTTP_FORBIDDEN);
        } else {
            return new RedirectResponse($redirectTo);
        }
    }

    protected function getRedirectUrl(): string
    {
        return 'https://stargate.datacentersud.univ-amu.fr/';
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }

    /**
     * All pages are managed by this Authenticator.
     */
    public function supports(Request $request): bool
    {
        return true;
    }

    public function authenticate(Request $request): Passport
    {
        $id = $request->server->get('eppn');
dump($request->server);
dump($id);
        return new Passport(
            new UserBadge($id, function ($userIdentifier) {
                // optionally pass a callback to load the User manually
                $user = $this->shibbolethUserProvider->loadUserByIdentifier($userIdentifier);
                if (!$user) {
                    throw new UserNotFoundException();
                }
dump($user);
                return $user;
            }),
            new CustomCredentials(fn($credentials, $user) => true, $id),
            [
                (new RememberMeBadge())->enable(),
            ]
        );
    }

    public function onLogout(LogoutEvent $logoutEvent): void
    {
        if ($logoutEvent->getResponse() !== null) {
            return;
        }
        $redirectTo = $this->urlGenerator->generate('shib_logout', [
            'return' => $this->idpUrl . '/profile/Logout',
        ]);
        $logoutEvent->setResponse(new RedirectResponse($redirectTo));
    }

    /**
     * @return array<string, mixed>
     */
    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => ['onLogout', 64]];
    }
}


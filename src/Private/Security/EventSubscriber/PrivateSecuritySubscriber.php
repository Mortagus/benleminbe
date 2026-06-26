<?php

declare(strict_types=1);

namespace App\Private\Security\EventSubscriber;

use App\Private\Security\Service\PrivateSessionGuard;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final readonly class PrivateSecuritySubscriber
{
    public function __construct(
        private PrivateSessionGuard $sessionGuard,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[AsEventListener(priority: -64)]
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $request = $event->getRequest();
        if (! str_starts_with($request->getPathInfo(), '/private')) {
            return;
        }

        $this->sessionGuard->markAuthenticated($request->getSession());
    }

    #[AsEventListener(priority: -64)]
    public function onPrivateRequest(RequestEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        if (! str_starts_with($path, '/private')) {
            return;
        }

        if ($path === '/private/login' || str_starts_with($path, '/private/security/passkeys/login')) {
            return;
        }

        if (! $request->hasSession() || ! $request->getSession()->has('private_authenticated_at')) {
            return;
        }

        if (! $this->sessionGuard->isExpired($request->getSession())) {
            $this->sessionGuard->touch($request->getSession());

            return;
        }

        $this->sessionGuard->clear($request->getSession());
        $request->getSession()->invalidate();

        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate('app_private_login', ['expired' => 1]),
            303,
        ));
    }
}

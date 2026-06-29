<?php

declare(strict_types=1);

namespace App\Private\Security\EventSubscriber;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

final class PrivateSecurityHeadersSubscriber
{
    #[AsEventListener(priority: -128)]
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (! $event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if (! str_starts_with($request->getPathInfo(), '/private')) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'publickey-credentials-get=(self), publickey-credentials-create=(self)');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'");
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->setSharedMaxAge(0);
        $response->setExpires(new \DateTimeImmutable('@0'));
        $response->headers->addCacheControlDirective('no-store', true);
    }
}

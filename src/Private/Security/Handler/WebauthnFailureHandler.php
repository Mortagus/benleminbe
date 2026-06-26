<?php

declare(strict_types=1);

namespace App\Private\Security\Handler;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Throwable;
use Webauthn\Bundle\Security\Handler\FailureHandler;

final class WebauthnFailureHandler implements FailureHandler, AuthenticationFailureHandlerInterface
{
    public function onFailure(Request $request, ?Throwable $exception = null): Response
    {
        return new JsonResponse([
            'status' => 'error',
            'errorMessage' => 'Operation impossible.',
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return $this->onFailure($request, $exception);
    }
}

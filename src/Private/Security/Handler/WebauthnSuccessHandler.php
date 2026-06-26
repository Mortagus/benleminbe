<?php

declare(strict_types=1);

namespace App\Private\Security\Handler;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Webauthn\Bundle\Security\Handler\SuccessHandler;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialUserEntity;

final class WebauthnSuccessHandler implements SuccessHandler, AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;

    private const FIREWALL_NAME = 'main';

    public function onSuccess(
        Request $request,
        ?PublicKeyCredential $publicKeyCredential = null,
        ?PublicKeyCredentialOptions $publicKeyCredentialOptions = null,
        ?PublicKeyCredentialUserEntity $userEntity = null,
    ): Response {
        return new JsonResponse([
            'status' => 'ok',
            'errorMessage' => '',
        ]);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $session = $request->getSession();
        if ($targetPath = $this->getTargetPath($session, self::FIREWALL_NAME)) {
            $this->removeTargetPath($session, self::FIREWALL_NAME);

            return new RedirectResponse($targetPath, Response::HTTP_SEE_OTHER);
        }

        return new RedirectResponse('/private', Response::HTTP_SEE_OTHER);
    }
}

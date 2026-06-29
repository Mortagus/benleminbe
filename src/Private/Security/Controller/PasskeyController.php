<?php

declare(strict_types=1);

namespace App\Private\Security\Controller;

use App\Entity\Private\PasskeyCredential;
use App\Private\Security\Repository\PasskeyCredentialRepository;
use App\Private\Security\Repository\PrivateAdminWebauthnUserRepository;
use App\Private\Security\Service\PasskeyCeremonyLogger;
use App\Private\Security\Service\PrivateAdminLoginUserFactory;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\Bundle\Security\Handler\DefaultCreationOptionsHandler;
use Webauthn\Bundle\Security\Handler\DefaultRequestOptionsHandler;
use Webauthn\Bundle\Security\Storage\Item;
use Webauthn\Bundle\Security\Storage\OptionsStorage;
use Webauthn\Bundle\Service\PublicKeyCredentialCreationOptionsFactory;
use Webauthn\Bundle\Service\PublicKeyCredentialRequestOptionsFactory;
use Webauthn\Exception\InvalidDataException;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialUserEntity;

#[Route('/private/security/passkeys', name: 'app_private_security_passkeys_')]
final class PasskeyController extends AbstractController
{
    private const REGISTER_CSRF_TOKEN_ID = 'private_webauthn_register';
    private const DELETE_CSRF_TOKEN_PREFIX = 'private_webauthn_delete_';
    private const PENDING_LABELS_SESSION_KEY = 'private_webauthn_pending_labels';

    public function __construct(
        private readonly PasskeyCredentialRepository $credentialRepository,
        private readonly PrivateAdminWebauthnUserRepository $userRepository,
        private readonly PublicKeyCredentialCreationOptionsFactory $creationOptionsFactory,
        private readonly PublicKeyCredentialRequestOptionsFactory $requestOptionsFactory,
        private readonly AuthenticatorAttestationResponseValidator $attestationResponseValidator,
        private readonly AuthenticatorAssertionResponseValidator $assertionResponseValidator,
        private readonly DefaultCreationOptionsHandler $creationOptionsHandler,
        private readonly DefaultRequestOptionsHandler $requestOptionsHandler,
        private readonly OptionsStorage $optionsStorage,
        private readonly SerializerInterface $serializer,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly Security $security,
        private readonly PrivateAdminLoginUserFactory $loginUserFactory,
        private readonly PasskeyCeremonyLogger $ceremonyLogger,
        #[Autowire(service: 'limiter.private_passkey_login_options')]
        private readonly RateLimiterFactory $loginOptionsLimiter,
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        $userEntity = $this->getCurrentUserEntity();
        $credentials = $this->credentialRepository->findAllForCurrentUser($userEntity->id);

        return $this->render('private/security/passkeys/index.html.twig', [
            'credentials' => $credentials,
            'nextLabel' => sprintf('Passkey %d', count($credentials) + 1),
            'shouldEncourageSecondPasskey' => count($credentials) < 2,
        ]);
    }

    #[Route('/register/options', name: 'register_options', methods: ['POST'])]
    public function registerOptions(Request $request): Response
    {
        $this->logCeremony($request, 'register_options.start');

        if (! $this->isValidCsrfToken($request, self::REGISTER_CSRF_TOKEN_ID)) {
            $this->logCeremony($request, 'register_options.csrf_invalid');

            return new JsonResponse([
                'status' => 'error',
                'errorMessage' => 'Operation impossible.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $userEntity = $this->getCurrentUserEntity();
        $credentials = $this->credentialRepository->findAllForCurrentUser($userEntity->id);
        $this->logCeremony($request, 'register_options.user_resolved', [
            'credential_count' => count($credentials),
            'user_handle_hash' => $this->hashForLog($userEntity->id),
        ]);

        $label = $this->normalizeLabel($this->extractLabel($request), count($credentials) + 1);
        $excludeCredentials = array_map(
            static fn (PasskeyCredential $credential): PublicKeyCredentialDescriptor => $credential->toCredentialRecord()->getPublicKeyCredentialDescriptor(),
            $credentials,
        );
        $creationOptions = $this->creationOptionsFactory->create('default', $userEntity, $excludeCredentials);
        $this->logCeremony($request, 'register_options.created', [
            'challenge_hash' => $this->hashForLog($creationOptions->challenge),
            'exclude_credentials_count' => count($excludeCredentials),
            'rp_id' => $creationOptions->rp->id,
            'rp_name' => $creationOptions->rp->name,
            'user_verification' => $creationOptions->authenticatorSelection?->userVerification,
            'resident_key' => $creationOptions->authenticatorSelection?->residentKey,
            'attestation' => $creationOptions->attestation,
        ]);

        $this->optionsStorage->store(Item::create($creationOptions, $userEntity));
        $this->storePendingLabel($request, $creationOptions->challenge, $label);
        $this->logCeremony($request, 'register_options.stored', [
            'challenge_hash' => $this->hashForLog($creationOptions->challenge),
            'label_length' => strlen($label),
        ]);

        return $this->creationOptionsHandler->onCreationOptions($creationOptions, $userEntity, $request);
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        $this->logCeremony($request, 'register_result.start', [
            'payload_size' => strlen($request->getContent()),
        ]);

        if (! $this->isValidCsrfToken($request, self::REGISTER_CSRF_TOKEN_ID)) {
            $this->logCeremony($request, 'register_result.csrf_invalid');

            return new JsonResponse([
                'status' => 'error',
                'errorMessage' => 'Operation impossible.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $stage = 'deserialize';
        try {
            $publicKeyCredential = $this->serializer->deserialize(
                $request->getContent(),
                PublicKeyCredential::class,
                JsonEncoder::FORMAT,
            );
            $response = $publicKeyCredential->response;
            if (! $response instanceof AuthenticatorAttestationResponse) {
                throw InvalidDataException::create($response, 'Invalid response');
            }
            $this->logCeremony($request, 'register_result.deserialized', [
                'credential_id_hash' => $this->hashForLog($publicKeyCredential->rawId),
                'response_class' => $response::class,
                'client_origin' => $response->clientDataJSON->origin,
                'client_type' => $response->clientDataJSON->type,
                'challenge_hash' => $this->hashForLog($response->clientDataJSON->challenge),
            ]);

            $stage = 'options_storage_get';
            $storedData = $this->optionsStorage->get($response->clientDataJSON->challenge);
            $creationOptions = $storedData->getPublicKeyCredentialOptions();
            $userEntity = $storedData->getPublicKeyCredentialUserEntity();
            if (! $userEntity instanceof PublicKeyCredentialUserEntity) {
                throw InvalidDataException::create($userEntity, 'Unable to find the user entity');
            }
            $this->logCeremony($request, 'register_result.options_loaded', [
                'challenge_hash' => $this->hashForLog($response->clientDataJSON->challenge),
                'rp_id' => $creationOptions->rp->id,
                'user_handle_hash' => $this->hashForLog($userEntity->id),
            ]);

            $stage = 'attestation_validation';
            $credentialRecord = $this->attestationResponseValidator->check(
                $response,
                $creationOptions,
                $request->getHost(),
            );
            $this->logCeremony($request, 'register_result.attestation_validated', [
                'credential_id_hash' => $this->hashForLog($credentialRecord->publicKeyCredentialId),
                'user_handle_hash' => $this->hashForLog($credentialRecord->userHandle),
                'counter' => $credentialRecord->counter,
                'transports_count' => count($credentialRecord->transports),
                'backup_eligible' => $credentialRecord->backupEligible,
                'backup_status' => $credentialRecord->backupStatus,
            ]);

            if ($this->credentialRepository->findOneByCredentialId($credentialRecord->publicKeyCredentialId) !== null) {
                $this->logCeremony($request, 'register_result.duplicate_credential', [
                    'credential_id_hash' => $this->hashForLog($credentialRecord->publicKeyCredentialId),
                ]);
                throw InvalidDataException::create($credentialRecord, 'The credential already exists');
            }

            $stage = 'save_credential';
            $label = $this->resolvePendingLabel($request, $response->clientDataJSON->challenge);
            $this->credentialRepository->saveCredentialRecordWithLabel($credentialRecord, $label);
            $request->getSession()->getFlashBag()->add('success', sprintf('%s enregistrée.', $label));
            $this->logCeremony($request, 'register_result.saved', [
                'credential_id_hash' => $this->hashForLog($credentialRecord->publicKeyCredentialId),
                'label_length' => strlen($label),
            ]);

            return new JsonResponse([
                'status' => 'ok',
                'errorMessage' => '',
            ]);
        } catch (\Throwable $throwable) {
            $this->logCeremony($request, 'register_result.failed', [
                'stage' => $stage,
                'exception' => $throwable,
            ]);

            return new JsonResponse([
                'status' => 'error',
                'errorMessage' => 'Operation impossible.',
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/login/options', name: 'login_options', methods: ['POST'])]
    public function loginOptions(Request $request): Response
    {
        $this->logCeremony($request, 'login_options.start');
        $limit = $this->loginOptionsLimiter
            ->create($request->getClientIp() ?? 'private-webauthn-anonymous')
            ->consume();
        if (! $limit->isAccepted()) {
            $this->logCeremony($request, 'login_options.rate_limited', [
                'retry_after_seconds' => $limit->getRetryAfter()?->getTimestamp(),
            ]);

            return new JsonResponse([
                'status' => 'error',
                'errorMessage' => 'Trop de tentatives. Reessayez dans un instant.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $options = $this->requestOptionsFactory->create('default', []);
        $this->logCeremony($request, 'login_options.created', [
            'challenge_hash' => $this->hashForLog($options->challenge),
            'rp_id' => $options->rpId,
            'allow_credentials_count' => count($options->allowCredentials),
            'user_verification' => $options->userVerification,
        ]);

        $this->optionsStorage->store(Item::create($options, null));
        $this->logCeremony($request, 'login_options.stored', [
            'challenge_hash' => $this->hashForLog($options->challenge),
        ]);

        return $this->requestOptionsHandler->onRequestOptions($options, null, $request);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): Response
    {
        $this->logCeremony($request, 'login_result.start', [
            'payload_size' => strlen($request->getContent()),
        ]);

        $stage = 'deserialize';
        try {
            $publicKeyCredential = $this->serializer->deserialize(
                $request->getContent(),
                PublicKeyCredential::class,
                JsonEncoder::FORMAT,
            );
            $response = $publicKeyCredential->response;
            if (! $response instanceof AuthenticatorAssertionResponse) {
                throw InvalidDataException::create($response, 'Invalid response');
            }
            $this->logCeremony($request, 'login_result.deserialized', [
                'credential_id_hash' => $this->hashForLog($publicKeyCredential->rawId),
                'response_class' => $response::class,
                'client_origin' => $response->clientDataJSON->origin,
                'client_type' => $response->clientDataJSON->type,
                'challenge_hash' => $this->hashForLog($response->clientDataJSON->challenge),
                'user_handle_hash' => $this->hashForLog($response->userHandle),
            ]);

            $stage = 'options_storage_get';
            $storedData = $this->optionsStorage->get($response->clientDataJSON->challenge);
            $requestOptions = $storedData->getPublicKeyCredentialOptions();
            $userEntity = $storedData->getPublicKeyCredentialUserEntity();
            $this->logCeremony($request, 'login_result.options_loaded', [
                'challenge_hash' => $this->hashForLog($response->clientDataJSON->challenge),
                'rp_id' => $requestOptions->rpId,
                'stored_user_handle_hash' => $userEntity instanceof PublicKeyCredentialUserEntity ? $this->hashForLog($userEntity->id) : null,
            ]);

            $stage = 'credential_lookup';
            $credentialRecord = $this->credentialRepository->findOneByCredentialId($publicKeyCredential->rawId);
            $this->logCeremony($request, 'login_result.credential_lookup', [
                'credential_id_hash' => $this->hashForLog($publicKeyCredential->rawId),
                'found' => $credentialRecord !== null,
            ]);
            if ($credentialRecord === null) {
                throw InvalidDataException::create($publicKeyCredential->rawId, 'Unknown credential');
            }

            $stage = 'assertion_validation';
            $validatedCredential = $this->assertionResponseValidator->check(
                $credentialRecord,
                $response,
                $requestOptions,
                $request->getHost(),
                $userEntity?->id,
            );
            $this->logCeremony($request, 'login_result.assertion_validated', [
                'credential_id_hash' => $this->hashForLog($validatedCredential->publicKeyCredentialId),
                'user_handle_hash' => $this->hashForLog($validatedCredential->userHandle),
                'counter' => $validatedCredential->counter,
                'backup_eligible' => $validatedCredential->backupEligible,
                'backup_status' => $validatedCredential->backupStatus,
            ]);

            $stage = 'save_credential';
            $this->credentialRepository->saveCredentialRecord($validatedCredential);
            $this->logCeremony($request, 'login_result.credential_saved', [
                'credential_id_hash' => $this->hashForLog($validatedCredential->publicKeyCredentialId),
            ]);

            $stage = 'security_login';
            $response = $this->security->login(
                $this->loginUserFactory->create(),
                'form_login',
                'main',
            );
            $this->logCeremony($request, 'login_result.security_login_completed', [
                'response_class' => $response !== null ? $response::class : null,
            ]);

            return $response ?? $this->redirectToRoute('app_private_dashboard');
        } catch (\Throwable $throwable) {
            $this->logCeremony($request, 'login_result.failed', [
                'stage' => $stage,
                'exception' => $throwable,
            ]);

            return new JsonResponse([
                'status' => 'error',
                'errorMessage' => 'Operation impossible.',
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, int $id): Response
    {
        $token = new CsrfToken(self::DELETE_CSRF_TOKEN_PREFIX . $id, (string) $request->request->get('_csrf_token', ''));
        if (! $this->csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'La suppression de la passkey a echoue.');

            return $this->redirectToRoute('app_private_security_passkeys_index');
        }

        $credential = $this->credentialRepository->findOneById($id);
        if ($credential === null) {
            $this->addFlash('error', 'La passkey demandee est introuvable.');

            return $this->redirectToRoute('app_private_security_passkeys_index');
        }

        $currentUserHandle = $this->getCurrentUserEntity()->id;
        if ($this->credentialRepository->countForUserHandle($currentUserHandle) <= 1) {
            $this->addFlash('error', 'Au moins une passkey doit rester disponible.');

            return $this->redirectToRoute('app_private_security_passkeys_index');
        }

        $this->credentialRepository->delete($credential);
        $this->addFlash('success', sprintf('%s supprimée.', $credential->getDisplayName()));

        return $this->redirectToRoute('app_private_security_passkeys_index');
    }

    private function getCurrentUserEntity(): PublicKeyCredentialUserEntity
    {
        $user = $this->getUser();
        if ($user === null) {
            throw new AccessDeniedException();
        }

        $userEntity = $this->userRepository->findOneByUsername($user->getUserIdentifier());
        if (! $userEntity instanceof PublicKeyCredentialUserEntity) {
            throw new AccessDeniedException();
        }

        return $userEntity;
    }

    private function extractLabel(Request $request): ?string
    {
        $payload = $this->decodeJsonPayload($request);
        if (! is_array($payload)) {
            return null;
        }

        $label = $payload['label'] ?? null;
        if (! is_string($label)) {
            return null;
        }

        $label = trim($label);

        return $label !== '' ? $label : null;
    }

    private function normalizeLabel(?string $label, int $fallbackIndex): string
    {
        if ($label !== null) {
            return $label;
        }

        return sprintf('Passkey %d', $fallbackIndex);
    }

    private function storePendingLabel(Request $request, string $challenge, string $label): void
    {
        $session = $request->getSession();
        $pendingLabels = $session->get(self::PENDING_LABELS_SESSION_KEY, []);
        $pendingLabels[Base64UrlSafe::encodeUnpadded($challenge)] = $label;
        $session->set(self::PENDING_LABELS_SESSION_KEY, $pendingLabels);
    }

    private function resolvePendingLabel(Request $request, string $challenge): string
    {
        $session = $request->getSession();
        $pendingLabels = $session->get(self::PENDING_LABELS_SESSION_KEY, []);
        $challengeKey = Base64UrlSafe::encodeUnpadded($challenge);
        $label = $pendingLabels[$challengeKey] ?? null;
        unset($pendingLabels[$challengeKey]);
        $session->set(self::PENDING_LABELS_SESSION_KEY, $pendingLabels);

        return $this->normalizeLabel(is_string($label) ? $label : null, count($pendingLabels) + 1);
    }

    private function isValidCsrfToken(Request $request, string $tokenId): bool
    {
        $payload = $this->decodeJsonPayload($request);
        if (! is_array($payload)) {
            return false;
        }

        $csrfToken = $payload['csrfToken'] ?? null;
        if (! is_string($csrfToken) || $csrfToken === '') {
            return false;
        }

        return $this->csrfTokenManager->isTokenValid(new CsrfToken($tokenId, $csrfToken));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJsonPayload(Request $request): ?array
    {
        if ($request->getContentTypeFormat() !== 'json') {
            return null;
        }

        try {
            $decoded = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function logCeremony(Request $request, string $event, array $context = []): void
    {
        $this->ceremonyLogger->log($request, $event, $context);
    }

    private function hashForLog(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return substr(hash('sha256', $value), 0, 12);
    }

}

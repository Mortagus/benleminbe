<?php

declare(strict_types=1);

namespace App\Tests\Functional\Private;

use App\Private\Security\Repository\PasskeyCredentialRepository;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\EmptyTrustPath;

final class PrivateSecurityWebTest extends PrivateSecurityWebTestCase
{
    public function testGuestsAreRedirectedFromPasskeysPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/private/security/passkeys');

        self::assertResponseRedirects('/private/login');
    }

    public function testLoginPageShowsPasskeyActionAndCsrfToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/private/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="_csrf_token"]');
        self::assertSelectorTextContains('body', 'Se connecter avec une passkey');
        self::assertSelectorTextContains('body', 'Connexion par passkey');
    }

    public function testPasskeyLoginOptionsEndpointIsPublic(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/private/security/passkeys/login/options',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{}',
        );

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        self::assertSame('ok', json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR)['status'] ?? null);
    }

    public function testMalformedPasskeyLoginPayloadIsRejected(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/private/security/passkeys/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{',
        );

        self::assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('error', $data['status'] ?? null);
        self::assertSame('Operation impossible.', $data['errorMessage'] ?? null);
    }

    public function testInvalidPasswordAndInvalidUsernameShareTheSameLoginMessage(): void
    {
        $client = static::createClient();

        $this->submitLoginForm($client, 'private_admin', '__invalid_password__');
        $client->followRedirect();
        $invalidPassword = $client->getResponse();

        $this->submitLoginForm($client, '__invalid_username__', 'private-dev-password');
        $client->followRedirect();
        $invalidUsername = $client->getResponse();

        self::assertStringContainsString('Identifiants invalides.', $invalidPassword->getContent());
        self::assertStringContainsString('Identifiants invalides.', $invalidUsername->getContent());
        self::assertSame(
            $this->extractAlertText($invalidPassword->getContent()),
            $this->extractAlertText($invalidUsername->getContent()),
        );
    }

    public function testLoginThrottleBlocksTemporarilyAndRecovers(): void
    {
        $client = static::createClient();

        for ($index = 0; $index < 5; ++$index) {
            $this->submitLoginForm($client, 'private_admin', '__invalid_password__');
            $client->followRedirect();
        }

        $this->submitLoginForm($client, 'private_admin', '__invalid_password__');
        $client->followRedirect();
        self::assertStringContainsString('Identifiants invalides.', $client->getResponse()->getContent());

        sleep(2);

        $this->submitLoginForm($client, 'private_admin', 'private-dev-password');
        self::assertResponseRedirects('/private');
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'Déconnexion');
    }

    public function testAuthenticatedAdminCanOpenPasskeysPageAndDeleteThemWithCsrfProtection(): void
    {
        $client = $this->createAuthenticatedClient();
        $repository = self::getContainer()->get(PasskeyCredentialRepository::class);

        $firstCredential = $this->createCredentialRecord();
        $secondCredential = $this->createCredentialRecord();
        $repository->saveCredentialRecordWithLabel($firstCredential, 'PC principal - Windows Hello');
        $repository->saveCredentialRecordWithLabel($secondCredential, 'Téléphone');

        $client->request('GET', '/private/security/passkeys');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Passkeys');
        self::assertSelectorTextContains('body', 'PC principal - Windows Hello');
        self::assertSelectorTextContains('body', 'Téléphone');
        $crawler = $client->getCrawler();
        $deleteForm = $crawler->filter('form[action^="/private/security/passkeys/"]')->first();
        $firstDeleteToken = $deleteForm->filter('input[name="_csrf_token"]')->attr('value');
        $firstDeleteAction = $deleteForm->attr('action');

        $client->request('POST', $firstDeleteAction, ['_csrf_token' => $firstDeleteToken]);
        self::assertResponseRedirects('/private/security/passkeys');
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'supprimée');

        $crawler = $client->getCrawler();
        $remainingDeleteForm = $crawler->filter('form[action^="/private/security/passkeys/"]')->first();
        $remainingDeleteToken = $remainingDeleteForm->filter('input[name="_csrf_token"]')->attr('value');
        $remainingDeleteAction = $remainingDeleteForm->attr('action');

        $client->request('POST', $remainingDeleteAction, ['_csrf_token' => $remainingDeleteToken]);
        self::assertResponseRedirects('/private/security/passkeys');
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'Au moins une passkey doit rester disponible.');
    }

    public function testLogoutInvalidatesPrivateSession(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/private/security/passkeys');
        self::assertResponseIsSuccessful();

        $client->request('GET', '/private/logout');
        self::assertResponseRedirects('/private/login');

        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $client->request('GET', '/private/security/passkeys');
        self::assertResponseRedirects('/private/login');
    }

    private function submitLoginForm(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client, string $username, string $password): void
    {
        $crawler = $client->request('GET', '/private/login');
        $form = $crawler->filter('form.private-form')->selectButton('Se connecter')->form([
            '_username' => $username,
            '_password' => $password,
        ]);

        $client->submit($form);
    }

    private function extractAlertText(string $content): string
    {
        if (preg_match('/<p class="private-alert" role="alert">(.*?)<\/p>/s', $content, $matches) !== 1) {
            return '';
        }

        return trim(strip_tags($matches[1]));
    }

    private function createCredentialRecord(): CredentialRecord
    {
        return CredentialRecord::create(
            random_bytes(32),
            'public-key',
            ['internal'],
            'none',
            EmptyTrustPath::create(),
            Uuid::v4(),
            random_bytes(32),
            'benlemin-private-admin',
            0,
            null,
            null,
            null,
            null,
        );
    }

}

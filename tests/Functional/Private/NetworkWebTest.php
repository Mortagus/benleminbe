<?php

declare(strict_types=1);

namespace App\Tests\Functional\Private;

use Symfony\Component\DomCrawler\Crawler;

final class NetworkWebTest extends NetworkWebTestCase
{
    public function testPrivateAreaRedirectsGuestsAndShowsLoginPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/private/network');

        self::assertResponseRedirects('/private/login');

        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Connexion');
        self::assertSelectorExists('input[name="_csrf_token"]');
    }

    public function testDashboardAndListsRenderForAuthenticatedAdmin(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/private/network');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Contacts et réseau');
        self::assertStringContainsString('Plateformes à suivre', $client->getResponse()->getContent());
        self::assertStringContainsString('Contacts prioritaires', $client->getResponse()->getContent());

        $client->request('GET', '/private/network/platforms');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Plateformes');

        $client->request('GET', '/private/network/contacts');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Contacts');

        $client->request('GET', '/private/network/import');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Import');
    }

    public function testPlatformCrudFlowWorks(): void
    {
        $client = $this->createAuthenticatedClient();

        $crawler = $client->request('GET', '/private/network/platforms/new');
        self::assertResponseIsSuccessful();

        $client->submit($this->fillPlatformForm($crawler, [
            'name' => 'Symfony Connect',
            'category' => 'freelance',
            'profile_url' => 'https://example.com/symfony-connect',
            'status' => 'a_jour',
            'note' => 'Initial note.',
            'last_reviewed_at' => '2026-05-27',
            'active' => 1,
        ]));

        self::assertResponseRedirects('/private/network/platforms/symfony-connect');
        $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Symfony Connect');
        self::assertSelectorTextContains('.private-copy', 'Initial note.');

        $crawler = $client->request('GET', '/private/network/platforms/symfony-connect/edit');
        self::assertResponseIsSuccessful();

        $client->submit($this->fillPlatformForm($crawler, [
            'name' => 'Symfony Connect',
            'category' => 'freelance',
            'profile_url' => 'https://example.com/symfony-connect',
            'status' => 'a_verifier',
            'note' => 'Updated note.',
            'last_reviewed_at' => '2026-05-28',
            'active' => 1,
        ], 'Enregistrer'));

        self::assertResponseRedirects('/private/network/platforms/symfony-connect');
        $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Symfony Connect');
        self::assertSelectorTextContains('.private-copy', 'Updated note.');
    }

    public function testContactCrudAndInteractionFlowWorks(): void
    {
        $client = $this->createAuthenticatedClient();

        $crawler = $client->request('GET', '/private/network/contacts/new');
        self::assertResponseIsSuccessful();

        $client->submit($this->fillContactForm($crawler, [
            'display_name' => 'Smoke Test Contact',
            'first_name' => 'Smoke',
            'last_name' => 'Test',
            'organization' => 'Codex QA',
            'role' => 'Validation',
            'main_channel' => 'email',
            'email' => 'smoke-test-contact@example.com',
            'phone' => '+32000000000',
            'profile_url' => 'https://example.com/smoke-test-contact',
            'source' => 'smoke test',
            'priority' => 'haute',
            'relationship_status' => 'a_relancer',
            'last_contact_at' => '2026-05-27',
            'next_action_at' => '2026-05-30',
            'next_action' => 'Follow up smoke test',
            'notes' => 'Created by smoke test.',
            'tags' => 'qa,smoke',
        ]));

        self::assertResponseRedirects();
        $location = $client->getResponse()->headers->get('Location');
        self::assertNotNull($location);
        self::assertMatchesRegularExpression('~^/private/network/contacts/([^/]+)$~', $location);

        $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Smoke Test Contact');
        self::assertSelectorTextContains('.private-section-card', 'Codex QA');
        self::assertSelectorTextContains('.private-copy', 'Created by smoke test.');

        $contactId = $this->extractContactId($location);

        $crawler = $client->request('GET', sprintf('/private/network/contacts/%s', $contactId));
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Smoke Test Contact');

        $client->submit($this->fillInteractionForm($crawler, [
            'date' => '2026-05-27',
            'channel' => 'email',
            'summary' => 'Smoke interaction',
            'result' => 'Created and verified',
            'next_action' => 'Follow up later',
            'next_action_at' => '2026-05-31',
        ]));

        self::assertResponseRedirects(sprintf('/private/network/contacts/%s', $contactId));
        $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Smoke Test Contact');
        self::assertSelectorTextContains('.private-list-stack', 'Smoke interaction');
        self::assertSelectorTextContains('.private-list-stack', 'Created and verified');
    }

    /**
     * @param array<string, mixed> $values
     */
    private function fillPlatformForm(Crawler $crawler, array $values, string $button = 'Créer')
    {
        return $crawler->selectButton($button)->form($values);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function fillContactForm(Crawler $crawler, array $values)
    {
        return $crawler->selectButton('Créer')->form($values);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function fillInteractionForm(Crawler $crawler, array $values)
    {
        return $crawler->selectButton("Enregistrer l'interaction")->form($values);
    }

    private function extractContactId(string $location): string
    {
        if (!preg_match('~^/private/network/contacts/([^/]+)$~', $location, $matches)) {
            self::fail(sprintf('Unable to extract contact id from "%s".', $location));
        }

        return $matches[1];
    }
}

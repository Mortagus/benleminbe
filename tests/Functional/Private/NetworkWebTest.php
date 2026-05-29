<?php

declare(strict_types=1);

namespace App\Tests\Functional\Private;

use App\Entity\Network\Contact;
use App\Entity\Network\ImportLog;
use App\Enum\Network\ContactImportSource;
use App\Enum\Network\ContactPriority;
use App\Enum\Network\ContactRelationshipStatus;
use App\Private\Service\Network\NetworkRepository;
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
        self::assertSelectorTextContains('.private-definition-list', 'Codex QA');
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

    public function testImportFlowCreatesContactsAndLogsImport(): void
    {
        $this->assertImportFlow(
            ContactImportSource::LinkedInConnectionsCsv,
            <<<CSV
First Name,Last Name,URL,Email Address,Company,Position,Connected On
Tom,Test,https://www.linkedin.com/in/tom-test,tom.test@example.com,Test Lab,Developer,29 May 2026
CSV,
            [
                'display_name' => 'Tom Test',
                'organization' => 'Test Lab',
                'role' => 'Developer',
                'profile_url' => 'https://www.linkedin.com/in/tom-test',
                'email' => 'tom.test@example.com',
                'last_contact_at' => '2026-05-29',
            ],
        );
    }

    public function testImportFlowSupportsVCardSource(): void
    {
        $this->assertImportFlow(
            ContactImportSource::PhoneVCard,
            <<<VCF
BEGIN:VCARD
VERSION:2.1
FN:Jean Test
N:Test;Jean;;;
ORG:Phone Lab
TITLE:Developer
TEL:+32000000002
EMAIL:jean.test@example.com
URL:https://example.com/jean-test
END:VCARD
VCF,
            [
                'display_name' => 'Jean Test',
                'organization' => 'Phone Lab',
                'role' => 'Developer',
                'profile_url' => 'https://example.com/jean-test',
                'phone' => '+32000000002',
                'email' => 'jean.test@example.com',
            ],
        );
    }

    public function testContactsListIsPaginatedAndRowsExposeMobileLinks(): void
    {
        $client = $this->createAuthenticatedClient();
        $repository = self::getContainer()->get(NetworkRepository::class);

        for ($index = 1; $index <= 21; ++$index) {
            $repository->saveContact([
                'display_name' => sprintf('Contact %02d', $index),
                'organization' => 'Pagination Lab',
                'role' => 'Tester',
                'priority' => 'moyenne',
                'relationship_status' => 'a_relancer',
                'email' => sprintf('contact-%02d@example.com', $index),
            ]);
        }

        $client->request('GET', '/private/network/contacts');

        self::assertResponseIsSuccessful();
        self::assertSame(20, $client->getCrawler()->filter('tbody tr')->count());
        self::assertSelectorExists('tbody tr[data-contact-row]');
        self::assertSelectorExists('.private-table__contact-link');
        self::assertStringContainsString('Affichage 1-20 sur 21 contacts.', $client->getResponse()->getContent());
        self::assertSelectorExists('a[href*="page=2"]');

        $client->clickLink('Charger plus');
        self::assertResponseIsSuccessful();
        self::assertSame(1, $client->getCrawler()->filter('tbody tr')->count());
        self::assertStringContainsString('Affichage 21-21 sur 21 contacts.', $client->getResponse()->getContent());
    }

    public function testContactDeleteActionRemovesContactFromTheList(): void
    {
        $client = $this->createAuthenticatedClient();
        $repository = self::getContainer()->get(NetworkRepository::class);
        $contact = $repository->saveContact([
            'display_name' => 'Contact à supprimer',
            'organization' => 'Deletion Lab',
            'role' => 'Tester',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
            'email' => 'delete-me@example.com',
        ]);

        $crawler = $client->request('GET', '/private/network/contacts');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(sprintf('form[action="/private/network/contacts/%s/delete"]', $contact['id']));

        $deleteForm = $crawler->filter(sprintf('form[action="/private/network/contacts/%s/delete"]', $contact['id']))->form();
        $client->submit($deleteForm);

        self::assertResponseRedirects('/private/network/contacts?page=1');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Aucun contact enregistré.', $client->getResponse()->getContent());
        self::assertSame('0', (string) self::getContainer()->get('doctrine')->getConnection()->fetchOne('SELECT COUNT(*) FROM network_contacts'));
    }

    public function testAutoMergeContactsActionMergesDuplicatesAndKeepsInteractions(): void
    {
        $client = $this->createAuthenticatedClient();
        $repository = self::getContainer()->get(NetworkRepository::class);
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $primary = new Contact('contact-merge-primary', 'Jean Dupont');
        $primary->setOrganization('Lab One');
        $primary->setNotes('Premier doublon');
        $primary->setPhone('+32470000001');
        $primary->setSource('phone');
        $primary->setPriority(ContactPriority::Medium);
        $primary->setRelationshipStatus(ContactRelationshipStatus::FollowUp);

        $duplicate = new Contact('contact-merge-duplicate', 'J. Dupont');
        $duplicate->setRole('Lead');
        $duplicate->setEmail('jean.dupont@example.com');
        $duplicate->setNotes('Second doublon');
        $duplicate->setPhone('+32470000001');
        $duplicate->setSource('linkedin');
        $duplicate->setPriority(ContactPriority::Medium);
        $duplicate->setRelationshipStatus(ContactRelationshipStatus::FollowUp);

        $entityManager->persist($primary);
        $entityManager->persist($duplicate);
        $entityManager->flush();

        $repository->addInteraction($duplicate->getId(), [
            'date' => '2026-05-28',
            'channel' => 'email',
            'summary' => 'Interaction à conserver',
            'result' => 'À vérifier',
        ]);

        $crawler = $client->request('GET', '/private/network/contacts');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[action="/private/network/contacts/merge-duplicates"]');

        $client->submit($crawler->filter('form[action="/private/network/contacts/merge-duplicates"]')->form());

        self::assertResponseRedirects('/private/network/contacts?page=1');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Contacts', $client->getResponse()->getContent());

        $connection = self::getContainer()->get('doctrine')->getConnection();
        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contacts'));
        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_interactions'));

        $contact = self::getContainer()->get('doctrine')->getRepository(Contact::class)->findOneBy([]);
        self::assertInstanceOf(Contact::class, $contact);
        self::assertSame('+32470000001', $contact->getPhone());
        self::assertSame('Lab One', $contact->getOrganization());
        self::assertSame('Lead', $contact->getRole());
        self::assertSame('jean.dupont@example.com', $contact->getEmail());
        self::assertStringContainsString('phone', $contact->getSource() ?? '');
        self::assertStringContainsString('linkedin', $contact->getSource() ?? '');
        self::assertStringContainsString('Premier doublon', $contact->getNotes() ?? '');
        self::assertStringContainsString('Second doublon', $contact->getNotes() ?? '');
        self::assertStringContainsString('Fusion automatique du doublon', $contact->getNotes() ?? '');
        self::assertSame($contact->getId(), (string) $connection->fetchOne('SELECT contact_id FROM network_interactions LIMIT 1'));
        self::assertSame('Interaction à conserver', (string) $connection->fetchOne('SELECT summary FROM network_interactions LIMIT 1'));
        self::assertSame('À vérifier', (string) $connection->fetchOne('SELECT result FROM network_interactions LIMIT 1'));
    }

    public function testManualMergeReviewFlowGeneratesCandidatesAndResolvesOnePair(): void
    {
        $client = $this->createAuthenticatedClient();
        $repository = self::getContainer()->get(NetworkRepository::class);

        $left = $repository->saveContact([
            'display_name' => 'Jean Dupond',
            'first_name' => 'Jean',
            'last_name' => 'Dupond',
            'organization' => 'Lab One',
            'role' => 'Lead',
            'email' => 'jean.dupond@example.com',
            'source' => 'phone',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
            'notes' => 'Notes gauche',
            'tags' => 'alpha,beta',
        ]);

        $right = $repository->saveContact([
            'display_name' => 'Jean Dupont',
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'organization' => 'Lab One',
            'role' => 'Lead',
            'phone' => '+32470000099',
            'source' => 'linkedin',
            'priority' => 'haute',
            'relationship_status' => 'a_relancer',
            'notes' => 'Notes droite',
            'tags' => 'beta,gamma',
        ]);

        $repository->addInteraction($right['id'], [
            'date' => '2026-05-28',
            'channel' => 'email',
            'summary' => 'Interaction à déplacer',
            'result' => 'OK',
        ]);

        $crawler = $client->request('GET', '/private/network/contact-merge-reviews');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Revue des doublons');
        self::assertSelectorExists('form[action="/private/network/contact-merge-reviews/generate"]');

        $client->submit($crawler->filter('form[action="/private/network/contact-merge-reviews/generate"]')->form());

        self::assertResponseRedirects('/private/network/contact-merge-reviews');
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'Jean Dupond');
        self::assertSelectorTextContains('body', 'Jean Dupont');
        self::assertSelectorExists('a[href*="/private/network/contact-merge-reviews/"]');

        $client->clickLink('Comparer');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Revue d’un doublon');
        self::assertSelectorExists('select[name="canonical_side"]');
        self::assertSelectorExists('select[name="field_choices[display_name]"]');
        self::assertSelectorExists('select[name="field_choices[notes]"]');

        $client->submit($client->getCrawler()->selectButton('Fusionner')->form([
            'canonical_side' => 'left',
        ]));

        self::assertResponseRedirects('/private/network/contact-merge-reviews');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $connection = self::getContainer()->get('doctrine')->getConnection();
        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contacts'));
        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_interactions'));
        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contact_merge_reviews'));
        self::assertSame('resolved', (string) $connection->fetchOne('SELECT status FROM network_contact_merge_reviews LIMIT 1'));

        $leftContact = self::getContainer()->get('doctrine')->getRepository(Contact::class)->find($left['id']);
        $rightContact = self::getContainer()->get('doctrine')->getRepository(Contact::class)->find($right['id']);

        self::assertTrue(($leftContact instanceof Contact) xor ($rightContact instanceof Contact));

        $contact = $leftContact instanceof Contact ? $leftContact : $rightContact;
        self::assertInstanceOf(Contact::class, $contact);
        self::assertTrue(in_array($contact->getDisplayName(), ['Jean Dupond', 'Jean Dupont'], true));
        self::assertSame('jean.dupond@example.com', $contact->getEmail());
        self::assertSame('+32470000099', $contact->getPhone());
        self::assertStringContainsString('phone', $contact->getSource() ?? '');
        self::assertStringContainsString('linkedin', $contact->getSource() ?? '');
        self::assertNotNull($contact->getNotes());
        self::assertStringContainsString('Notes gauche', $contact->getNotes() ?? '');
        self::assertStringContainsString('Notes droite', $contact->getNotes() ?? '');
        self::assertContains('alpha', $contact->getTags());
        self::assertContains('beta', $contact->getTags());
        self::assertContains('gamma', $contact->getTags());
        self::assertSame($contact->getId(), (string) $connection->fetchOne('SELECT contact_id FROM network_interactions LIMIT 1'));
        self::assertSame('Interaction à déplacer', (string) $connection->fetchOne('SELECT summary FROM network_interactions LIMIT 1'));
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

    /**
     * @param array<string, mixed> $values
     */
    private function fillImportForm(Crawler $crawler, array $values)
    {
        return $crawler->selectButton('Importer')->form($values);
    }

    /**
     * @param array<string, string> $expected
     */
    private function assertImportFlow(ContactImportSource $source, string $content, array $expected): void
    {
        $client = $this->createAuthenticatedClient();
        $connection = self::getContainer()->get('doctrine')->getConnection();

        self::assertSame('0', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contacts'));
        self::assertSame('0', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_import_logs'));

        $crawler = $client->request('GET', '/private/network/import');
        self::assertResponseIsSuccessful();

        $client->submit($this->fillImportForm($crawler, [
            'source_label' => $source->value,
            'content' => $content,
        ]));

        self::assertResponseRedirects('/private/network/contacts');
        $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Contacts');
        self::assertSelectorTextContains('.private-list-stack, .private-table, body', $expected['display_name']);

        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contacts'));
        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_import_logs'));
        self::assertSame($source->label(), (string) $connection->fetchOne('SELECT source_label FROM network_import_logs ORDER BY imported_at DESC LIMIT 1'));

        $contact = self::getContainer()->get('doctrine')->getRepository(Contact::class)->findOneBy([
            'email' => $expected['email'] ?? null,
        ]);
        self::assertInstanceOf(Contact::class, $contact);
        self::assertSame($expected['display_name'], $contact->getDisplayName());
        self::assertSame($expected['organization'], $contact->getOrganization());
        self::assertSame($expected['role'], $contact->getRole());
        self::assertSame($expected['profile_url'], $contact->getProfileUrl());

        if (isset($expected['phone'])) {
            self::assertSame($expected['phone'], $contact->getPhone());
        }

        if (isset($expected['last_contact_at'])) {
            self::assertSame($expected['last_contact_at'], $contact->getLastContactAt()?->format('Y-m-d'));
        }

        $importLog = self::getContainer()->get('doctrine')->getRepository(ImportLog::class)->findOneBy([
            'sourceLabel' => $source->label(),
        ]);
        self::assertInstanceOf(ImportLog::class, $importLog);
        self::assertSame(1, $importLog->getTotal());
        self::assertSame(1, $importLog->getCreated());
        self::assertSame(0, $importLog->getUpdated());
    }

    private function extractContactId(string $location): string
    {
        if (!preg_match('~^/private/network/contacts/([^/]+)$~', $location, $matches)) {
            self::fail(sprintf('Unable to extract contact id from "%s".', $location));
        }

        return $matches[1];
    }
}

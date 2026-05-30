<?php

declare(strict_types=1);

namespace App\Tests\Functional\Private;

use App\Entity\Network\Contact;
use App\Entity\Network\ContactMergeReview;
use App\Entity\Network\ImportLog;
use App\Entity\Network\Platform;
use App\Enum\Network\ContactImportSource;
use App\Enum\Network\ContactMergeReviewStatus;
use App\Enum\Network\ContactPriority;
use App\Enum\Network\ContactRelationshipStatus;
use App\Private\Service\Network\NetworkRepository;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

    public function testPrivateDashboardShowsContactStatistics(): void
    {
        $client = $this->createAuthenticatedClient();
        $repository = self::getContainer()->get(NetworkRepository::class);

        $repository->saveContact([
            'display_name' => 'Alice Example',
            'organization' => 'Example Lab',
            'email' => 'alice@example.com',
            'phone' => '+32470000001',
            'profile_url' => 'https://www.linkedin.com/in/alice-example',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
        ]);
        $repository->saveContact([
            'display_name' => 'Bob Example',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
        ]);
        $repository->saveContact([
            'display_name' => 'Claire Example',
            'organization' => 'Example Studio',
            'role' => 'Consultante',
            'email' => 'claire@example.com',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
        ]);

        $client->request('GET', '/private');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Tableau de bord');
        self::assertSelectorTextContains('h2', 'Contacts en chiffres');
        self::assertSelectorTextContains('[data-stat-key="total_contacts"]', '3');
        self::assertSelectorTextContains('[data-stat-key="contacts_with_organization"]', '2');
        self::assertSelectorTextContains('[data-stat-key="contacts_with_linkedin"]', '1');
        self::assertSelectorTextContains('[data-stat-key="contacts_with_email"]', '2');
        self::assertSelectorTextContains('[data-stat-key="contacts_with_phone"]', '1');
        self::assertSelectorTextContains('[data-stat-key="contacts_to_qualify"]', '1');
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

    public function testContactsListingPaginatesWithNumberedNavigation(): void
    {
        $client = $this->createAuthenticatedClient();
        $repository = self::getContainer()->get(NetworkRepository::class);

        for ($index = 1; $index <= 22; ++$index) {
            $repository->saveContact([
                'display_name' => sprintf('Contact %02d', $index),
                'organization' => 'Pagination Lab',
                'priority' => 'moyenne',
                'relationship_status' => 'a_relancer',
            ]);
        }

        $client->request('GET', '/private/network/contacts?page=2');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Contacts');
        self::assertSelectorTextContains('.private-muted', 'Affichage 21-22 sur 22 contacts.');
        self::assertSelectorExists('.private-pagination');
        self::assertSelectorTextContains('.private-pagination', 'Précédente');
        self::assertSelectorTextContains('.private-pagination', 'Suivante');
        self::assertSelectorTextContains('.private-pagination', '1');
        self::assertSelectorTextContains('.private-pagination', '2');
        self::assertSelectorTextContains('.private-pagination__link--current', '2');
    }

    public function testContactsListingSupportsAlphabeticFilter(): void
    {
        $client = $this->createAuthenticatedClient();
        $repository = self::getContainer()->get(NetworkRepository::class);

        $repository->saveContact([
            'display_name' => 'Alpha Example',
            'organization' => 'Alphabet Lab',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
        ]);
        $repository->saveContact([
            'display_name' => 'Élodie Example',
            'organization' => 'Alphabet Lab',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
        ]);
        $repository->saveContact([
            'display_name' => 'Ethan Example',
            'organization' => 'Alphabet Lab',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
        ]);

        $client->request('GET', '/private/network/contacts?letter=E');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Contacts');
        self::assertSelectorTextContains('.private-alpha-index__link--active', 'E');
        self::assertSelectorTextContains('.private-muted', 'Affichage 1-2 sur 2 contacts.');
        self::assertSelectorTextContains('body', 'Élodie Example');
        self::assertSelectorTextContains('body', 'Ethan Example');
        self::assertStringNotContainsString('Alpha Example', $client->getResponse()->getContent());
    }

    public function testContactsListingCanFilterContactsWithOrganization(): void
    {
        $client = $this->createAuthenticatedClient();
        $repository = self::getContainer()->get(NetworkRepository::class);

        $repository->saveContact([
            'display_name' => 'Entreprise Example',
            'organization' => 'Entreprise Lab',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
        ]);
        $repository->saveContact([
            'display_name' => 'Sans Entreprise',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
        ]);

        $client->request('GET', '/private/network/contacts?organization_state=with');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('select[name="organization_state"]');
        self::assertSame('with', $client->getCrawler()->filter('select[name="organization_state"] option[selected]')->attr('value'));
        self::assertSelectorTextContains('.private-muted', 'Affichage 1-1 sur 1 contact.');
        self::assertSelectorTextContains('body', 'Entreprise Example');
        self::assertStringNotContainsString('Sans Entreprise', $client->getResponse()->getContent());
    }

    public function testContactsListingCanFilterContactsWithRole(): void
    {
        $client = $this->createAuthenticatedClient();
        $repository = self::getContainer()->get(NetworkRepository::class);

        $repository->saveContact([
            'display_name' => 'Rôle Example',
            'role' => 'Consultante',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
        ]);
        $repository->saveContact([
            'display_name' => 'Sans Rôle',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
        ]);

        $client->request('GET', '/private/network/contacts?role_state=with');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('select[name="role_state"]');
        self::assertSame('with', $client->getCrawler()->filter('select[name="role_state"] option[selected]')->attr('value'));
        self::assertSelectorTextContains('.private-muted', 'Affichage 1-1 sur 1 contact.');
        self::assertSelectorTextContains('body', 'Rôle Example');
        self::assertStringNotContainsString('Sans Rôle', $client->getResponse()->getContent());
    }

    public function testContactsListingCanSortByOrganization(): void
    {
        $client = $this->createAuthenticatedClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $first = new Contact('contact_sort_org_a', 'Alice Alpha');
        $first->setOrganization('  acseo  ');
        $first->setPriority(ContactPriority::Medium);
        $first->setRelationshipStatus(ContactRelationshipStatus::FollowUp);
        $entityManager->persist($first);

        $second = new Contact('contact_sort_org_b', 'Bob Beta');
        $second->setOrganization('ACSEO');
        $second->setPriority(ContactPriority::Medium);
        $second->setRelationshipStatus(ContactRelationshipStatus::FollowUp);
        $entityManager->persist($second);

        $third = new Contact('contact_sort_org_c', 'Charlie Gamma');
        $third->setOrganization('Beta Lab');
        $third->setPriority(ContactPriority::Medium);
        $third->setRelationshipStatus(ContactRelationshipStatus::FollowUp);
        $entityManager->persist($third);

        $entityManager->flush();

        $client->request('GET', '/private/network/contacts?sort=organization');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('select[name="sort"]');
        self::assertSame('organization', $client->getCrawler()->filter('select[name="sort"] option[selected]')->attr('value'));

        $rows = $client->getCrawler()->filter('tbody tr');
        self::assertCount(3, $rows);
        self::assertSame('Alice Alpha', trim($rows->eq(0)->filter('a.private-table__contact-link')->text()));
        self::assertSame('Bob Beta', trim($rows->eq(1)->filter('a.private-table__contact-link')->text()));
        self::assertSame('Charlie Gamma', trim($rows->eq(2)->filter('a.private-table__contact-link')->text()));
    }

    public function testContactsListingCanFilterContactsBySpecificRole(): void
    {
        $client = $this->createAuthenticatedClient();
        $repository = self::getContainer()->get(NetworkRepository::class);

        $repository->saveContact([
            'display_name' => 'Consultante One',
            'role' => 'Consultante',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
        ]);
        $repository->saveContact([
            'display_name' => 'Consultante Two',
            'role' => 'consultante',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
        ]);
        $repository->saveContact([
            'display_name' => 'Developer Example',
            'role' => 'Developer',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
        ]);

        $client->request('GET', '/private/network/contacts?role=consultante');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('select[name="role"]');
        self::assertSame('consultante', $client->getCrawler()->filter('select[name="role"] option[selected]')->attr('value'));
        self::assertSelectorTextContains('.private-muted', 'Affichage 1-2 sur 2 contacts.');
        self::assertSelectorTextContains('body', 'Consultante One');
        self::assertSelectorTextContains('body', 'Consultante Two');
        self::assertStringNotContainsString('Developer Example', $client->getResponse()->getContent());
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

    public function testPlatformBackupExportDownloadsJsonSnapshot(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/private/network/platforms/export');

        self::assertResponseIsSuccessful();
        self::assertSame('application/json; charset=utf-8', $client->getResponse()->headers->get('Content-Type'));
        self::assertStringContainsString('attachment;', (string) $client->getResponse()->headers->get('Content-Disposition'));

        $payload = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(1, $payload['schema_version']);
        self::assertArrayHasKey('exported_at', $payload);
        self::assertCount(7, $payload['platforms']);
        self::assertContains('linkedin', array_column($payload['platforms'], 'slug'));
    }

    public function testPlatformBackupImportRestoresPlatformsFromJson(): void
    {
        $client = $this->createAuthenticatedClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $connection = self::getContainer()->get('doctrine')->getConnection();

        $existingPlatform = new Platform();
        $existingPlatform->setSlug('old-platform');
        $existingPlatform->setName('Old Platform');
        $existingPlatform->setCategory('reseau');
        $entityManager->persist($existingPlatform);
        $entityManager->flush();

        $crawler = $client->request('GET', '/private/network/platforms/import');
        self::assertResponseIsSuccessful();

        $uploadedFile = $this->createJsonUpload(<<<JSON
{
  "schema_version": 1,
  "exported_at": "2026-05-30T12:00:00+02:00",
  "platforms": [
    {
      "slug": "backup-platform",
      "name": "Backup Platform",
      "category": "freelance",
      "profile_url": "https://example.com/backup-platform",
      "status": "a_jour",
      "note": "Imported from a snapshot.",
      "last_reviewed_at": "2026-05-29",
      "active": true
    }
  ]
}
JSON);

        $client->submit($this->fillPlatformBackupImportForm($crawler, [
            'file' => $uploadedFile,
        ]));

        self::assertResponseRedirects('/private/network/platforms');
        $client->followRedirect();
        self::assertSelectorTextContains('h1', 'Plateformes');
        self::assertSelectorTextContains('body', 'Backup Platform');

        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_platforms'));
        self::assertSame('0', (string) $connection->fetchOne("SELECT COUNT(*) FROM network_platforms WHERE slug = 'old-platform'"));
        self::assertSame('1', (string) $connection->fetchOne("SELECT COUNT(*) FROM network_platforms WHERE slug = 'backup-platform'"));
    }

    public function testPlatformBackupImportRejectsInvalidJsonWithoutMutatingData(): void
    {
        $client = $this->createAuthenticatedClient();
        $connection = self::getContainer()->get('doctrine')->getConnection();
        $initialCount = (string) $connection->fetchOne('SELECT COUNT(*) FROM network_platforms');

        $crawler = $client->request('GET', '/private/network/platforms/import');
        self::assertResponseIsSuccessful();

        $client->submit($this->fillPlatformBackupImportForm($crawler, [
            'file' => $this->createJsonUpload('not valid json'),
        ]));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.private-alert-list', 'Le fichier JSON de sauvegarde est invalide.');
        self::assertSame($initialCount, (string) $connection->fetchOne('SELECT COUNT(*) FROM network_platforms'));
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
            'phone' => '+32475258941',
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
        self::assertSelectorTextContains('.private-definition-list', 'Codex Qa');
        self::assertSelectorTextContains('.private-definition-list', '+32 475 25 89 41');
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
Tom,Test,https://www.linkedin.com/in/tom-test,tom.test@example.com,ACSEO,Developer,29 May 2026
CSV,
            [
                'display_name' => 'Tom Test',
                'organization' => 'Acseo',
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

    public function testImportFlowCollapsesDuplicateRowsThatShareTheSamePhone(): void
    {
        $client = $this->createAuthenticatedClient();
        $connection = self::getContainer()->get('doctrine')->getConnection();

        $crawler = $client->request('GET', '/private/network/import');
        self::assertResponseIsSuccessful();

        $client->submit($this->fillImportForm($crawler, [
            'source_label' => ContactImportSource::PhoneVCard->value,
            'content' => <<<VCF
BEGIN:VCARD
VERSION:2.1
FN:Alice Phone
N:Phone;Alice;;;
ORG:Phone Lab
TITLE:Developer
TEL:+32470123456
EMAIL:alice.one@example.com
END:VCARD
BEGIN:VCARD
VERSION:2.1
FN:Alice Phone Updated
N:Phone;Alice;;;
ORG:Phone Lab Updated
TITLE:Lead
TEL:0032470123456
EMAIL:alice.two@example.com
END:VCARD
VCF,
        ]));

        self::assertResponseRedirects('/private/network/contacts');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contacts'));
        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_import_logs'));

        $contact = self::getContainer()->get('doctrine')->getRepository(Contact::class)->findOneBy([
            'displayName' => 'Alice Phone Updated',
        ]);
        self::assertInstanceOf(Contact::class, $contact);
        self::assertSame('Alice Phone Updated', $contact->getDisplayName());
        self::assertSame('Phone Lab Updated', $contact->getOrganization());
        self::assertSame('Lead', $contact->getRole());
        self::assertSame('alice.one@example.com', $contact->getEmail());
        self::assertSame('1', (string) $connection->fetchOne('SELECT created FROM network_import_logs LIMIT 1'));
        self::assertSame('1', (string) $connection->fetchOne('SELECT updated FROM network_import_logs LIMIT 1'));
        self::assertSame('2', (string) $connection->fetchOne('SELECT total FROM network_import_logs LIMIT 1'));
    }

    public function testImportFlowCollapsesDuplicateRowsThatShareTheSameProfileUrl(): void
    {
        $client = $this->createAuthenticatedClient();
        $connection = self::getContainer()->get('doctrine')->getConnection();

        $crawler = $client->request('GET', '/private/network/import');
        self::assertResponseIsSuccessful();

        $client->submit($this->fillImportForm($crawler, [
            'source_label' => ContactImportSource::LinkedInConnectionsCsv->value,
            'content' => <<<CSV
First Name,Last Name,URL,Email Address,Company,Position,Connected On
Tom,Test,https://www.linkedin.com/in/tom-test,tom.one@example.com,Lab One,Developer,29 May 2026
Thomas,Test,https://www.linkedin.com/in/tom-test,tom.two@example.com,Lab One Updated,Lead,30 May 2026
CSV,
        ]));

        self::assertResponseRedirects('/private/network/contacts');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contacts'));
        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_import_logs'));

        $contact = self::getContainer()->get('doctrine')->getRepository(Contact::class)->findOneBy([
            'profileUrl' => 'https://www.linkedin.com/in/tom-test',
        ]);
        self::assertInstanceOf(Contact::class, $contact);
        self::assertSame('Thomas Test', $contact->getDisplayName());
        self::assertSame('Lab One Updated', $contact->getOrganization());
        self::assertSame('Lead', $contact->getRole());
        self::assertSame('tom.one@example.com', $contact->getEmail());
    }

    public function testImportFlowCollapsesDuplicateRowsThatShareTheSameEmail(): void
    {
        $client = $this->createAuthenticatedClient();
        $connection = self::getContainer()->get('doctrine')->getConnection();

        $crawler = $client->request('GET', '/private/network/import');
        self::assertResponseIsSuccessful();

        $client->submit($this->fillImportForm($crawler, [
            'source_label' => ContactImportSource::LinkedInConnectionsCsv->value,
            'content' => <<<CSV
First Name,Last Name,URL,Email Address,Company,Position,Connected On
Jean,Dupond,https://www.linkedin.com/in/jean-dupond,jean.dupond@example.com,Lab One,Lead,29 May 2026
Jean,Dupond,https://www.linkedin.com/in/jean-dupond,jean.dupond@example.com,Lab One Updated,Lead,30 May 2026
CSV,
        ]));

        self::assertResponseRedirects('/private/network/contacts');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contacts'));
        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_import_logs'));

        $contact = self::getContainer()->get('doctrine')->getRepository(Contact::class)->findOneBy([
            'displayName' => 'Jean Dupond',
        ]);
        self::assertInstanceOf(Contact::class, $contact);
        self::assertSame('Jean Dupond', $contact->getDisplayName());
        self::assertSame('Lab One Updated', $contact->getOrganization());
        self::assertSame('Lead', $contact->getRole());
        self::assertSame('https://www.linkedin.com/in/jean-dupond', $contact->getProfileUrl());
        self::assertSame('2026-05-30', $contact->getLastContactAt()?->format('Y-m-d'));
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
        self::assertSelectorExists('.private-pagination');
        self::assertSelectorTextContains('.private-pagination', 'Précédente');
        self::assertSelectorTextContains('.private-pagination', 'Suivante');
        self::assertSelectorTextContains('.private-pagination', '1');
        self::assertSelectorTextContains('.private-pagination', '2');

        $client->clickLink('Suivante');
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

        $duplicate = new Contact('contact-merge-duplicate', 'Jean Dupont');
        $duplicate->setFirstName('Jean');
        $duplicate->setLastName('Dupont');
        $duplicate->setOrganization('Lab One');
        $duplicate->setRole('Lead');
        $duplicate->setEmail('jean.dupont@example.com');
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
        self::assertStringContainsString('Fusion automatique du doublon', $contact->getNotes() ?? '');
        self::assertSame($contact->getId(), (string) $connection->fetchOne('SELECT contact_id FROM network_interactions LIMIT 1'));
        self::assertSame('Interaction à conserver', (string) $connection->fetchOne('SELECT summary FROM network_interactions LIMIT 1'));
        self::assertSame('À vérifier', (string) $connection->fetchOne('SELECT result FROM network_interactions LIMIT 1'));
    }

    public function testAutoMergeContactsActionHandlesExactIdentityWithLinkedInMainChannel(): void
    {
        $client = $this->createAuthenticatedClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $primary = new Contact('contact-merge-name-primary', 'Jean Dupont');
        $primary->setFirstName('Jean');
        $primary->setLastName('Dupont');
        $primary->setOrganization('ACSEO');
        $primary->setRole('Lead');
        $primary->setMainChannel('email');
        $primary->setSource('phone');
        $primary->setNotes('Fiche plus complète');
        $primary->setPriority(ContactPriority::High);
        $primary->setRelationshipStatus(ContactRelationshipStatus::Priority);

        $duplicate = new Contact('contact-merge-name-duplicate', 'Jean Dupont');
        $duplicate->setFirstName('Jean');
        $duplicate->setLastName('Dupont');
        $duplicate->setOrganization('Acseo');
        $duplicate->setRole('Lead');
        $duplicate->setMainChannel('phone');
        $duplicate->setProfileUrl('https://www.linkedin.com/in/j-dupont');
        $duplicate->setSource('linkedin');
        $duplicate->setPriority(ContactPriority::High);
        $duplicate->setRelationshipStatus(ContactRelationshipStatus::Priority);

        $entityManager->persist($primary);
        $entityManager->persist($duplicate);
        $entityManager->flush();

        $crawler = $client->request('GET', '/private/network/contacts');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[action="/private/network/contacts/merge-duplicates"]');

        $client->submit($crawler->filter('form[action="/private/network/contacts/merge-duplicates"]')->form());

        self::assertResponseRedirects('/private/network/contacts?page=1');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $connection = self::getContainer()->get('doctrine')->getConnection();
        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contacts'));
        self::assertStringContainsString('phone', (string) $connection->fetchOne('SELECT source FROM network_contacts LIMIT 1'));
        self::assertStringContainsString('linkedin', (string) $connection->fetchOne('SELECT source FROM network_contacts LIMIT 1'));

        $contact = self::getContainer()->get('doctrine')->getRepository(Contact::class)->findOneBy([]);
        self::assertInstanceOf(Contact::class, $contact);
        self::assertSame('Jean Dupont', $contact->getDisplayName());
        self::assertSame('Acseo', $contact->getOrganization());
        self::assertSame('LinkedIn', $contact->getMainChannel());
        self::assertStringContainsString('Fiche plus complète', $contact->getNotes() ?? '');
    }

    public function testAutoMergeContactsActionMergesContactsSharingTheSameProfileUrl(): void
    {
        $client = $this->createAuthenticatedClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $primary = new Contact('contact-merge-profile-primary', 'Profil principal');
        $primary->setOrganization('Lab Profile');
        $primary->setNotes('Fiche de départ');
        $primary->setProfileUrl('https://www.linkedin.com/in/benlemin-profile');
        $primary->setSource('website');
        $primary->setPriority(ContactPriority::Medium);
        $primary->setRelationshipStatus(ContactRelationshipStatus::FollowUp);

        $duplicate = new Contact('contact-merge-profile-duplicate', 'Profil secondaire');
        $duplicate->setFirstName('Benjamin');
        $duplicate->setLastName('Lemin');
        $duplicate->setOrganization('Lab Profile Updated');
        $duplicate->setRole('Lead');
        $duplicate->setProfileUrl('https://www.linkedin.com/in/benlemin-profile');
        $duplicate->setSource('linkedin');
        $duplicate->setPriority(ContactPriority::High);
        $duplicate->setRelationshipStatus(ContactRelationshipStatus::Priority);

        $entityManager->persist($primary);
        $entityManager->persist($duplicate);
        $entityManager->flush();

        $crawler = $client->request('GET', '/private/network/contacts');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[action="/private/network/contacts/merge-duplicates"]');

        $client->submit($crawler->filter('form[action="/private/network/contacts/merge-duplicates"]')->form());

        self::assertResponseRedirects('/private/network/contacts?page=1');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $connection = self::getContainer()->get('doctrine')->getConnection();
        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contacts'));

        $contact = self::getContainer()->get('doctrine')->getRepository(Contact::class)->findOneBy([]);
        self::assertInstanceOf(Contact::class, $contact);
        self::assertSame('https://www.linkedin.com/in/benlemin-profile', $contact->getProfileUrl());
        self::assertSame('LinkedIn', $contact->getMainChannel());
        self::assertStringContainsString('website', $contact->getSource() ?? '');
        self::assertStringContainsString('linkedin', $contact->getSource() ?? '');
        self::assertStringContainsString('Fiche de départ', $contact->getNotes() ?? '');
        self::assertStringContainsString('Fusion automatique du doublon', $contact->getNotes() ?? '');
    }

    public function testManualReviewGenerationAutoMergesExactIdentityMatches(): void
    {
        $client = $this->createAuthenticatedClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $primary = new Contact('contact-review-auto-primary', 'Jean Dupont');
        $primary->setFirstName('Jean');
        $primary->setLastName('Dupont');
        $primary->setOrganization('Lab One');
        $primary->setRole('Lead');
        $primary->setSource('phone');

        $duplicate = new Contact('contact-review-auto-duplicate', 'Jean Dupont');
        $duplicate->setFirstName('Jean');
        $duplicate->setLastName('Dupont');
        $duplicate->setOrganization('Lab One');
        $duplicate->setRole('Lead');
        $duplicate->setMainChannel('phone');
        $duplicate->setProfileUrl('https://www.linkedin.com/in/jean-dupont');
        $duplicate->setSource('linkedin');

        $entityManager->persist($primary);
        $entityManager->persist($duplicate);
        $entityManager->flush();

        $crawler = $client->request('GET', '/private/network/contact-merge-reviews');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[action="/private/network/contact-merge-reviews/generate"]');

        $client->submit($crawler->filter('form[action="/private/network/contact-merge-reviews/generate"]')->form());

        self::assertResponseRedirects('/private/network/contact-merge-reviews');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'fusionné automatiquement');

        $connection = self::getContainer()->get('doctrine')->getConnection();
        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contacts'));
        self::assertSame('0', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contact_merge_reviews'));
    }

    public function testManualReviewGenerationAutoMergesSparseNearlyIdenticalContacts(): void
    {
        $client = $this->createAuthenticatedClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $primary = new Contact('contact-review-sparse-primary', 'Lina');
        $duplicate = new Contact('contact-review-sparse-duplicate', 'Linaa');

        $entityManager->persist($primary);
        $entityManager->persist($duplicate);
        $entityManager->flush();

        $crawler = $client->request('GET', '/private/network/contact-merge-reviews');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[action="/private/network/contact-merge-reviews/generate"]');

        $client->submit($crawler->filter('form[action="/private/network/contact-merge-reviews/generate"]')->form());

        self::assertResponseRedirects('/private/network/contact-merge-reviews');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'fusionné automatiquement');

        $connection = self::getContainer()->get('doctrine')->getConnection();
        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contacts'));
        self::assertSame('0', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contact_merge_reviews'));
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
            'source' => 'crm',
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
        self::assertStringNotContainsString('Nom affiché identique', $client->getResponse()->getContent());
        self::assertStringNotContainsString('Nom affiché très proche', $client->getResponse()->getContent());
        self::assertStringNotContainsString('Prénom et nom proches', $client->getResponse()->getContent());
        self::assertSelectorTextContains('body', 'Conflit à trancher: canal principal');
        self::assertStringNotContainsString('Points communs:', $client->getResponse()->getContent());
        self::assertStringNotContainsString('Données complémentaires:', $client->getResponse()->getContent());
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
        self::assertStringContainsString('crm', $contact->getSource() ?? '');
        self::assertNotNull($contact->getNotes());
        self::assertStringContainsString('Notes gauche', $contact->getNotes() ?? '');
        self::assertStringContainsString('Notes droite', $contact->getNotes() ?? '');
        self::assertContains('alpha', $contact->getTags());
        self::assertContains('beta', $contact->getTags());
        self::assertContains('gamma', $contact->getTags());
        self::assertSame($contact->getId(), (string) $connection->fetchOne('SELECT contact_id FROM network_interactions LIMIT 1'));
        self::assertSame('Interaction à déplacer', (string) $connection->fetchOne('SELECT summary FROM network_interactions LIMIT 1'));
    }

    public function testPendingMergeReviewsCanBePurgedWithoutTouchingResolvedReviews(): void
    {
        $client = $this->createAuthenticatedClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $resolvedLeft = new Contact('contact-merge-resolved-left', 'Jean Dupont');
        $resolvedLeft->setOrganization('Lab One');
        $resolvedLeft->setSource('phone');

        $resolvedRight = new Contact('contact-merge-resolved-right', 'Jean Dupont');
        $resolvedRight->setOrganization('Lab One');
        $resolvedRight->setSource('linkedin');

        $pendingLeft = new Contact('contact-merge-pending-left', 'Marie Dupont');
        $pendingLeft->setOrganization('Lab Two');
        $pendingLeft->setRole('Manager');

        $pendingRight = new Contact('contact-merge-pending-right', 'Marc Dupont');
        $pendingRight->setOrganization('Lab Two');
        $pendingRight->setRole('Manager');

        $entityManager->persist($resolvedLeft);
        $entityManager->persist($resolvedRight);
        $entityManager->persist($pendingLeft);
        $entityManager->persist($pendingRight);
        $entityManager->flush();

        $resolvedReview = new ContactMergeReview(
            'contact-merge-review-resolved',
            'resolved-fingerprint',
            $resolvedLeft,
            $resolvedRight,
        );
        $resolvedReview
            ->setStatus(ContactMergeReviewStatus::Resolved)
            ->setResolvedContact($resolvedLeft)
            ->setReviewedAt(new \DateTimeImmutable('2026-05-29 10:00:00'))
            ->setResolvedAt(new \DateTimeImmutable('2026-05-29 10:00:00'));

        $pendingReview = new ContactMergeReview(
            'contact-merge-review-pending',
            'pending-fingerprint',
            $pendingLeft,
            $pendingRight,
        );

        $entityManager->persist($resolvedReview);
        $entityManager->persist($pendingReview);
        $entityManager->flush();

        $crawler = $client->request('GET', '/private/network/contact-merge-reviews');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[action="/private/network/contact-merge-reviews/purge-pending"]');
        self::assertSelectorTextContains('body', 'En attente');
        self::assertSelectorTextContains('body', 'Résolus');

        $client->submit($crawler->filter('form[action="/private/network/contact-merge-reviews/purge-pending"]')->form());

        self::assertResponseRedirects('/private/network/contact-merge-reviews');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $connection = self::getContainer()->get('doctrine')->getConnection();
        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contact_merge_reviews'));
        self::assertSame('resolved', (string) $connection->fetchOne('SELECT status FROM network_contact_merge_reviews LIMIT 1'));
    }

    public function testNetworkResetActionClearsContactsImportsInteractionsAndReviewsButKeepsPlatforms(): void
    {
        $client = $this->createAuthenticatedClient();
        $entityManager = self::getContainer()->get('doctrine')->getManager();

        $platform = new Platform();
        $platform->setSlug('reset-check');
        $platform->setName('Reset Check');
        $platform->setCategory('reseau');
        $entityManager->persist($platform);

        $contactLeft = new Contact('contact-reset-left', 'Reset Left');
        $contactLeft->setOrganization('Reset Lab');
        $contactRight = new Contact('contact-reset-right', 'Reset Right');
        $contactRight->setOrganization('Reset Lab');
        $entityManager->persist($contactLeft);
        $entityManager->persist($contactRight);
        $entityManager->flush();

        $interaction = new \App\Entity\Network\Interaction('interaction-reset', $contactLeft, new \DateTimeImmutable('2026-05-29'));
        $interaction->setSummary('Reset interaction');
        $entityManager->persist($interaction);

        $importLog = new ImportLog('import-reset', 'Test import');
        $importLog->setTotal(2);
        $importLog->setCreated(2);
        $entityManager->persist($importLog);

        $review = new ContactMergeReview(
            'contact-merge-review-reset',
            'reset-fingerprint',
            $contactLeft,
            $contactRight,
        );
        $entityManager->persist($review);
        $entityManager->flush();

        $crawler = $client->request('GET', '/private/network/contacts');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[action="/private/network/contact-merge-reviews/reset"] .private-link-button--danger');

        $client->request('GET', '/private/network/contact-merge-reviews');
        self::assertResponseIsSuccessful();
        self::assertSame(0, $client->getCrawler()->filter('form[action="/private/network/contact-merge-reviews/reset"]')->count());

        $client->submit($crawler->filter('form[action="/private/network/contact-merge-reviews/reset"]')->form());

        self::assertResponseRedirects('/private/network/contact-merge-reviews');
        $client->followRedirect();
        self::assertResponseIsSuccessful();

        $connection = self::getContainer()->get('doctrine')->getConnection();
        self::assertSame('0', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contacts'));
        self::assertSame('0', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_interactions'));
        self::assertSame('0', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_import_logs'));
        self::assertSame('0', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_contact_merge_reviews'));
        self::assertSame('1', (string) $connection->fetchOne('SELECT COUNT(*) FROM network_platforms'));
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
    private function fillPlatformBackupImportForm(Crawler $crawler, array $values)
    {
        return $crawler->selectButton('Restaurer la sauvegarde')->form($values);
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
            'displayName' => $expected['display_name'],
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

    private function createJsonUpload(string $contents, string $filename = 'platforms.json'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'platform-backup-');
        self::assertNotFalse($path);
        file_put_contents($path, $contents);

        return new UploadedFile($path, $filename, 'application/json', null, true);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Private\Service\Network;

use App\Entity\Network\Contact;
use App\Enum\Network\ContactPriority;
use App\Enum\Network\ContactRelationshipStatus;
use App\Private\Service\Network\ContactMergeRulesService;
use App\Private\Service\Network\ContactWritePolicyService;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ContactWritePolicyServiceTest extends TestCase
{
    public function testItNormalizesPayloadAndPreservesCreatedAtWhenItMatchesAnExistingContact(): void
    {
        $policy = new ContactWritePolicyService(new ContactMergeRulesService());

        $existing = new Contact('contact-existing', 'Existing Contact');
        $existing->setPhone('0032470123456');
        $existing->setCreatedAt(new DateTimeImmutable('2026-05-01T10:00:00+00:00'));

        $payload = [
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'organization' => 'acme   inc',
            'role' => 'Lead',
            'email' => ['Alice@example.com', 'alice@example.com', 'other@example.com'],
            'phone' => ['00 32 470 12 34 56', '+32470123457', '+32470123457'],
            'tags' => 'alpha, beta; gamma',
            'priority' => 'invalid',
            'relationship_status' => 'invalid',
        ];

        $normalized = $policy->normalizeContactPayload($payload, null, [$existing], 'LinkedIn');

        self::assertSame('Jean Dupont', $normalized['display_name']);
        self::assertSame('Jean', $normalized['first_name']);
        self::assertSame('Dupont', $normalized['last_name']);
        self::assertSame('Acme Inc', $normalized['organization']);
        self::assertSame('Lead', $normalized['role']);
        self::assertSame(['alice@example.com', 'other@example.com'], $normalized['email']);
        self::assertSame(['00 32 470 12 34 56', '+32470123457'], $normalized['phone']);
        self::assertSame('LinkedIn', $normalized['source']);
        self::assertSame(ContactPriority::default()->value, $normalized['priority']);
        self::assertSame(ContactRelationshipStatus::default()->value, $normalized['relationship_status']);
        self::assertSame(['alpha', 'beta', 'gamma'], $normalized['tags']);
        self::assertSame('2026-05-01T10:00:00+00:00', $normalized['created_at']);
        self::assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T/', $normalized['updated_at']);
    }

    #[DataProvider('matchingCandidatesProvider')]
    public function testItFindsDuplicatesThroughStrongIdentityKeys(array $candidate, int $expectedIndex): void
    {
        $policy = new ContactWritePolicyService(new ContactMergeRulesService());

        $contacts = [];

        $emailContact = new Contact('contact-email', 'Email Match');
        $emailContact->setEmail('alice@example.com');
        $contacts[] = $emailContact;

        $phoneContact = new Contact('contact-phone', 'Phone Match');
        $phoneContact->setPhone('+32470123456');
        $contacts[] = $phoneContact;

        $profileContact = new Contact('contact-profile', 'Profile Match');
        $profileContact->setProfileUrl('https://www.linkedin.com/in/alice-martin');
        $contacts[] = $profileContact;

        $nameContact = new Contact('contact-name', 'Alice Martin');
        $nameContact->setFirstName('Alice');
        $nameContact->setLastName('Martin');
        $nameContact->setOrganization('Acme');
        $contacts[] = $nameContact;

        self::assertSame($expectedIndex, $policy->findMatchingContactIndex($contacts, $candidate));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: int}>
     */
    public static function matchingCandidatesProvider(): array
    {
        return [
            'email' => [
                [
                    'display_name' => 'Any Name',
                    'first_name' => 'Any',
                    'last_name' => 'Name',
                    'organization' => '',
                    'email' => 'alice@example.com',
                    'phone' => '',
                    'profile_url' => '',
                ],
                0,
            ],
            'phone' => [
                [
                    'display_name' => 'Any Name',
                    'first_name' => 'Any',
                    'last_name' => 'Name',
                    'organization' => '',
                    'email' => '',
                    'phone' => '0032470123456',
                    'profile_url' => '',
                ],
                1,
            ],
            'profile_url' => [
                [
                    'display_name' => 'Any Name',
                    'first_name' => 'Any',
                    'last_name' => 'Name',
                    'organization' => '',
                    'email' => '',
                    'phone' => '',
                    'profile_url' => 'https://www.linkedin.com/in/alice-martin/',
                ],
                2,
            ],
            'name keys' => [
                [
                    'display_name' => 'Alice Martin',
                    'first_name' => 'Alice',
                    'last_name' => 'Martin',
                    'organization' => 'Acme',
                    'email' => '',
                    'phone' => '',
                    'profile_url' => '',
                ],
                3,
            ],
        ];
    }

    public function testItAppliesTheMergePolicyWithoutLosingExistingUsefulValues(): void
    {
        $policy = new ContactWritePolicyService(new ContactMergeRulesService());

        $contact = new Contact('contact-merge', 'Old Name');
        $contact->setFirstName('Old');
        $contact->setLastName('Value');
        $contact->setOrganization('Old Org');
        $contact->setRole('Old Role');
        $contact->setMainChannel('Slack');
        $contact->setEmail(['old@example.com', 'shared@example.com']);
        $contact->setPhone(['+32470000000', '+32470000001']);
        $contact->setProfileUrl('https://example.com/old');
        $contact->setSource('crm');
        $contact->setPriority(ContactPriority::Low);
        $contact->setRelationshipStatus(ContactRelationshipStatus::Cold);
        $contact->setLastContactAt(new DateTimeImmutable('2026-05-01T00:00:00+00:00'));
        $contact->setNextActionAt(new DateTimeImmutable('2026-05-10T00:00:00+00:00'));
        $contact->setNextAction('Old follow-up');
        $contact->setNotes('Old notes');
        $contact->setTags(['alpha']);
        $contact->setCreatedAt(new DateTimeImmutable('2026-05-01T08:00:00+00:00'));
        $contact->setUpdatedAt(new DateTimeImmutable('2026-05-01T08:00:00+00:00'));

        $policy->applyContactData($contact, [
            'id' => 'contact-merge',
            'display_name' => 'New Name',
            'first_name' => 'New',
            'last_name' => '',
            'organization' => 'New Org',
            'role' => 'New Role',
            'main_channel' => '',
            'email' => ['shared@example.com', 'new@example.com'],
            'phone' => ['+32470000001', '+32470000002'],
            'profile_url' => 'https://www.linkedin.com/in/new-name',
            'source' => 'website',
            'priority' => ContactPriority::High->value,
            'relationship_status' => ContactRelationshipStatus::Priority->value,
            'last_contact_at' => '2026-05-28',
            'next_action_at' => '2026-06-01',
            'next_action' => 'Follow up',
            'notes' => 'New notes',
            'tags' => ['beta', 'alpha', 'gamma'],
            'created_at' => '2026-05-27T10:00:00+00:00',
            'updated_at' => '2026-05-29T10:00:00+00:00',
        ], true);

        self::assertSame('New Name', $contact->getDisplayName());
        self::assertSame('New', $contact->getFirstName());
        self::assertSame('Value', $contact->getLastName());
        self::assertSame('New Org', $contact->getOrganization());
        self::assertSame('New Role', $contact->getRole());
        self::assertSame('LinkedIn', $contact->getMainChannel());
        self::assertSame(['old@example.com', 'shared@example.com', 'new@example.com'], $contact->getEmails());
        self::assertSame(['+32470000000', '+32470000001', '+32470000002'], $contact->getPhones());
        self::assertSame('old@example.com', $contact->getEmail());
        self::assertSame('+32470000000', $contact->getPhone());
        self::assertSame('https://www.linkedin.com/in/new-name', $contact->getProfileUrl());
        self::assertSame('crm | website', $contact->getSource());
        self::assertSame(ContactPriority::High, $contact->getPriority());
        self::assertSame(ContactRelationshipStatus::Priority, $contact->getRelationshipStatus());
        self::assertSame('2026-05-28', $contact->getLastContactAt()?->format('Y-m-d'));
        self::assertSame('2026-06-01', $contact->getNextActionAt()?->format('Y-m-d'));
        self::assertSame('Follow up', $contact->getNextAction());
        self::assertSame('New notes', $contact->getNotes());
        self::assertSame(['alpha', 'beta', 'gamma'], $contact->getTags());
        self::assertSame('2026-05-01T08:00:00+00:00', $contact->getCreatedAt()->format(DATE_ATOM));
        self::assertSame('2026-05-29T10:00:00+00:00', $contact->getUpdatedAt()->format(DATE_ATOM));
    }
}

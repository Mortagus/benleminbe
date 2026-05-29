<?php

declare(strict_types=1);

namespace App\Tests\Unit\Private\Service\Network;

use App\Entity\Network\Contact;
use App\Enum\Network\ContactPriority;
use App\Enum\Network\ContactRelationshipStatus;
use App\Private\Service\Network\ContactMergeRulesService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ContactMergeRulesServiceTest extends TestCase
{
    public function testItNormalizesSharedKeysConsistently(): void
    {
        $rules = new ContactMergeRulesService();

        self::assertSame('societe generale', $rules->normalizeComparableText(' Société  Générale '));
        self::assertSame('Acme Inc', $rules->normalizeOrganizationName('acme   inc'));
        self::assertSame('+32470123456', $rules->normalizePhoneKey('00 32 470 12 34 56'));
        self::assertSame('www.linkedin.com/in/benlem/?trk=profile', $rules->normalizeProfileUrlKey('https://www.linkedin.com/in/benlem/?trk=profile'));
        self::assertTrue($rules->isLinkedInProfileUrl('https://www.linkedin.com/in/benlem/'));
    }

    public function testItMergesAndScoresSharedValues(): void
    {
        $rules = new ContactMergeRulesService();

        $left = new Contact('c_left', 'Left Person');
        $left->setFirstName('Left');
        $left->setEmail('left@example.com');
        $left->setTags(['alpha', 'beta']);
        $left->setLastContactAt(new DateTimeImmutable('2026-05-10'));

        $right = new Contact('c_right', 'Right Person');
        $right->setLastName('Right');
        $right->setProfileUrl('https://example.com/right');
        $right->setTags(['beta', 'gamma']);
        $right->setNextActionAt(new DateTimeImmutable('2026-05-12'));

        self::assertSame('crm | website | linkedin', $rules->mergeSourceValues('CRM', 'Website | LinkedIn'));
        self::assertSame(['alpha', 'beta', 'gamma'], $rules->mergeTags(['alpha', 'beta'], ['beta', 'gamma']));
        self::assertSame('2026-05-12', $rules->mergeLatestDate($left->getLastContactAt(), $right->getNextActionAt())?->format('Y-m-d'));
        self::assertSame('2026-05-10', $rules->mergeEarliestDate($left->getLastContactAt(), $right->getNextActionAt())?->format('Y-m-d'));
        self::assertSame(ContactPriority::High, $rules->mergeContactPriority(ContactPriority::Medium, ContactPriority::High));
        self::assertSame(ContactRelationshipStatus::Priority, $rules->mergeContactRelationshipStatus(ContactRelationshipStatus::Cold, ContactRelationshipStatus::Priority));
        self::assertSame('Left Person', $rules->preferContactValue(null, ' Left Person '));
        self::assertSame(6, $rules->scoreContactCompleteness($left));
        self::assertSame(6, $rules->scoreContactCompleteness($right));
        self::assertTrue($rules->isSparseContactForAutoMerge(new Contact('c_sparse', 'Sparse')));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Private\Service\Network;

use App\Entity\Network\Contact;
use App\Private\Service\Network\ContactMergeReviewScoringService;
use App\Private\Service\Network\ContactMergeRulesService;
use PHPUnit\Framework\TestCase;

final class ContactMergeReviewScoringServiceTest extends TestCase
{
    public function testItTreatsSharedEmailAndPhoneListsAsPositiveSignals(): void
    {
        $service = new ContactMergeReviewScoringService(new ContactMergeRulesService());

        $left = new Contact('contact-left', 'Nicolas Potier');
        $left->setEmails(['nicolas@example.com', 'shared@example.com']);
        $left->setPhones(['+32470123456']);
        $left->setOrganization('Acseo');

        $right = new Contact('contact-right', 'Nicolas Potier');
        $right->setEmails(['shared@example.com', 'other@example.com']);
        $right->setPhones(['+32470123456', '+32470999999']);
        $right->setOrganization('Acseo');

        $pair = $service->buildCandidatePair($left, $right);

        self::assertNotNull($pair);
        self::assertContains('Email identique', $pair['reasons']);
        self::assertContains('Téléphone identique', $pair['reasons']);
        self::assertNotContains('Conflit à trancher: entreprise', $pair['reasons']);
    }
}

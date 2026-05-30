<?php

declare(strict_types=1);

namespace App\Tests\Unit\Private\Service\Network;

use App\Private\Service\Network\ContactMergeRulesService;
use App\Private\Service\Network\ContactRoleClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ContactRoleClassifierTest extends TestCase
{
    private ContactRoleClassifier $classifier;

    protected function setUp(): void
    {
        $this->classifier = new ContactRoleClassifier(new ContactMergeRulesService());
    }

    public static function provideRoleClassifications(): iterable
    {
        yield 'recruitment' => [
            'Talent Acquisition Specialist',
            ContactRoleClassifier::CATEGORY_RECRUITMENT_HR,
            'Recrutement / RH',
            'recruitment_keywords:talent acquisition',
        ];

        yield 'technical' => [
            'Senior Symfony Developer',
            ContactRoleClassifier::CATEGORY_DEVELOPER_TECHNICAL,
            'Développement / technique',
            'technical_keywords:developer',
        ];

        yield 'decision maker' => [
            'Co-Founder & CEO',
            ContactRoleClassifier::CATEGORY_MANAGER_DECISION_MAKER,
            'Direction / décideur',
            'decision_maker_keywords:ceo',
        ];

        yield 'business' => [
            'Business Development Manager',
            ContactRoleClassifier::CATEGORY_BUSINESS_SALES_ACCOUNT,
            'Business / sales / account',
            'commercial_keywords:business development',
        ];

        yield 'product' => [
            'Product Owner',
            ContactRoleClassifier::CATEGORY_PRODUCT_PROJECT_ANALYSIS,
            'Produit / projet / analyse',
            'product_project_keywords:product owner',
        ];

        yield 'design' => [
            'Director of Communications',
            ContactRoleClassifier::CATEGORY_DESIGN_MARKETING_COMMUNICATION,
            'Design / marketing / communication',
            'creative_keywords:communications',
        ];

        yield 'infra' => [
            'System and Network Administrator',
            ContactRoleClassifier::CATEGORY_IT_OPS_SECURITY_INFRA,
            'Ops / sécurité / infra',
            'infra_keywords:system and network administrator',
        ];

        yield 'generic review' => [
            'Assistant Manager',
            ContactRoleClassifier::CATEGORY_TO_REVIEW,
            'À revoir',
            'generic_role',
        ];

        yield 'fallback other' => [
            'Dentisphere',
            ContactRoleClassifier::CATEGORY_OTHER,
            'Autre',
            'fallback_other',
        ];
    }

    #[DataProvider('provideRoleClassifications')]
    public function testItClassifiesRoles(string $role, string $expectedCategory, string $expectedLabel, string $expectedRulePrefix): void
    {
        $classification = $this->classifier->classify($role);

        self::assertSame($expectedCategory, $classification['category']);
        self::assertSame($expectedLabel, $classification['label']);
        self::assertNotSame('', $classification['normalized_role']);
        self::assertGreaterThan(0, $classification['confidence']);
        self::assertIsString($classification['matched_rule']);
        self::assertStringStartsWith($expectedRulePrefix, $classification['matched_rule']);
    }

    public function testItHandlesEmptyRoles(): void
    {
        $classification = $this->classifier->classify('');

        self::assertSame(ContactRoleClassifier::CATEGORY_TO_REVIEW, $classification['category']);
        self::assertSame('À revoir', $classification['label']);
        self::assertSame(0, $classification['confidence']);
        self::assertNull($classification['matched_rule']);
        self::assertSame('', $classification['normalized_role']);
    }
}

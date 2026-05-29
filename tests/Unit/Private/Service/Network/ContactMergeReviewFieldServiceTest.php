<?php

declare(strict_types=1);

namespace App\Tests\Unit\Private\Service\Network;

use App\Entity\Network\Contact;
use App\Private\Service\Network\ContactMergeRulesService;
use App\Private\Service\Network\ContactMergeReviewFieldService;
use PHPUnit\Framework\TestCase;

final class ContactMergeReviewFieldServiceTest extends TestCase
{
    public function testItHighlightsTheDifferingCharactersInScalarFields(): void
    {
        $service = new ContactMergeReviewFieldService(new ContactMergeRulesService());

        $left = new Contact('contact-left', 'Nicolas Potier');
        $left->setOrganization('Acseo');

        $right = new Contact('contact-right', 'Nicolas Potier');
        $right->setOrganization('Asceo');

        $fields = $service->buildComparisonFields($left, $right, []);

        $organizationField = null;
        foreach ($fields as $field) {
            if ($field['name'] === 'organization') {
                $organizationField = $field;
                break;
            }
        }

        self::assertIsArray($organizationField);
        self::assertSame('Acseo', $organizationField['left_value']);
        self::assertSame('Asceo', $organizationField['right_value']);
        self::assertSame('A<span class="private-merge-diff">cs</span>eo', $organizationField['left_display']);
        self::assertSame('A<span class="private-merge-diff">sc</span>eo', $organizationField['right_display']);
    }
}

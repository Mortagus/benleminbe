<?php

declare(strict_types=1);

namespace App\Tests\Unit\Private\Service\Network;

use App\Private\Service\Network\ContactMessageSuggestionBuilder;
use App\Private\Service\Network\ContactMergeRulesService;
use App\Private\Service\Network\ContactRoleClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ContactMessageSuggestionBuilderTest extends TestCase
{
    private ContactMessageSuggestionBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new ContactMessageSuggestionBuilder(
            new ContactMergeRulesService(),
            new ContactRoleClassifier(new ContactMergeRulesService()),
        );
    }

    public static function provideMessageSuggestions(): iterable
    {
        yield 'recruitment linkedin' => [
            [
                'first_name' => 'Anne',
                'role_category' => ContactRoleClassifier::CATEGORY_RECRUITMENT_HR,
                'profile_url' => 'https://www.linkedin.com/in/anne-example',
                'emails' => ['anne@example.com'],
                'phones' => ['0475 25 89 41'],
            ],
            'recruitment_hr',
            'linkedin',
            'LinkedIn',
            'Disponibilité freelance - développement web backend',
            'Bonjour Anne,',
        ];

        yield 'email fallback' => [
            [
                'first_name' => 'Marc',
                'role_category' => ContactRoleClassifier::CATEGORY_DEVELOPER_TECHNICAL,
                'profile_url' => '',
                'emails' => ['marc@example.com'],
                'phones' => [],
            ],
            'default',
            'email',
            'Email',
            'Prise de contact freelance',
            'Bonjour Marc,',
        ];

        yield 'phone fallback' => [
            [
                'first_name' => '',
                'role_category' => ContactRoleClassifier::CATEGORY_DESIGN_MARKETING_COMMUNICATION,
                'profile_url' => '',
                'emails' => [],
                'phones' => ['+32 475 25 89 41'],
            ],
            'default',
            'phone',
            'Téléphone',
            'Prise de contact freelance',
            'Bonjour,',
        ];

        yield 'no channel fallback' => [
            [
                'first_name' => '',
                'role_category' => ContactRoleClassifier::CATEGORY_OTHER,
                'profile_url' => '',
                'emails' => [],
                'phones' => [],
            ],
            'default',
            'none',
            'Aucun canal exploitable',
            'Prise de contact freelance',
            'Bonjour,',
        ];
    }

    #[DataProvider('provideMessageSuggestions')]
    public function testItBuildsSuggestions(array $contact, string $expectedTemplate, string $expectedChannel, string $expectedChannelLabel, string $expectedSubject, string $expectedGreeting): void
    {
        $suggestion = $this->builder->build($contact);

        self::assertSame($expectedTemplate, $suggestion['template']);
        self::assertSame($expectedChannel, $suggestion['recommended_channel']);
        self::assertSame($expectedChannelLabel, $suggestion['recommended_channel_label']);
        self::assertSame($expectedSubject, $suggestion['subject']);
        self::assertStringStartsWith($expectedGreeting, $suggestion['message']);
    }
}

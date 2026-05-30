<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

final class ContactMessageSuggestionBuilder
{
    private const string RECOMMENDED_CHANNEL_LINKEDIN = 'linkedin';
    private const string RECOMMENDED_CHANNEL_EMAIL = 'email';
    private const string RECOMMENDED_CHANNEL_PHONE = 'phone';
    private const string RECOMMENDED_CHANNEL_NONE = 'none';

    /**
     * @var array<string, array{subject: string, body: string}>
     */
    private const MESSAGE_TEMPLATES = [
        ContactRoleClassifier::CATEGORY_RECRUITMENT_HR => [
            'subject' => 'Disponibilité freelance - développement web backend',
            'body' => <<<TEXT
Bonjour{greeting},

Je me permets de vous recontacter car je suis actuellement disponible pour des missions freelance en développement web backend, principalement PHP, Symfony et Drupal.

J’ai plus de 15 ans d’expérience sur des applications web métier, de la reprise d’existant, de la maintenance évolutive et des projets nécessitant une bonne compréhension du contexte.

Si vous avez des besoins en cours ou à venir, je serais ravi d’en discuter avec vous.

Bonne journée,
Benjamin Lemin
TEXT,
        ],
    ];

    private const DEFAULT_TEMPLATE = [
        'subject' => 'Prise de contact freelance',
        'body' => <<<TEXT
Bonjour{greeting},

Je me permets de vous recontacter car je suis actuellement disponible pour des missions freelance.

Si vous avez des besoins en cours ou à venir, je serais ravi d’en discuter avec vous.

Bonne journée,
Benjamin Lemin
TEXT,
    ];

    public function __construct(
        private readonly ContactMergeRulesService $mergeRules,
        private readonly ContactRoleClassifier $roleClassifier,
    ) {
    }

    /**
     * @param array<string, mixed> $contact
     *
     * @return array{
     *     template: string,
     *     subject: string,
     *     message: string,
     *     recommended_channel: string,
     *     recommended_channel_label: string
     * }
     */
    public function build(array $contact): array
    {
        $template = $this->messageTemplateFor($contact);
        $greeting = $this->buildGreeting($contact);

        return [
            'template' => $this->templateKeyFor($contact),
            'subject' => $template['subject'],
            'message' => $this->renderTemplate($template['body'], $greeting),
            'recommended_channel' => $this->recommendedChannelFor($contact),
            'recommended_channel_label' => $this->recommendedChannelLabelFor($contact),
        ];
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function templateKeyFor(array $contact): string
    {
        $category = $this->normalizedCategory($contact);
        if ($category !== '' && isset(self::MESSAGE_TEMPLATES[$category])) {
            return $category;
        }

        return 'default';
    }

    /**
     * @param array<string, mixed> $contact
     *
     * @return array{subject: string, body: string}
     */
    private function messageTemplateFor(array $contact): array
    {
        $category = $this->normalizedCategory($contact);

        return self::MESSAGE_TEMPLATES[$category] ?? self::DEFAULT_TEMPLATE;
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function normalizedCategory(array $contact): string
    {
        $category = trim((string) ($contact['role_category'] ?? ''));

        return $this->roleClassifier->isKnownCategory($category) ? $category : '';
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function recommendedChannelFor(array $contact): string
    {
        if ($this->hasLinkedInProfile($contact)) {
            return self::RECOMMENDED_CHANNEL_LINKEDIN;
        }

        if ($this->hasEmail($contact)) {
            return self::RECOMMENDED_CHANNEL_EMAIL;
        }

        if ($this->hasPhone($contact)) {
            return self::RECOMMENDED_CHANNEL_PHONE;
        }

        return self::RECOMMENDED_CHANNEL_NONE;
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function recommendedChannelLabelFor(array $contact): string
    {
        return match ($this->recommendedChannelFor($contact)) {
            self::RECOMMENDED_CHANNEL_LINKEDIN => 'LinkedIn',
            self::RECOMMENDED_CHANNEL_EMAIL => 'Email',
            self::RECOMMENDED_CHANNEL_PHONE => 'Téléphone',
            default => 'Aucun canal exploitable',
        };
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function buildGreeting(array $contact): string
    {
        $firstName = trim((string) ($contact['first_name'] ?? ''));
        if ($firstName === '') {
            return '';
        }

        return $firstName;
    }

    private function renderTemplate(string $template, string $greeting): string
    {
        $replacement = $greeting !== '' ? ' ' . $greeting : '';
        $message = str_replace('{greeting}', $replacement, $template);

        return preg_replace('/[ \t]+(?=\R)/u', '', $message) ?? $message;
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function hasLinkedInProfile(array $contact): bool
    {
        return $this->mergeRules->isLinkedInProfileUrl($contact['profile_url'] ?? '')
            || mb_strtolower(trim((string) ($contact['main_channel'] ?? ''))) === 'linkedin';
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function hasEmail(array $contact): bool
    {
        $emails = $contact['emails'] ?? [];
        if (!is_array($emails)) {
            $emails = [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $email): string => trim((string) $email),
            $emails,
        ))) !== [];
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function hasPhone(array $contact): bool
    {
        $phones = $contact['phones'] ?? [];
        if (!is_array($phones)) {
            $phones = [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $phone): string => trim((string) $phone),
            $phones,
        ))) !== [];
    }
}

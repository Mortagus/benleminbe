<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

use App\Entity\Network\Contact;
use App\Enum\Network\ContactPriority;
use App\Enum\Network\ContactRelationshipStatus;
use DateTimeImmutable;

final class ContactMergeReviewFieldService
{
    /**
     * @var list<array{name: string, label: string, mode: string}>
     */
    private const array FIELD_DEFINITIONS = [
        ['name' => 'display_name', 'label' => 'Nom affiché', 'mode' => 'scalar'],
        ['name' => 'first_name', 'label' => 'Prénom', 'mode' => 'scalar'],
        ['name' => 'last_name', 'label' => 'Nom', 'mode' => 'scalar'],
        ['name' => 'organization', 'label' => 'Entreprise', 'mode' => 'scalar'],
        ['name' => 'role', 'label' => 'Rôle', 'mode' => 'scalar'],
        ['name' => 'main_channel', 'label' => 'Canal principal', 'mode' => 'scalar'],
        ['name' => 'email', 'label' => 'Email', 'mode' => 'scalar'],
        ['name' => 'phone', 'label' => 'Téléphone', 'mode' => 'scalar'],
        ['name' => 'profile_url', 'label' => 'Profil', 'mode' => 'scalar'],
        ['name' => 'source', 'label' => 'Source', 'mode' => 'union'],
        ['name' => 'priority', 'label' => 'Priorité', 'mode' => 'ranked'],
        ['name' => 'relationship_status', 'label' => 'Relation', 'mode' => 'ranked'],
        ['name' => 'last_contact_at', 'label' => 'Dernier contact', 'mode' => 'latest_date'],
        ['name' => 'next_action_at', 'label' => 'Prochaine action le', 'mode' => 'earliest_date'],
        ['name' => 'next_action', 'label' => 'Prochaine action', 'mode' => 'scalar'],
        ['name' => 'notes', 'label' => 'Notes', 'mode' => 'notes'],
        ['name' => 'tags', 'label' => 'Tags', 'mode' => 'tags'],
    ];

    public function __construct(
        private readonly ContactMergeRulesService $mergeRules,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function buildComparisonFields(Contact $leftContact, Contact $rightContact, array $fieldChoices): array
    {
        $fields = [];

        foreach (self::FIELD_DEFINITIONS as $definition) {
            $fieldName = $definition['name'];
            $mode = $definition['mode'];

            $fields[] = [
                'name' => $fieldName,
                'label' => $definition['label'],
                'mode' => $mode,
                'left_value' => $this->formatFieldValue($leftContact, $fieldName),
                'right_value' => $this->formatFieldValue($rightContact, $fieldName),
                'choice' => $fieldChoices[$fieldName] ?? $this->defaultChoiceForField($fieldName, $leftContact, $rightContact),
                'choices' => $this->choiceOptionsForMode($mode),
            ];
        }

        return $fields;
    }

    /**
     * @return array<string, string>
     */
    public function buildDefaultFieldChoices(Contact $leftContact, Contact $rightContact): array
    {
        $choices = [];

        foreach (self::FIELD_DEFINITIONS as $definition) {
            $choices[$definition['name']] = $this->defaultChoiceForField($definition['name'], $leftContact, $rightContact);
        }

        return $choices;
    }

    /**
     * @param array<string, string> $fieldChoices
     *
     * @return array<string, string>
     */
    public function normalizeFieldChoices(array $fieldChoices, Contact $leftContact, Contact $rightContact): array
    {
        $normalized = [];

        foreach (self::FIELD_DEFINITIONS as $definition) {
            $fieldName = $definition['name'];
            $choice = $this->normalizeChoice($fieldChoices[$fieldName] ?? $this->defaultChoiceForField($fieldName, $leftContact, $rightContact), $definition['mode']);
            $normalized[$fieldName] = $choice;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $fieldChoices
     */
    public function applyMergeChoices(Contact $canonical, Contact $source, array $fieldChoices): void
    {
        foreach (self::FIELD_DEFINITIONS as $definition) {
            $fieldName = $definition['name'];
            $choice = $fieldChoices[$fieldName] ?? $this->defaultChoiceForField($fieldName, $canonical, $source);
            $value = $this->resolveFieldValue($fieldName, $choice, $canonical, $source);

            match ($fieldName) {
                'display_name' => $canonical->setDisplayName($this->normalizeDisplayName($value, $canonical, $source)),
                'first_name' => $canonical->setFirstName($this->mergeRules->normalizeOptionalString($value)),
                'last_name' => $canonical->setLastName($this->mergeRules->normalizeOptionalString($value)),
                'organization' => $canonical->setOrganization($this->mergeRules->normalizeOrganizationName($value)),
                'role' => $canonical->setRole($this->mergeRules->normalizeOptionalString($value)),
                'main_channel' => $canonical->setMainChannel($this->mergeRules->normalizeOptionalString($value)),
                'email' => $canonical->setEmail($this->mergeRules->normalizeOptionalString($value)),
                'phone' => $canonical->setPhone($this->mergeRules->normalizeOptionalString($value)),
                'profile_url' => $canonical->setProfileUrl($this->mergeRules->normalizeOptionalString($value)),
                'source' => $canonical->setSource($this->mergeRules->normalizeOptionalString($value)),
                'priority' => $canonical->setPriority($this->priorityFromValue($value)),
                'relationship_status' => $canonical->setRelationshipStatus($this->relationshipStatusFromValue($value)),
                'last_contact_at' => $canonical->setLastContactAt($value instanceof DateTimeImmutable ? $value : $this->parseDate($value)),
                'next_action_at' => $canonical->setNextActionAt($value instanceof DateTimeImmutable ? $value : $this->parseDate($value)),
                'next_action' => $canonical->setNextAction($this->mergeRules->normalizeOptionalString($value)),
                'notes' => $canonical->setNotes($this->normalizeNotesValue($value, $canonical, $source, $choice)),
                'tags' => $canonical->setTags($this->normalizeTagsValue($value)),
                default => null,
            };
        }
    }

    public function normalizeFieldChoicesForStorage(array $choices): array
    {
        $normalized = [];

        foreach ($choices as $field => $choice) {
            $field = trim((string) $field);
            $choice = trim((string) $choice);
            if ($field !== '' && $choice !== '') {
                $normalized[$field] = $choice;
            }
        }

        return $normalized;
    }

    public function recommendedCanonicalSide(Contact $leftContact, Contact $rightContact): string
    {
        return $this->mergeRules->scoreContactCompleteness($rightContact) > $this->mergeRules->scoreContactCompleteness($leftContact) ? 'right' : 'left';
    }

    /**
     * @return array<string, string>
     */
    private function choiceOptionsForMode(string $mode): array
    {
        return match ($mode) {
            'union', 'notes', 'tags' => [
                'left' => 'Gauche',
                'right' => 'Droite',
                'union' => 'Fusionner les deux',
            ],
            'latest_date' => [
                'left' => 'Gauche',
                'right' => 'Droite',
                'latest' => 'La plus récente',
            ],
            'earliest_date' => [
                'left' => 'Gauche',
                'right' => 'Droite',
                'earliest' => 'La plus proche',
            ],
            'ranked' => [
                'left' => 'Gauche',
                'right' => 'Droite',
                'highest' => 'La plus forte',
            ],
            default => [
                'left' => 'Gauche',
                'right' => 'Droite',
            ],
        };
    }

    private function defaultChoiceForField(string $field, Contact $leftContact, Contact $rightContact): string
    {
        return match ($field) {
            'source', 'tags' => $this->hasValue($leftContact, $field) && $this->hasValue($rightContact, $field) ? 'union' : ($this->hasValue($leftContact, $field) ? 'left' : 'right'),
            'notes' => $this->hasValue($leftContact, $field) && $this->hasValue($rightContact, $field) ? 'union' : ($this->hasValue($leftContact, $field) ? 'left' : 'right'),
            'last_contact_at' => 'latest',
            'next_action_at' => 'earliest',
            'priority', 'relationship_status' => 'highest',
            default => $this->preferredSideForField($field, $leftContact, $rightContact),
        };
    }

    private function preferredSideForField(string $field, Contact $leftContact, Contact $rightContact): string
    {
        $leftValue = $this->formatFieldValue($leftContact, $field);
        $rightValue = $this->formatFieldValue($rightContact, $field);

        if ($leftValue === '' && $rightValue !== '') {
            return 'right';
        }

        if ($rightValue === '' && $leftValue !== '') {
            return 'left';
        }

        return $this->mergeRules->scoreContactCompleteness($rightContact) > $this->mergeRules->scoreContactCompleteness($leftContact) ? 'right' : 'left';
    }

    private function hasValue(Contact $contact, string $field): bool
    {
        $value = $this->rawFieldValue($contact, $field);

        if (is_array($value)) {
            return $value !== [];
        }

        if ($value instanceof DateTimeImmutable) {
            return true;
        }

        if ($value instanceof ContactPriority || $value instanceof ContactRelationshipStatus) {
            return true;
        }

        return $this->mergeRules->normalizeOptionalString($value) !== null;
    }

    private function normalizeChoice(string $choice, string $mode): string
    {
        $choice = strtolower(trim($choice));
        $allowed = array_keys($this->choiceOptionsForMode($mode));

        return in_array($choice, $allowed, true) ? $choice : $allowed[0];
    }

    private function resolveFieldValue(string $field, string $choice, Contact $leftContact, Contact $rightContact): mixed
    {
        $leftValue = $this->rawFieldValue($leftContact, $field);
        $rightValue = $this->rawFieldValue($rightContact, $field);

        return match ($field) {
            'source' => $choice === 'union' ? $this->mergeRules->mergeSourceValues($leftValue, $rightValue) : ($choice === 'right' ? $rightValue : $leftValue),
            'notes' => $choice === 'union' ? $this->mergeNotesValues($leftContact, $rightContact) : ($choice === 'right' ? $rightValue : $leftValue),
            'tags' => $choice === 'union' ? $this->mergeRules->mergeTags($this->normalizeTagsValue($leftValue), $this->normalizeTagsValue($rightValue)) : ($choice === 'right' ? $rightValue : $leftValue),
            'last_contact_at' => $choice === 'latest' ? $this->pickLatestDateValue($leftValue, $rightValue) : ($choice === 'right' ? $rightValue : $leftValue),
            'next_action_at' => $choice === 'earliest' ? $this->pickEarliestDateValue($leftValue, $rightValue) : ($choice === 'right' ? $rightValue : $leftValue),
            'priority' => $choice === 'highest' ? $this->pickHighestPriorityValue($leftContact->getPriority(), $rightContact->getPriority()) : ($choice === 'right' ? $rightContact->getPriority()->value : $leftContact->getPriority()->value),
            'relationship_status' => $choice === 'highest' ? $this->pickHighestRelationshipValue($leftContact->getRelationshipStatus(), $rightContact->getRelationshipStatus()) : ($choice === 'right' ? $rightContact->getRelationshipStatus()->value : $leftContact->getRelationshipStatus()->value),
            default => $choice === 'right' ? $rightValue : $leftValue,
        };
    }

    private function normalizeDisplayName(mixed $value, Contact $canonical, Contact $source): string
    {
        $value = $this->mergeRules->normalizeOptionalString($value);
        if ($value !== null) {
            return $value;
        }

        foreach ([
            trim(($canonical->getFirstName() ?? '') . ' ' . ($canonical->getLastName() ?? '')),
            trim(($source->getFirstName() ?? '') . ' ' . ($source->getLastName() ?? '')),
            trim(($canonical->getOrganization() ?? '') . ' ' . ($canonical->getRole() ?? '')),
            trim(($source->getOrganization() ?? '') . ' ' . ($source->getRole() ?? '')),
        ] as $candidate) {
            $candidate = trim(preg_replace('/\s+/', ' ', $candidate) ?? $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return $canonical->getDisplayName();
    }

    private function normalizeNotesValue(mixed $value, Contact $canonical, Contact $source, string $choice): ?string
    {
        if ($choice === 'union') {
            return $this->mergeNotesValues($canonical, $source);
        }

        return $this->mergeRules->normalizeOptionalString($value);
    }

    /**
     * @return list<string>
     */
    private function normalizeTagsValue(mixed $value): array
    {
        return $this->mergeRules->normalizeTags($value);
    }

    private function rawFieldValue(Contact $contact, string $field): mixed
    {
        return match ($field) {
            'display_name' => $contact->getDisplayName(),
            'first_name' => $contact->getFirstName(),
            'last_name' => $contact->getLastName(),
            'organization' => $contact->getOrganization(),
            'role' => $contact->getRole(),
            'main_channel' => $contact->getMainChannel(),
            'email' => $contact->getEmail(),
            'phone' => $contact->getPhone(),
            'profile_url' => $contact->getProfileUrl(),
            'source' => $contact->getSource(),
            'priority' => $contact->getPriority(),
            'relationship_status' => $contact->getRelationshipStatus(),
            'last_contact_at' => $contact->getLastContactAt(),
            'next_action_at' => $contact->getNextActionAt(),
            'next_action' => $contact->getNextAction(),
            'notes' => $contact->getNotes(),
            'tags' => $contact->getTags(),
            default => null,
        };
    }

    private function formatFieldValue(Contact $contact, string $field): string
    {
        return match ($field) {
            'display_name' => $contact->getDisplayName(),
            'first_name' => $contact->getFirstName() ?? '',
            'last_name' => $contact->getLastName() ?? '',
            'organization' => $contact->getOrganization() ?? '',
            'role' => $contact->getRole() ?? '',
            'main_channel' => $contact->getMainChannel() ?? '',
            'email' => $contact->getEmail() ?? '',
            'phone' => $contact->getPhone() ?? '',
            'profile_url' => $contact->getProfileUrl() ?? '',
            'source' => $contact->getSource() ?? '',
            'priority' => $contact->getPriorityLabel(),
            'relationship_status' => $contact->getRelationshipStatusLabel(),
            'last_contact_at' => $this->formatDate($contact->getLastContactAt()) ?? '',
            'next_action_at' => $this->formatDate($contact->getNextActionAt()) ?? '',
            'next_action' => $contact->getNextAction() ?? '',
            'notes' => $contact->getNotes() ?? '',
            'tags' => implode(', ', $contact->getTags()),
            default => '',
        };
    }

    private function mergeNotesValues(Contact $leftContact, Contact $rightContact): ?string
    {
        $blocks = [];

        $leftNotes = $this->mergeRules->normalizeOptionalString($leftContact->getNotes());
        if ($leftNotes !== null) {
            $blocks[] = sprintf('[%s] %s', $leftContact->getDisplayName(), $leftNotes);
        }

        $rightNotes = $this->mergeRules->normalizeOptionalString($rightContact->getNotes());
        if ($rightNotes !== null) {
            $blocks[] = sprintf('[%s] %s', $rightContact->getDisplayName(), $rightNotes);
        }

        return $blocks === [] ? null : implode("\n\n", $blocks);
    }

    private function pickLatestDateValue(mixed $left, mixed $right): ?DateTimeImmutable
    {
        return $this->mergeRules->mergeLatestDate($left instanceof DateTimeImmutable ? $left : null, $right instanceof DateTimeImmutable ? $right : null);
    }

    private function pickEarliestDateValue(mixed $left, mixed $right): ?DateTimeImmutable
    {
        return $this->mergeRules->mergeEarliestDate($left instanceof DateTimeImmutable ? $left : null, $right instanceof DateTimeImmutable ? $right : null);
    }

    private function pickHighestPriorityValue(ContactPriority $left, ContactPriority $right): string
    {
        return $this->mergeRules->priorityWeight($right->value) > $this->mergeRules->priorityWeight($left->value) ? $right->value : $left->value;
    }

    private function pickHighestRelationshipValue(ContactRelationshipStatus $left, ContactRelationshipStatus $right): string
    {
        return $this->mergeRules->relationshipWeight($right) > $this->mergeRules->relationshipWeight($left) ? $right->value : $left->value;
    }

    private function priorityFromValue(mixed $priority): ContactPriority
    {
        if ($priority instanceof ContactPriority) {
            return $priority;
        }

        $priority = $this->mergeRules->normalizeOptionalString($priority) ?? ContactPriority::default()->value;

        return ContactPriority::tryFrom($priority) ?? ContactPriority::default();
    }

    private function relationshipStatusFromValue(mixed $status): ContactRelationshipStatus
    {
        if ($status instanceof ContactRelationshipStatus) {
            return $status;
        }

        $status = $this->mergeRules->normalizeOptionalString($status) ?? ContactRelationshipStatus::default()->value;

        return ContactRelationshipStatus::tryFrom($status) ?? ContactRelationshipStatus::default();
    }

    private function parseDate(mixed $value): ?DateTimeImmutable
    {
        $value = $this->mergeRules->normalizeOptionalString($value);
        if ($value === null) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDate(?DateTimeImmutable $value): ?string
    {
        return $value !== null ? $value->format('Y-m-d') : null;
    }
}

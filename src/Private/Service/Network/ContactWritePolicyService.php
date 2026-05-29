<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

use App\Entity\Network\Contact;
use App\Enum\Network\ContactPriority;
use App\Enum\Network\ContactRelationshipStatus;
use DateTimeImmutable;
use InvalidArgumentException;

final class ContactWritePolicyService
{
    public function __construct(
        private readonly ContactMergeRulesService $mergeRules,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<Contact> $existingContacts
     *
     * @return array<string, mixed>
     */
    public function normalizeContactPayload(array $payload, ?string $existingId, array $existingContacts, string $sourceLabel = ''): array
    {
        $displayName = $this->normalizeString($payload['display_name'] ?? '');
        $firstName = $this->normalizeString($payload['first_name'] ?? '');
        $lastName = $this->normalizeString($payload['last_name'] ?? '');
        $organization = $this->normalizeOrganizationName($payload['organization'] ?? '');
        $role = $this->normalizeString($payload['role'] ?? '');

        if ($displayName === '') {
            $displayName = trim($firstName . ' ' . $lastName);
        }

        if ($displayName === '') {
            $displayName = trim($organization . ' ' . $role);
        }

        if ($displayName === '') {
            throw new InvalidArgumentException('Contact display name is required.');
        }

        $id = $existingId ?? $this->normalizeString($payload['id'] ?? '');
        $id = $id !== '' ? $id : $this->generateId('contact');

        $contact = [
            'id' => $id,
            'display_name' => $displayName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'organization' => $organization,
            'role' => $role,
            'main_channel' => $this->normalizeString($payload['main_channel'] ?? ''),
            'email' => $this->normalizeString($payload['email'] ?? ''),
            'phone' => $this->normalizeString($payload['phone'] ?? ''),
            'profile_url' => $this->normalizeString($payload['profile_url'] ?? ''),
            'source' => $this->normalizeString($payload['source'] ?? $sourceLabel),
            'priority' => $this->normalizeContactPriority($payload['priority'] ?? 'moyenne')->value,
            'relationship_status' => $this->normalizeContactRelationStatus($payload['relationship_status'] ?? 'a_relancer')->value,
            'last_contact_at' => $this->normalizeDate($payload['last_contact_at'] ?? null),
            'next_action_at' => $this->normalizeDate($payload['next_action_at'] ?? null),
            'next_action' => $this->normalizeString($payload['next_action'] ?? ''),
            'notes' => $this->normalizeString($payload['notes'] ?? ''),
            'tags' => $this->normalizeTags($payload['tags'] ?? []),
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];

        $existingIndex = $this->findMatchingContactIndex($existingContacts, $contact);
        if ($existingIndex !== null) {
            $contact['created_at'] = $this->formatDateTime($existingContacts[$existingIndex]->getCreatedAt()) ?? $contact['created_at'];
        }

        return $contact;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function applyContactData(Contact $contact, array $data, bool $merge): void
    {
        if (!$merge) {
            $contact->setId($data['id']);
        }

        $contact->setDisplayName($merge ? $this->mergeString($contact->getDisplayName(), $data['display_name']) : $data['display_name']);
        $contact->setFirstName($merge ? $this->mergeString($contact->getFirstName(), $data['first_name']) : $data['first_name']);
        $contact->setLastName($merge ? $this->mergeString($contact->getLastName(), $data['last_name']) : $data['last_name']);
        $contact->setOrganization($merge ? $this->mergeOrganizationValue($contact->getOrganization(), $data['organization']) : ($data['organization'] !== '' ? $this->normalizeOrganizationName($data['organization']) : null));
        $contact->setRole($merge ? $this->mergeString($contact->getRole(), $data['role']) : $data['role']);
        $contact->setMainChannel($merge ? $this->resolveMainChannelValue(
            $contact->getMainChannel(),
            $data['main_channel'],
            $contact->getProfileUrl(),
            $data['profile_url'],
        ) : $data['main_channel']);
        $contact->setEmail($merge ? $this->mergeString($contact->getEmail(), $data['email']) : $data['email']);
        $contact->setPhone($merge ? $this->mergeString($contact->getPhone(), $data['phone']) : $data['phone']);
        $contact->setProfileUrl($merge ? $this->mergeString($contact->getProfileUrl(), $data['profile_url']) : $data['profile_url']);
        $contact->setSource($merge ? $this->mergeRules->mergeSourceValues($contact->getSource(), $data['source']) : $data['source']);
        $contact->setPriority($this->normalizeContactPriority($data['priority']));
        $contact->setRelationshipStatus($this->normalizeContactRelationStatus($data['relationship_status']));
        $contact->setLastContactAt($merge ? $this->mergeDate($contact->getLastContactAt(), $data['last_contact_at']) : $this->parseDate($data['last_contact_at']));
        $contact->setNextActionAt($merge ? $this->mergeDate($contact->getNextActionAt(), $data['next_action_at']) : $this->parseDate($data['next_action_at']));
        $contact->setNextAction($merge ? $this->mergeString($contact->getNextAction(), $data['next_action']) : $data['next_action']);
        $contact->setNotes($merge ? $this->mergeString($contact->getNotes(), $data['notes']) : $data['notes']);
        $contact->setTags($merge ? $this->mergeRules->mergeTags($contact->getTags(), $data['tags']) : $data['tags']);

        if (!$merge) {
            $contact->setCreatedAt($this->parseDateTime($data['created_at']) ?? new DateTimeImmutable());
        }

        $contact->setUpdatedAt($this->parseDateTime($data['updated_at']) ?? new DateTimeImmutable());
    }

    /**
     * @param array<string, mixed> $candidate
     * @param list<Contact> $contacts
     */
    public function findMatchingContactIndex(array $contacts, array $candidate): ?int
    {
        $candidateEmail = mb_strtolower($this->normalizeString($candidate['email'] ?? ''));
        $candidatePhone = $this->mergeRules->normalizePhoneKey($candidate['phone'] ?? null);
        $candidateProfileUrl = $this->mergeRules->normalizeProfileUrlKey($candidate['profile_url'] ?? null);
        $candidateNameKeys = $this->mergeRules->buildContactNameKeys(
            $candidate['display_name'] ?? '',
            $candidate['first_name'] ?? '',
            $candidate['last_name'] ?? '',
            $candidate['organization'] ?? '',
        );

        foreach ($contacts as $index => $contact) {
            if (!$contact instanceof Contact) {
                continue;
            }

            $contactEmail = mb_strtolower($this->normalizeString($contact->getEmail() ?? ''));
            $contactPhone = $this->mergeRules->normalizePhoneKey($contact->getPhone());
            $contactProfileUrl = $this->mergeRules->normalizeProfileUrlKey($contact->getProfileUrl());
            $contactNameKeys = $this->mergeRules->buildContactNameKeys(
                $contact->getDisplayName(),
                $contact->getFirstName() ?? '',
                $contact->getLastName() ?? '',
                $contact->getOrganization() ?? '',
            );

            if ($candidateEmail !== '' && $contactEmail !== '' && $candidateEmail === $contactEmail) {
                return $index;
            }

            if ($candidatePhone !== '' && $contactPhone !== '' && $candidatePhone === $contactPhone) {
                return $index;
            }

            if ($candidateProfileUrl !== '' && $contactProfileUrl !== '' && $candidateProfileUrl === $contactProfileUrl) {
                return $index;
            }

            if ($candidateNameKeys !== [] && array_intersect($candidateNameKeys, $contactNameKeys) !== []) {
                return $index;
            }
        }

        return null;
    }

    private function mergeString(?string $current, string $incoming): ?string
    {
        $incoming = $this->normalizeString($incoming);

        return $incoming !== '' ? $incoming : $current;
    }

    private function mergeOrganizationValue(?string $current, ?string $incoming): ?string
    {
        $incoming = $this->normalizeOrganizationName($incoming);
        if ($incoming !== '') {
            return $incoming;
        }

        $current = $this->normalizeOrganizationName($current);

        return $current !== '' ? $current : null;
    }

    private function resolveMainChannelValue(mixed $currentMainChannel, mixed $incomingMainChannel, mixed $currentProfileUrl, mixed $incomingProfileUrl): ?string
    {
        if ($this->mergeRules->isLinkedInProfileUrl($currentProfileUrl) || $this->mergeRules->isLinkedInProfileUrl($incomingProfileUrl)) {
            return 'LinkedIn';
        }

        $incoming = $this->normalizeString((string) $incomingMainChannel);
        if ($incoming !== '') {
            return $incoming;
        }

        $current = $this->normalizeString((string) $currentMainChannel);

        return $current !== '' ? $current : null;
    }

    private function normalizeContactPriority(mixed $priority): ContactPriority
    {
        if ($priority instanceof ContactPriority) {
            return $priority;
        }

        $priority = $this->mergeRules->normalizeOptionalString($priority) ?? ContactPriority::default()->value;

        return ContactPriority::tryFrom($priority) ?? ContactPriority::default();
    }

    private function normalizeContactRelationStatus(mixed $status): ContactRelationshipStatus
    {
        if ($status instanceof ContactRelationshipStatus) {
            return $status;
        }

        $status = $this->mergeRules->normalizeOptionalString($status) ?? ContactRelationshipStatus::default()->value;

        return ContactRelationshipStatus::tryFrom($status) ?? ContactRelationshipStatus::default();
    }

    /**
     * @param mixed $tags
     *
     * @return list<string>
     */
    private function normalizeTags(mixed $tags): array
    {
        return $this->mergeRules->normalizeTags($tags);
    }

    private function normalizeOrganizationName(mixed $value): ?string
    {
        return $this->mergeRules->normalizeOrganizationName($value);
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = $this->normalizeString((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($value))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseDate(mixed $value): ?DateTimeImmutable
    {
        $date = $this->normalizeDate($value);
        if ($date === null) {
            return null;
        }

        try {
            return new DateTimeImmutable($date);
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseDateTime(mixed $value): ?DateTimeImmutable
    {
        $value = $this->normalizeString((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function mergeDate(?DateTimeImmutable $current, mixed $incoming): ?DateTimeImmutable
    {
        $incoming = $this->parseDate($incoming);

        return $incoming ?? $current;
    }

    private function now(): string
    {
        return (new DateTimeImmutable())->format(DATE_ATOM);
    }

    private function normalizeString(mixed $value): string
    {
        return trim((string) $value);
    }

    private function formatDateTime(?DateTimeImmutable $value): ?string
    {
        return $value !== null ? $value->format(DATE_ATOM) : null;
    }

    private function generateId(string $prefix): string
    {
        return sprintf('%s_%s', $prefix, bin2hex(random_bytes(8)));
    }
}

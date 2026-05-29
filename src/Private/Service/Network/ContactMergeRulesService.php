<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

use App\Entity\Network\Contact;
use App\Enum\Network\ContactPriority;
use App\Enum\Network\ContactRelationshipStatus;
use DateTimeImmutable;

final class ContactMergeRulesService
{
    private const int SPARSE_AUTO_MERGE_SCORE_THRESHOLD = 3;

    public function normalizeOptionalString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    public function normalizeComparableText(mixed $value): string
    {
        $value = $this->normalizeOptionalString($value);
        if ($value === null) {
            return '';
        }

        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    public function normalizeOrganizationName(mixed $value): ?string
    {
        $value = $this->normalizeOptionalString($value);
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = mb_strtolower($value);

        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    public function normalizePhoneKey(mixed $phone): string
    {
        $phone = $this->normalizeOptionalString($phone);
        if ($phone === null) {
            return '';
        }

        $phone = preg_replace('/[^0-9+]/', '', $phone) ?? $phone;
        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        return mb_strtolower($phone);
    }

    public function normalizeProfileUrlKey(mixed $profileUrl): string
    {
        $profileUrl = $this->normalizeOptionalString($profileUrl);
        if ($profileUrl === null) {
            return '';
        }

        $profileUrl = mb_strtolower($profileUrl);
        $parts = parse_url($profileUrl);
        if ($parts === false) {
            return rtrim($profileUrl, '/');
        }

        $key = $parts['host'] ?? '';
        if ($key === '' && isset($parts['path'])) {
            $key = $parts['path'];
        } elseif (isset($parts['path'])) {
            $key .= $parts['path'];
        }

        if (isset($parts['query'])) {
            $key .= '?' . $parts['query'];
        }

        if (isset($parts['fragment'])) {
            $key .= '#' . $parts['fragment'];
        }

        return rtrim($key, '/');
    }

    public function isLinkedInProfileUrl(mixed $profileUrl): bool
    {
        $profileUrl = $this->normalizeProfileUrlKey($profileUrl);

        return $profileUrl !== '' && str_contains($profileUrl, 'linkedin.com');
    }

    /**
     * @return list<string>
     */
    public function sourceTokens(?string $source): array
    {
        $source = $this->normalizeOptionalString($source);
        if ($source === null) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (string $token): string => trim(mb_strtolower($token)),
            preg_split('/\s*[|,;\/]\s*/', mb_strtolower($source)) ?: [],
        ), static fn (string $token): bool => $token !== '')));
    }

    public function mergeSourceValues(?string $left, ?string $right): ?string
    {
        $labels = [];

        foreach ([$left, $right] as $value) {
            $value = $this->normalizeOptionalString($value);
            if ($value === null) {
                continue;
            }

            foreach ($this->sourceTokens($value) as $token) {
                $labels[] = $token;
            }
        }

        $labels = array_values(array_unique($labels));

        return $labels === [] ? null : implode(' | ', $labels);
    }

    /**
     * @param list<string> $left
     * @param list<string> $right
     *
     * @return list<string>
     */
    public function mergeTags(array $left, array $right): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $tag): string => trim((string) $tag),
            array_merge($left, $right),
        ), static fn (string $tag): bool => $tag !== '')));
    }

    /**
     * @return list<string>
     */
    public function normalizeTags(mixed $tags): array
    {
        if (is_string($tags)) {
            $tags = preg_split('/[,\n;]/', $tags) ?: [];
        }

        if (!is_array($tags)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $tag): string => trim((string) $tag),
            $tags,
        ), static fn (string $tag): bool => $tag !== '')));
    }

    public function mergeLatestDate(?DateTimeImmutable $current, ?DateTimeImmutable $incoming): ?DateTimeImmutable
    {
        if ($current === null) {
            return $incoming;
        }

        if ($incoming === null) {
            return $current;
        }

        return $incoming > $current ? $incoming : $current;
    }

    public function mergeEarliestDate(?DateTimeImmutable $current, ?DateTimeImmutable $incoming): ?DateTimeImmutable
    {
        if ($current === null) {
            return $incoming;
        }

        if ($incoming === null) {
            return $current;
        }

        return $incoming < $current ? $incoming : $current;
    }

    public function mergeContactPriority(ContactPriority $current, ContactPriority $incoming): ContactPriority
    {
        return $this->priorityWeight($incoming->value) > $this->priorityWeight($current->value) ? $incoming : $current;
    }

    public function mergeContactRelationshipStatus(ContactRelationshipStatus $current, ContactRelationshipStatus $incoming): ContactRelationshipStatus
    {
        return $this->relationshipWeight($incoming) > $this->relationshipWeight($current) ? $incoming : $current;
    }

    public function preferContactValue(?string $current, ?string $incoming): ?string
    {
        $current = $this->normalizeOptionalString($current);
        if ($current !== null) {
            return $current;
        }

        $incoming = $this->normalizeOptionalString($incoming);

        return $incoming !== null ? $incoming : null;
    }

    public function scoreContactCompleteness(Contact $contact): int
    {
        $score = 0;

        foreach ([
            $contact->getDisplayName(),
            $contact->getFirstName(),
            $contact->getLastName(),
            $contact->getOrganization(),
            $contact->getRole(),
            $contact->getMainChannel(),
            $contact->getEmail(),
            $contact->getPhone(),
            $contact->getProfileUrl(),
            $contact->getSource(),
            $contact->getNextAction(),
            $contact->getNotes(),
        ] as $value) {
            if ($this->normalizeOptionalString($value) !== null) {
                $score++;
            }
        }

        $score += count($contact->getTags());

        if ($contact->getLastContactAt() !== null) {
            $score++;
        }

        if ($contact->getNextActionAt() !== null) {
            $score++;
        }

        return $score;
    }

    public function isSparseContactForAutoMerge(Contact $contact): bool
    {
        return $this->scoreContactCompleteness($contact) <= self::SPARSE_AUTO_MERGE_SCORE_THRESHOLD;
    }

    /**
     * @return list<string>
     */
    public function buildContactNameKeys(mixed $displayName, mixed $firstName, mixed $lastName, mixed $organization): array
    {
        $keys = [];
        $normalizedDisplayName = $this->normalizeComparableText($displayName);
        $normalizedFirstName = $this->normalizeComparableText($firstName);
        $normalizedLastName = $this->normalizeComparableText($lastName);
        $normalizedOrganization = $this->normalizeComparableText($organization);

        if ($normalizedDisplayName !== '' && $normalizedOrganization !== '') {
            $keys[] = 'display-org:' . $normalizedDisplayName . '|' . $normalizedOrganization;
        }

        if ($normalizedFirstName !== '' && $normalizedLastName !== '' && $normalizedOrganization !== '') {
            $keys[] = 'name-org:' . $normalizedFirstName . '|' . $normalizedLastName . '|' . $normalizedOrganization;
        }

        $initialLastKey = $this->buildInitialLastOrganizationKey($normalizedFirstName, $normalizedLastName, $normalizedOrganization);
        if ($initialLastKey !== null) {
            $keys[] = 'initial-org:' . $initialLastKey;
        }

        return array_values(array_unique($keys));
    }

    private function buildInitialLastOrganizationKey(string $firstName, string $lastName, string $organization): ?string
    {
        if ($firstName === '' || $lastName === '' || $organization === '') {
            return null;
        }

        $initial = mb_substr($firstName, 0, 1);
        if ($initial === '') {
            return null;
        }

        return $initial . '|' . $lastName . '|' . $organization;
    }

    public function priorityWeight(string $priority): int
    {
        return match ($priority) {
            'haute' => 3,
            'moyenne' => 2,
            'basse' => 1,
            default => 0,
        };
    }

    public function relationshipWeight(ContactRelationshipStatus $status): int
    {
        return match ($status) {
            ContactRelationshipStatus::Priority => 5,
            ContactRelationshipStatus::FollowUp => 4,
            ContactRelationshipStatus::InProgress => 3,
            ContactRelationshipStatus::Waiting => 2,
            ContactRelationshipStatus::Cold => 1,
        };
    }
}

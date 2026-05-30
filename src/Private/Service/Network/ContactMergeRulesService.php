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

    /**
     * @return list<string>
     */
    public function normalizeEmailList(mixed $emails): array
    {
        $emails = $this->normalizeDelimitedValueList($emails);
        if ($emails === []) {
            return [];
        }

        $normalized = [];
        $seen = [];

        foreach ($emails as $email) {
            $key = mb_strtolower($email);
            if ($key !== '' && !isset($seen[$key])) {
                $seen[$key] = true;
                $normalized[] = $key;
            }
        }

        return $normalized;
    }

    /**
     * @return list<string>
     */
    public function normalizePhoneList(mixed $phones): array
    {
        $phones = $this->normalizeDelimitedValueList($phones);
        if ($phones === []) {
            return [];
        }

        $normalized = [];
        $seen = [];

        foreach ($phones as $phone) {
            $key = $this->normalizePhoneKey($phone);
            if ($key !== '' && !isset($seen[$key])) {
                $seen[$key] = true;
                $normalized[] = $phone;
            }
        }

        return $normalized;
    }

    /**
     * @param list<string> $left
     * @param list<string> $right
     *
     * @return list<string>
     */
    public function mergeEmailLists(array $left, array $right): array
    {
        return $this->normalizeEmailList(array_merge($left, $right));
    }

    /**
     * @param list<string> $left
     * @param list<string> $right
     *
     * @return list<string>
     */
    public function mergePhoneLists(array $left, array $right): array
    {
        return $this->normalizePhoneList(array_merge($left, $right));
    }

    /**
     * @param list<string> $left
     * @param list<string> $right
     */
    public function hasSharedEmailValue(array $left, array $right): bool
    {
        return array_intersect($this->normalizeEmailList($left), $this->normalizeEmailList($right)) !== [];
    }

    /**
     * @param list<string> $left
     * @param list<string> $right
     */
    public function hasSharedPhoneValue(array $left, array $right): bool
    {
        return array_intersect($this->phoneKeys($left), $this->phoneKeys($right)) !== [];
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

    public function isLinkedInSourceValue(mixed $source): bool
    {
        $source = $this->normalizeOptionalString($source);
        if ($source === null) {
            return false;
        }

        return str_contains(mb_strtolower($source), 'linkedin');
    }

    public function isLinkedInContact(Contact $contact): bool
    {
        return $this->isLinkedInSourceValue($contact->getSource())
            || $this->isLinkedInProfileUrl($contact->getProfileUrl())
            || mb_strtolower($this->normalizeOptionalString($contact->getMainChannel()) ?? '') === 'linkedin';
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

        if ($contact->getEmails() !== []) {
            $score++;
        }

        if ($contact->getPhones() !== []) {
            $score++;
        }

        return $score;
    }

    public function isSparseContactForAutoMerge(Contact $contact): bool
    {
        return $this->scoreContactCompleteness($contact) <= self::SPARSE_AUTO_MERGE_SCORE_THRESHOLD;
    }

    /**
     * @param mixed $values
     *
     * @return list<string>
     */
    private function normalizeDelimitedValueList(mixed $values): array
    {
        if (is_string($values)) {
            $values = preg_split('/[\r\n,;|]+/', $values) ?: [$values];
        }

        if (!is_array($values)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $values,
        ), static fn (string $value): bool => $value !== ''));
    }

    /**
     * @param list<string> $values
     *
     * @return list<string>
     */
    private function phoneKeys(array $values): array
    {
        $keys = [];

        foreach ($values as $value) {
            $key = $this->normalizePhoneKey($value);
            if ($key !== '' && !in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        return $keys;
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

    public function formatPhoneDisplayValue(mixed $phone): string
    {
        $phone = $this->normalizeOptionalString($phone);
        if ($phone === null) {
            return '';
        }

        $normalized = $this->normalizePhoneKey($phone);
        if ($normalized === '') {
            return $phone;
        }

        if (str_starts_with($normalized, '+32')) {
            return $this->formatBelgianPhoneDigits(substr($normalized, 3), true);
        }

        if (preg_match('/^0\d+$/', $normalized) === 1) {
            return $this->formatBelgianPhoneDigits(substr($normalized, 1), false);
        }

        if (str_starts_with($normalized, '+')) {
            return '+' . $this->formatGenericPhoneDigits(ltrim($normalized, '+'));
        }

        return $this->formatGenericPhoneDigits($normalized);
    }

    /**
     * @param list<string> $phones
     */
    public function formatPhoneListDisplay(array $phones, string $separator = ', '): string
    {
        $phones = array_values(array_filter(array_map(
            fn (mixed $phone): string => $this->formatPhoneDisplayValue($phone),
            $phones,
        ), static fn (string $phone): bool => $phone !== ''));

        return implode($separator, $phones);
    }

    private function formatBelgianPhoneDigits(string $digits, bool $internationalPrefix): string
    {
        $digits = preg_replace('/\D+/', '', $digits) ?? '';
        if ($digits === '') {
            return $internationalPrefix ? '+32' : '';
        }

        if ($internationalPrefix) {
            if (strlen($digits) === 9 && str_starts_with($digits, '4')) {
                return sprintf('+32 %s %s %s %s', substr($digits, 0, 3), substr($digits, 3, 2), substr($digits, 5, 2), substr($digits, 7, 2));
            }

            if (strlen($digits) === 8 && str_starts_with($digits, '1')) {
                return sprintf('+32 %s %s %s %s', substr($digits, 0, 2), substr($digits, 2, 2), substr($digits, 4, 2), substr($digits, 6, 2));
            }

            if (strlen($digits) === 8 && in_array($digits[0], ['2', '3', '4', '9'], true)) {
                return sprintf('+32 %s %s %s %s', $digits[0], substr($digits, 1, 3), substr($digits, 4, 2), substr($digits, 6, 2));
            }

            return '+32 ' . $this->groupDigitsFromLeft($digits, 2);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '4')) {
            return sprintf('0%s %s %s %s', substr($digits, 0, 3), substr($digits, 3, 2), substr($digits, 5, 2), substr($digits, 7, 2));
        }

        if (strlen($digits) === 8 && str_starts_with($digits, '1')) {
            return sprintf('0%s %s %s %s', substr($digits, 0, 2), substr($digits, 2, 2), substr($digits, 4, 2), substr($digits, 6, 2));
        }

        if (strlen($digits) === 8 && in_array($digits[0], ['2', '3', '4', '9'], true)) {
            return sprintf('0%s %s %s %s', $digits[0], substr($digits, 1, 3), substr($digits, 4, 2), substr($digits, 6, 2));
        }

        return '0' . $this->groupDigitsFromLeft($digits, 2);
    }

    private function formatGenericPhoneDigits(string $digits): string
    {
        $digits = preg_replace('/\D+/', '', $digits) ?? '';
        if ($digits === '') {
            return '';
        }

        if (strlen($digits) <= 4) {
            return $digits;
        }

        return $this->groupDigitsFromRight($digits, 2);
    }

    private function groupDigitsFromLeft(string $digits, int $size): string
    {
        $digits = preg_replace('/\D+/', '', $digits) ?? '';
        if ($digits === '' || $size <= 0) {
            return $digits;
        }

        return trim(implode(' ', str_split($digits, $size)));
    }

    private function groupDigitsFromRight(string $digits, int $size): string
    {
        $digits = preg_replace('/\D+/', '', $digits) ?? '';
        if ($digits === '' || $size <= 0) {
            return $digits;
        }

        $groups = [];
        while ($digits !== '') {
            $groups[] = substr($digits, -$size);
            $digits = substr($digits, 0, -$size);
        }

        return implode(' ', array_reverse($groups));
    }
}

<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

use App\Entity\Network\Contact;
use App\Enum\Network\ContactRelationshipStatus;
use App\Enum\Network\ContactPriority;

final class ContactMergeReviewScoringService
{
    private const int MIN_REVIEW_SCORE_FOR_CANDIDATE = 50;

    public function __construct(
        private readonly ContactMergeRulesService $mergeRules,
    ) {
    }

    /**
     * @return array{score: int, review_score: int, reasons: list<string>}|null
     */
    public function buildCandidatePair(Contact $left, Contact $right): ?array
    {
        if ($this->mergeRules->isLinkedInContact($left) || $this->mergeRules->isLinkedInContact($right)) {
            return null;
        }

        $exactScoreData = $this->computeExactScore($left, $right);
        $reviewScoreData = $this->computeReviewScore($left, $right, $exactScoreData['score']);

        if ($reviewScoreData['score'] < self::MIN_REVIEW_SCORE_FOR_CANDIDATE) {
            return null;
        }

        return [
            'score' => $exactScoreData['score'],
            'review_score' => $reviewScoreData['score'],
            'reasons' => $this->buildComparisonReasons($left, $right),
        ];
    }

    public function buildFingerprint(string $leftId, string $rightId): string
    {
        $ids = [$leftId, $rightId];
        sort($ids, SORT_STRING);

        return implode('|', $ids);
    }

    /**
     * @return list<string>
     */
    private function buildComparisonReasons(Contact $left, Contact $right): array
    {
        $reasons = [];
        $blocking = [];

        foreach ([
            'organization' => 'entreprise',
            'role' => 'rôle',
            'main_channel' => 'canal principal',
            'profile_url' => 'profil',
        ] as $field => $label) {
            $leftValue = $this->comparisonFieldValue($left, $field);
            $rightValue = $this->comparisonFieldValue($right, $field);

            if ($leftValue === '' && $rightValue === '') {
                continue;
            }

            if ($leftValue === $rightValue) {
                continue;
            }

            if ($leftValue === '' || $rightValue === '') {
                continue;
            }

            if ($field === 'main_channel' && ($this->isLinkedInProfileUrl($left->getProfileUrl()) || $this->isLinkedInProfileUrl($right->getProfileUrl()))) {
                continue;
            }

            $blocking[] = $label;
        }

        if ($blocking !== []) {
            $reasons[] = 'Conflit à trancher: ' . implode(', ', $blocking);

            return $reasons;
        }

        if ($this->mergeRules->hasSharedEmailValue($left->getEmails(), $right->getEmails())) {
            $reasons[] = 'Email identique';
        }

        if ($this->mergeRules->hasSharedPhoneValue($left->getPhones(), $right->getPhones())) {
            $reasons[] = 'Téléphone identique';
        }

        if ($this->normalizeUrlKey($left->getProfileUrl()) !== '' && $this->normalizeUrlKey($left->getProfileUrl()) === $this->normalizeUrlKey($right->getProfileUrl())) {
            $reasons[] = 'Profil identique';
        }

        $displaySimilarity = $this->textSimilarity($left->getDisplayName(), $right->getDisplayName());
        if ($displaySimilarity >= 100) {
            $reasons[] = 'Nom affiché identique';
        } elseif ($displaySimilarity >= 90) {
            $reasons[] = 'Nom affiché proche';
        }

        $nameSimilarity = $this->textSimilarity($this->buildComparableFullName($left), $this->buildComparableFullName($right));
        if ($nameSimilarity >= 100) {
            $reasons[] = 'Prénom et nom identiques';
        } elseif ($nameSimilarity >= 90) {
            $reasons[] = 'Prénom et nom proches';
        }

        if ($this->normalizeComparableText($left->getOrganization()) !== '' && $this->normalizeComparableText($left->getOrganization()) === $this->normalizeComparableText($right->getOrganization())) {
            $reasons[] = 'Entreprise identique';
        }

        if ($this->normalizeComparableText($left->getRole()) !== '' && $this->normalizeComparableText($left->getRole()) === $this->normalizeComparableText($right->getRole())) {
            $reasons[] = 'Rôle identique';
        }

        $namePenaltyData = $this->computeNameMismatchPenalty($left, $right);
        $reasons = array_merge($reasons, $namePenaltyData['reasons']);

        $reasons = array_values(array_unique($reasons));

        return $reasons !== [] ? $reasons : ['Pas de clé forte suffisante'];
    }

    /**
     * @return array{score: int, reasons: list<string>}
     */
    private function computeExactScore(Contact $left, Contact $right): array
    {
        $score = 0;
        $reasons = [];

        if ($this->mergeRules->hasSharedPhoneValue($left->getPhones(), $right->getPhones())) {
            $score += 100;
            $reasons[] = 'Téléphone identique';
        }

        if ($this->mergeRules->hasSharedEmailValue($left->getEmails(), $right->getEmails())) {
            $score += 95;
            $reasons[] = 'Email identique';
        }

        if ($this->normalizeUrlKey($left->getProfileUrl()) !== '' && $this->normalizeUrlKey($left->getProfileUrl()) === $this->normalizeUrlKey($right->getProfileUrl())) {
            $score += 90;
            $reasons[] = 'Profil identique';
        }

        if ($this->normalizeComparableText($left->getDisplayName()) !== '' && $this->normalizeComparableText($left->getDisplayName()) === $this->normalizeComparableText($right->getDisplayName())) {
            $score += 50;
            $reasons[] = 'Nom affiché identique';
        }

        $leftFirstLast = trim($this->normalizeComparableText((string) $left->getFirstName()) . ' ' . $this->normalizeComparableText((string) $left->getLastName()));
        $rightFirstLast = trim($this->normalizeComparableText((string) $right->getFirstName()) . ' ' . $this->normalizeComparableText((string) $right->getLastName()));
        if ($leftFirstLast !== '' && $leftFirstLast === $rightFirstLast && $this->normalizeComparableText($left->getDisplayName()) !== $this->normalizeComparableText($right->getDisplayName())) {
            $score += 40;
            $reasons[] = 'Prénom et nom identiques';
        }

        if ($this->normalizeComparableText($left->getOrganization()) !== '' && $this->normalizeComparableText($left->getOrganization()) === $this->normalizeComparableText($right->getOrganization())) {
            $score += 20;
            $reasons[] = 'Entreprise identique';
        }

        if ($this->normalizeComparableText($left->getRole()) !== '' && $this->normalizeComparableText($left->getRole()) === $this->normalizeComparableText($right->getRole())) {
            $score += 10;
            $reasons[] = 'Rôle identique';
        }

        $namePenaltyData = $this->computeNameMismatchPenalty($left, $right);
        $score += $namePenaltyData['score'];
        $reasons = array_merge($reasons, $namePenaltyData['reasons']);

        $leftSources = $this->sourceTokens($left->getSource());
        $rightSources = $this->sourceTokens($right->getSource());
        if ($leftSources !== [] && $rightSources !== [] && array_intersect($leftSources, $rightSources) !== []) {
            $score += 5;
            $reasons[] = 'Source commune';
        }

        return [
            'score' => min(100, $score),
            'reasons' => $reasons,
        ];
    }

    /**
     * @return array{score: int, reasons: list<string>}
     */
    private function computeReviewScore(Contact $left, Contact $right, int $exactScore): array
    {
        $score = $exactScore;
        $reasons = [];

        $displaySimilarity = $this->textSimilarity($left->getDisplayName(), $right->getDisplayName());
        if ($displaySimilarity >= 92) {
            $score += 30;
            $reasons[] = 'Nom affiché très proche';
        } elseif ($displaySimilarity >= 82) {
            $score += 20;
            $reasons[] = 'Nom affiché proche';
        } elseif ($displaySimilarity >= 70) {
            $score += 10;
            $reasons[] = 'Nom affiché partiellement proche';
        }

        $nameSimilarity = $this->textSimilarity(trim((string) $left->getFirstName() . ' ' . (string) $left->getLastName()), trim((string) $right->getFirstName() . ' ' . (string) $right->getLastName()));
        if ($nameSimilarity >= 90) {
            $score += 20;
            $reasons[] = 'Prénom et nom proches';
        } elseif ($nameSimilarity >= 80) {
            $score += 10;
            $reasons[] = 'Prénom et nom partiellement proches';
        }

        if ($this->normalizeComparableText($left->getOrganization()) !== '' && $this->normalizeComparableText($left->getOrganization()) === $this->normalizeComparableText($right->getOrganization())) {
            $score += 10;
            $reasons[] = 'Même entreprise';
        }

        if ($this->normalizeComparableText($left->getRole()) !== '' && $this->normalizeComparableText($left->getRole()) === $this->normalizeComparableText($right->getRole())) {
            $score += 5;
            $reasons[] = 'Même rôle';
        }

        $leftSources = $this->sourceTokens($left->getSource());
        $rightSources = $this->sourceTokens($right->getSource());
        if ($leftSources !== [] && $rightSources !== [] && array_intersect($leftSources, $rightSources) !== []) {
            $score += 5;
            $reasons[] = 'Source cohérente';
        }

        if ($this->mergeRules->scoreContactCompleteness($left) < 4 || $this->mergeRules->scoreContactCompleteness($right) < 4) {
            $score += 10;
            $reasons[] = 'Fiche partielle';
        }

        return [
            'score' => min(100, $score),
            'reasons' => $reasons,
        ];
    }

    /**
     * @return list<string>
     */
    private function sourceTokens(?string $source): array
    {
        return $this->mergeRules->sourceTokens($source);
    }

    private function textSimilarity(?string $left, ?string $right): int
    {
        $left = $this->mergeRules->normalizeComparableText($left);
        $right = $this->mergeRules->normalizeComparableText($right);

        if ($left === '' || $right === '') {
            return 0;
        }

        similar_text($left, $right, $percent);

        return (int) round($percent);
    }

    private function comparisonFieldValue(Contact $contact, string $field): string
    {
        return match ($field) {
            'organization' => $this->normalizeComparableText($contact->getOrganization()),
            'role' => $this->normalizeComparableText($contact->getRole()),
            'main_channel' => $this->normalizeComparableText($contact->getMainChannel()),
            'profile_url' => $this->normalizeUrlKey($contact->getProfileUrl()),
            default => '',
        };
    }

    private function buildComparableFullName(Contact $contact): string
    {
        return trim(
            $this->normalizeComparableText($contact->getFirstName()) . ' ' . $this->normalizeComparableText($contact->getLastName()),
        );
    }

    /**
     * @return array{score: int, reasons: list<string>}
     */
    private function computeNameMismatchPenalty(Contact $left, Contact $right): array
    {
        $score = 0;
        $reasons = [];

        $displaySimilarity = $this->textSimilarity($left->getDisplayName(), $right->getDisplayName());
        if ($displaySimilarity !== 100) {
            if ($displaySimilarity < 40) {
                $score -= 25;
                $reasons[] = 'Nom affiché très différent';
            } elseif ($displaySimilarity < 60) {
                $score -= 15;
                $reasons[] = 'Nom affiché différent';
            }
        }

        $fullNameSimilarity = $this->textSimilarity($this->buildComparableFullName($left), $this->buildComparableFullName($right));
        if ($fullNameSimilarity !== 100) {
            if ($fullNameSimilarity < 40) {
                $score -= 20;
                $reasons[] = 'Prénom et nom très différents';
            } elseif ($fullNameSimilarity < 60) {
                $score -= 10;
                $reasons[] = 'Prénom et nom différents';
            }
        }

        if ($displaySimilarity < 40 && $fullNameSimilarity < 40) {
            $score -= 10;
            $reasons[] = 'Identité nominative très faible';
        }

        return [
            'score' => $score,
            'reasons' => $reasons,
        ];
    }

    private function isLinkedInProfileUrl(mixed $profileUrl): bool
    {
        $profileUrl = $this->normalizeUrlKey($profileUrl);

        return $profileUrl !== '' && str_contains($profileUrl, 'linkedin.com');
    }

    private function normalizeComparableText(mixed $value): string
    {
        return $this->mergeRules->normalizeComparableText($value);
    }

    private function normalizeUrlKey(mixed $url): string
    {
        return $this->mergeRules->normalizeProfileUrlKey($url);
    }
}

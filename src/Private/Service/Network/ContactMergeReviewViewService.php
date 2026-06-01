<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

use App\Entity\Network\Contact;
use App\Entity\Network\ContactMergeReview;
use App\Enum\Network\ContactMergeReviewStatus;
use App\Enum\Network\ContactPriority;
use App\Enum\Network\ContactRelationshipStatus;

final class ContactMergeReviewViewService
{
    public function __construct(
        private readonly ContactMergeReviewFieldService $fieldService,
        private readonly ContactMergeRulesService $mergeRules,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function decorateReview(ContactMergeReview $review): array
    {
        $leftContact = $review->getLeftContact();
        $rightContact = $review->getRightContact();

        $left = $leftContact instanceof Contact
            ? $this->decorateContact($leftContact)
            : $this->decorateContactSnapshot($review->getLeftSnapshot());
        $right = $rightContact instanceof Contact
            ? $this->decorateContact($rightContact)
            : $this->decorateContactSnapshot($review->getRightSnapshot());

        if (!$leftContact instanceof Contact || !$rightContact instanceof Contact) {
            if ($review->getStatus() === ContactMergeReviewStatus::Pending) {
                throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException(sprintf('Merge review "%s" no longer has both contacts.', $review->getId()));
            }
        }

        $fieldChoices = array_merge(
            $this->fieldService->buildDefaultFieldChoices($leftContact instanceof Contact ? $leftContact : $this->contactFromSnapshot($review->getLeftSnapshot()), $rightContact instanceof Contact ? $rightContact : $this->contactFromSnapshot($review->getRightSnapshot())),
            $review->getStatus() === ContactMergeReviewStatus::Pending ? [] : $review->getFieldChoices(),
        );

        return [
            'id' => $review->getId(),
            'fingerprint' => $review->getFingerprint(),
            'status' => $review->getStatus()->value,
            'status_label' => $review->getStatusLabel(),
            'score' => $review->getScore(),
            'review_score' => $review->getReviewScore(),
            'reasons' => $this->normalizeReasonsForDisplay($review->getReasons()),
            'left_contact' => $left,
            'right_contact' => $right,
            'recommended_canonical_side' => $this->fieldService->recommendedCanonicalSide($leftContact instanceof Contact ? $leftContact : $this->contactFromSnapshot($review->getLeftSnapshot()), $rightContact instanceof Contact ? $rightContact : $this->contactFromSnapshot($review->getRightSnapshot())),
            'field_choices' => $fieldChoices,
            'fields' => $this->fieldService->buildComparisonFields($leftContact instanceof Contact ? $leftContact : $this->contactFromSnapshot($review->getLeftSnapshot()), $rightContact instanceof Contact ? $rightContact : $this->contactFromSnapshot($review->getRightSnapshot()), $fieldChoices),
            'resolved_contact' => $review->getResolvedContact() instanceof Contact ? $this->decorateContact($review->getResolvedContact()) : null,
            'created_at' => $this->formatDateTime($review->getCreatedAt()),
            'updated_at' => $this->formatDateTime($review->getUpdatedAt()),
            'reviewed_at' => $this->formatDateTime($review->getReviewedAt()),
            'resolved_at' => $this->formatDateTime($review->getResolvedAt()),
            'ignored_at' => $this->formatDateTime($review->getIgnoredAt()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function decorateContact(Contact $contact): array
    {
        return [
            'id' => $contact->getId(),
            'display_name' => $contact->getDisplayName(),
            'first_name' => $contact->getFirstName() ?? '',
            'last_name' => $contact->getLastName() ?? '',
            'organization' => $contact->getOrganization() ?? '',
            'role' => $contact->getRole() ?? '',
            'main_channel' => $contact->getMainChannel() ?? '',
            'email' => implode(', ', $contact->getEmails()),
            'phone' => $this->mergeRules->formatPhoneListDisplay($contact->getPhones()),
            'emails' => $contact->getEmails(),
            'phones' => $contact->getPhones(),
            'profile_url' => $contact->getProfileUrl() ?? '',
            'source' => $contact->getSource() ?? '',
            'priority' => $contact->getPriority()->value,
            'priority_label' => $contact->getPriorityLabel(),
            'relationship_status' => $contact->getRelationshipStatus()->value,
            'relationship_status_label' => $contact->getRelationshipStatusLabel(),
            'last_contact_at' => $this->formatDate($contact->getLastContactAt()),
            'next_action_at' => $this->formatDate($contact->getNextActionAt()),
            'next_action' => $contact->getNextAction() ?? '',
            'notes' => $contact->getNotes() ?? '',
            'tags' => array_values($contact->getTags()),
            'created_at' => $this->formatDateTime($contact->getCreatedAt()),
            'updated_at' => $this->formatDateTime($contact->getUpdatedAt()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function decorateContactSnapshot(array $snapshot): array
    {
        return array_merge([
            'id' => '',
            'display_name' => '',
            'first_name' => '',
            'last_name' => '',
            'organization' => '',
            'role' => '',
            'main_channel' => '',
            'email' => '',
            'phone' => '',
            'emails' => [],
            'phones' => [],
            'profile_url' => '',
            'source' => '',
            'priority' => ContactPriority::default()->value,
            'priority_label' => ContactPriority::default()->label(),
            'relationship_status' => ContactRelationshipStatus::default()->value,
            'relationship_status_label' => ContactRelationshipStatus::default()->label(),
            'last_contact_at' => null,
            'next_action_at' => null,
            'next_action' => '',
            'notes' => '',
            'tags' => [],
            'created_at' => null,
            'updated_at' => null,
        ], $snapshot);
    }

    public function contactFromSnapshot(array $snapshot): Contact
    {
        $data = $this->decorateContactSnapshot($snapshot);
        $contact = new Contact($data['id'] !== '' ? (string) $data['id'] : $this->generateId('contact'), (string) $data['display_name']);
        $contact->setFirstName($data['first_name'] !== '' ? (string) $data['first_name'] : null);
        $contact->setLastName($data['last_name'] !== '' ? (string) $data['last_name'] : null);
        $contact->setOrganization($data['organization'] !== '' ? (string) $data['organization'] : null);
        $contact->setRole($data['role'] !== '' ? (string) $data['role'] : null);
        $contact->setMainChannel($data['main_channel'] !== '' ? (string) $data['main_channel'] : null);
        $contact->setEmail($this->snapshotValues($data['emails'] ?? $data['email'] ?? null, $data['email'] ?? null));
        $contact->setPhone($this->snapshotValues($data['phones'] ?? $data['phone'] ?? null, $data['phone'] ?? null));
        $contact->setProfileUrl($data['profile_url'] !== '' ? (string) $data['profile_url'] : null);
        $contact->setSource($data['source'] !== '' ? (string) $data['source'] : null);
        $contact->setPriority($this->priorityFromValue($data['priority']));
        $contact->setRelationshipStatus($this->relationshipStatusFromValue($data['relationship_status']));

        return $contact;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveContactView(ContactMergeReview $review, string $side): array
    {
        $contact = $side === 'right' ? $review->getRightContact() : $review->getLeftContact();

        if ($contact instanceof Contact) {
            return $this->decorateContact($contact);
        }

        $snapshot = $side === 'right' ? $review->getRightSnapshot() : $review->getLeftSnapshot();

        return $this->decorateContactSnapshot($snapshot);
    }

    /**
     * @param list<ContactMergeReview> $reviews
     *
     * @return list<ContactMergeReview>
     */
    public function sortReviews(array $reviews): array
    {
        usort($reviews, static function (ContactMergeReview $left, ContactMergeReview $right): int {
            if ($left->getReviewScore() !== $right->getReviewScore()) {
                return $right->getReviewScore() <=> $left->getReviewScore();
            }

            if ($left->getScore() !== $right->getScore()) {
                return $right->getScore() <=> $left->getScore();
            }

            return strcmp($right->getUpdatedAt()->format(DATE_ATOM), $left->getUpdatedAt()->format(DATE_ATOM));
        });

        return $reviews;
    }

    /**
     * @param list<string> $reasons
     *
     * @return list<string>
     */
    public function normalizeReasonsForDisplay(array $reasons): array
    {
        $reasons = array_values(array_filter(array_map(
            static fn (mixed $reason): string => trim((string) $reason),
            $reasons,
        ), static fn (string $reason): bool => $reason !== ''));

        return $reasons !== [] ? array_values(array_unique($reasons)) : ['Pas de clé forte suffisante'];
    }

    private function priorityFromValue(mixed $priority): ContactPriority
    {
        if ($priority instanceof ContactPriority) {
            return $priority;
        }

        $priority = trim((string) $priority);

        return ContactPriority::tryFrom($priority) ?? ContactPriority::default();
    }

    private function relationshipStatusFromValue(mixed $status): ContactRelationshipStatus
    {
        if ($status instanceof ContactRelationshipStatus) {
            return $status;
        }

        $status = trim((string) $status);

        return ContactRelationshipStatus::tryFrom($status) ?? ContactRelationshipStatus::default();
    }

    private function formatDate(?\DateTimeImmutable $value): ?string
    {
        return $value !== null ? $value->format('Y-m-d') : null;
    }

    private function formatDateTime(?\DateTimeImmutable $value): ?string
    {
        return $value !== null ? $value->format(DATE_ATOM) : null;
    }

    private function generateId(string $prefix): string
    {
        return sprintf('%s_%s', $prefix, bin2hex(random_bytes(8)));
    }

    /**
     * @return list<string>
     */
    private function snapshotValues(mixed $value, mixed $fallback = null): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(
                static fn (mixed $entry): string => trim((string) $entry),
                $value,
            ), static fn (string $entry): bool => $entry !== ''));
        }

        if (is_string($value)) {
            $parts = preg_split('/[\r\n,;|]+/', trim($value)) ?: [];
            $parts = array_values(array_filter(array_map(
                static fn (string $entry): string => trim($entry),
                $parts,
            ), static fn (string $entry): bool => $entry !== ''));

            if ($parts !== []) {
                return $parts;
            }
        }

        if (is_array($fallback)) {
            return array_values(array_filter(array_map(
                static fn (mixed $entry): string => trim((string) $entry),
                $fallback,
            ), static fn (string $entry): bool => $entry !== ''));
        }

        if (is_string($fallback)) {
            $fallback = trim($fallback);
            if ($fallback !== '') {
                return [$fallback];
            }
        }

        return [];
    }
}

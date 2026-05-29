<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

use App\Entity\Network\Contact;
use App\Entity\Network\ContactMergeReview;
use App\Entity\Network\Interaction;
use App\Enum\Network\ContactMergeReviewStatus;
use App\Enum\Network\ContactPriority;
use App\Enum\Network\ContactRelationshipStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ContactMergeReviewService
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
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{
     *     stats: array{pending: int, resolved: int, ignored: int, total: int},
     *     reviews: list<array<string, mixed>>
     * }
     */
    public function getQueueData(): array
    {
        $reviews = $this->loadReviews();

        $pendingReviews = array_values(array_filter(
            $reviews,
            static fn (ContactMergeReview $review): bool => $review->getStatus() === ContactMergeReviewStatus::Pending,
        ));

        return [
            'stats' => [
                'pending' => count(array_filter($reviews, static fn (ContactMergeReview $review): bool => $review->getStatus() === ContactMergeReviewStatus::Pending)),
                'resolved' => count(array_filter($reviews, static fn (ContactMergeReview $review): bool => $review->getStatus() === ContactMergeReviewStatus::Resolved)),
                'ignored' => count(array_filter($reviews, static fn (ContactMergeReview $review): bool => $review->getStatus() === ContactMergeReviewStatus::Ignored)),
                'total' => count($reviews),
            ],
            'reviews' => array_map(
                fn (ContactMergeReview $review): array => $this->decorateReview($review),
                $this->sortReviews($pendingReviews),
            ),
        ];
    }

    /**
     * @return array{created: int, updated: int, skipped: int, considered: int, total: int}
     */
    public function refreshCandidates(): array
    {
        $contacts = $this->loadContacts();
        $reviews = [];

        foreach ($this->loadReviews() as $review) {
            $reviews[$review->getFingerprint()] = $review;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $considered = 0;
        $now = new DateTimeImmutable();

        $count = count($contacts);
        for ($leftIndex = 0; $leftIndex < $count; ++$leftIndex) {
            for ($rightIndex = $leftIndex + 1; $rightIndex < $count; ++$rightIndex) {
                $left = $contacts[$leftIndex];
                $right = $contacts[$rightIndex];

                $pair = $this->buildCandidatePair($left, $right);
                if ($pair === null) {
                    continue;
                }

                $considered++;
                $fingerprint = $this->buildFingerprint($left->getId(), $right->getId());
                $existingReview = $reviews[$fingerprint] ?? null;

                if ($existingReview instanceof ContactMergeReview) {
                    if ($existingReview->getStatus() !== ContactMergeReviewStatus::Pending) {
                        $skipped++;
                        continue;
                    }

                    $existingReview
                        ->setScore($pair['score'])
                        ->setReviewScore($pair['review_score'])
                        ->setReasons($pair['reasons'])
                        ->setLeftSnapshot($this->decorateContact($left))
                        ->setRightSnapshot($this->decorateContact($right))
                        ->setUpdatedAt($now);
                    $updated++;

                    continue;
                }

                $review = new ContactMergeReview(
                    $this->generateId('contact-merge-review'),
                    $fingerprint,
                    $left,
                    $right,
                );
                $review
                    ->setScore($pair['score'])
                    ->setReviewScore($pair['review_score'])
                    ->setReasons($pair['reasons'])
                    ->setLeftSnapshot($this->decorateContact($left))
                    ->setRightSnapshot($this->decorateContact($right))
                    ->setUpdatedAt($now);

                $this->entityManager->persist($review);
                $reviews[$fingerprint] = $review;
                $created++;
            }
        }

        $this->entityManager->flush();

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'considered' => $considered,
            'total' => $created + $updated + $skipped,
        ];
    }

    /**
     * @return array{deleted: int}
     */
    public function purgePendingReviews(): array
    {
        $deleted = $this->entityManager->createQuery(
            'DELETE FROM App\\Entity\\Network\\ContactMergeReview review WHERE review.status = :status',
        )
            ->setParameter('status', ContactMergeReviewStatus::Pending)
            ->execute();

        return [
            'deleted' => (int) $deleted,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getReview(string $id): array
    {
        $review = $this->loadReview($id);

        return $this->decorateReview($review);
    }

    /**
     * @param array<string, string> $fieldChoices
     *
     * @return array<string, mixed>
     */
    public function resolveReview(string $id, string $canonicalSide, array $fieldChoices): array
    {
        $review = $this->loadReview($id);
        if ($review->getStatus() !== ContactMergeReviewStatus::Pending) {
            throw new InvalidArgumentException('Ce doublon a déjà été traité.');
        }

        $leftContact = $review->getLeftContact();
        $rightContact = $review->getRightContact();
        if (!$leftContact instanceof Contact || !$rightContact instanceof Contact) {
            throw new NotFoundHttpException(sprintf('Review "%s" is missing contact references.', $id));
        }

        $canonicalSide = $this->normalizeChoiceSide($canonicalSide);
        $canonical = $canonicalSide === 'right' ? $rightContact : $leftContact;
        $source = $canonicalSide === 'right' ? $leftContact : $rightContact;
        $choices = $this->normalizeFieldChoices($fieldChoices, $leftContact, $rightContact);

        $movedInteractions = 0;

        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use (
            $review,
            $canonical,
            $source,
            $choices,
            &$movedInteractions,
        ): void {
            $this->applyMergeChoices($canonical, $source, $choices);
            $movedInteractions = $this->moveInteractions($canonical, $source);

            $canonical->setUpdatedAt(new DateTimeImmutable());
            $review
                ->setStatus(ContactMergeReviewStatus::Resolved)
                ->setFieldChoices($choices)
                ->setResolvedContact($canonical)
                ->setReviewedAt(new DateTimeImmutable())
                ->setResolvedAt(new DateTimeImmutable())
                ->setUpdatedAt(new DateTimeImmutable());

            $entityManager->persist($canonical);
            $entityManager->persist($review);
            $entityManager->remove($source);
        });

        return [
            'review' => $this->decorateReview($review),
            'resolved_contact' => $this->decorateContact($review->getResolvedContact() ?? $canonical),
            'moved_interactions' => $movedInteractions,
        ];
    }

    public function ignoreReview(string $id): array
    {
        $review = $this->loadReview($id);
        if ($review->getStatus() !== ContactMergeReviewStatus::Pending) {
            throw new InvalidArgumentException('Ce doublon a déjà été traité.');
        }

        $now = new DateTimeImmutable();
        $review
            ->setStatus(ContactMergeReviewStatus::Ignored)
            ->setReviewedAt($now)
            ->setIgnoredAt($now)
            ->setUpdatedAt($now);

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return $this->decorateReview($review);
    }

    /**
     * @return array<string, string>
     */
    public function getFieldChoiceLabels(): array
    {
        $choices = [];

        foreach (self::FIELD_DEFINITIONS as $definition) {
            $choices[$definition['name']] = array_keys($this->choiceOptionsForMode($definition['mode']));
        }

        return $choices;
    }

    /**
     * @return list<array{name: string, label: string, mode: string}>
     */
    public function getFieldDefinitions(): array
    {
        return self::FIELD_DEFINITIONS;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadContacts(): array
    {
        /** @var list<Contact> $contacts */
        $contacts = $this->entityManager->getRepository(Contact::class)->findAll();

        return $contacts;
    }

    /**
     * @return list<ContactMergeReview>
     */
    private function loadReviews(): array
    {
        /** @var list<ContactMergeReview> $reviews */
        $reviews = $this->entityManager->getRepository(ContactMergeReview::class)->findAll();

        return $reviews;
    }

    private function loadReview(string $id): ContactMergeReview
    {
        $review = $this->entityManager->getRepository(ContactMergeReview::class)->find($id);
        if (!$review instanceof ContactMergeReview) {
            throw new NotFoundHttpException(sprintf('Merge review "%s" was not found.', $id));
        }

        return $review;
    }

    /**
     * @return array<string, mixed>
     */
    private function decorateReview(ContactMergeReview $review): array
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
                throw new NotFoundHttpException(sprintf('Merge review "%s" no longer has both contacts.', $review->getId()));
            }
        }

        $fieldChoices = array_merge(
            $this->buildDefaultFieldChoices($leftContact instanceof Contact ? $leftContact : $this->contactFromSnapshot($review->getLeftSnapshot()), $rightContact instanceof Contact ? $rightContact : $this->contactFromSnapshot($review->getRightSnapshot())),
            $review->getStatus() === ContactMergeReviewStatus::Pending ? [] : $review->getFieldChoices(),
        );

        return [
            'id' => $review->getId(),
            'fingerprint' => $review->getFingerprint(),
            'status' => $review->getStatus()->value,
            'status_label' => $review->getStatusLabel(),
            'score' => $review->getScore(),
            'review_score' => $review->getReviewScore(),
            'reasons' => $review->getReasons(),
            'left_contact' => $left,
            'right_contact' => $right,
            'recommended_canonical_side' => $this->recommendedCanonicalSide($leftContact, $rightContact),
            'field_choices' => $fieldChoices,
            'fields' => $this->buildComparisonFields($leftContact, $rightContact, $fieldChoices),
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
    private function decorateContact(Contact $contact): array
    {
        return [
            'id' => $contact->getId(),
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
    private function decorateContactSnapshot(array $snapshot): array
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

    private function contactFromSnapshot(array $snapshot): Contact
    {
        $data = $this->decorateContactSnapshot($snapshot);
        $contact = new Contact($data['id'] !== '' ? (string) $data['id'] : $this->generateId('contact'), (string) $data['display_name']);
        $contact->setFirstName($data['first_name'] !== '' ? (string) $data['first_name'] : null);
        $contact->setLastName($data['last_name'] !== '' ? (string) $data['last_name'] : null);
        $contact->setOrganization($data['organization'] !== '' ? (string) $data['organization'] : null);
        $contact->setRole($data['role'] !== '' ? (string) $data['role'] : null);
        $contact->setMainChannel($data['main_channel'] !== '' ? (string) $data['main_channel'] : null);
        $contact->setEmail($data['email'] !== '' ? (string) $data['email'] : null);
        $contact->setPhone($data['phone'] !== '' ? (string) $data['phone'] : null);
        $contact->setProfileUrl($data['profile_url'] !== '' ? (string) $data['profile_url'] : null);
        $contact->setSource($data['source'] !== '' ? (string) $data['source'] : null);
        $contact->setPriority($this->priorityFromValue($data['priority']));
        $contact->setRelationshipStatus($this->relationshipStatusFromValue($data['relationship_status']));

        return $contact;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildComparisonFields(Contact $leftContact, Contact $rightContact, array $fieldChoices): array
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
    private function buildDefaultFieldChoices(Contact $leftContact, Contact $rightContact): array
    {
        $choices = [];

        foreach (self::FIELD_DEFINITIONS as $definition) {
            $choices[$definition['name']] = $this->defaultChoiceForField($definition['name'], $leftContact, $rightContact);
        }

        return $choices;
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

        return $this->contactCompletenessScore($rightContact) > $this->contactCompletenessScore($leftContact) ? 'right' : 'left';
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

        return $this->normalizeOptionalString($value) !== null;
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

    private function normalizeFieldChoices(array $fieldChoices, Contact $leftContact, Contact $rightContact): array
    {
        $normalized = [];

        foreach (self::FIELD_DEFINITIONS as $definition) {
            $fieldName = $definition['name'];
            $choice = $this->normalizeChoice($fieldChoices[$fieldName] ?? $this->defaultChoiceForField($fieldName, $leftContact, $rightContact), $definition['mode']);
            $normalized[$fieldName] = $choice;
        }

        return $normalized;
    }

    private function normalizeChoice(string $choice, string $mode): string
    {
        $choice = strtolower(trim($choice));
        $allowed = array_keys($this->choiceOptionsForMode($mode));

        return in_array($choice, $allowed, true) ? $choice : $allowed[0];
    }

    private function normalizeChoiceSide(string $choice): string
    {
        $choice = strtolower(trim($choice));

        return $choice === 'right' ? 'right' : 'left';
    }

    private function applyMergeChoices(Contact $canonical, Contact $source, array $fieldChoices): void
    {
        foreach (self::FIELD_DEFINITIONS as $definition) {
            $fieldName = $definition['name'];
            $choice = $fieldChoices[$fieldName] ?? $this->defaultChoiceForField($fieldName, $canonical, $source);
            $value = $this->resolveFieldValue($fieldName, $choice, $canonical, $source);

            match ($fieldName) {
                'display_name' => $canonical->setDisplayName($this->normalizeDisplayName($value, $canonical, $source)),
                'first_name' => $canonical->setFirstName($this->normalizeOptionalString($value)),
                'last_name' => $canonical->setLastName($this->normalizeOptionalString($value)),
                'organization' => $canonical->setOrganization($this->normalizeOptionalString($value)),
                'role' => $canonical->setRole($this->normalizeOptionalString($value)),
                'main_channel' => $canonical->setMainChannel($this->normalizeOptionalString($value)),
                'email' => $canonical->setEmail($this->normalizeOptionalString($value)),
                'phone' => $canonical->setPhone($this->normalizeOptionalString($value)),
                'profile_url' => $canonical->setProfileUrl($this->normalizeOptionalString($value)),
                'source' => $canonical->setSource($this->normalizeOptionalString($value)),
                'priority' => $canonical->setPriority($this->priorityFromValue($value)),
                'relationship_status' => $canonical->setRelationshipStatus($this->relationshipStatusFromValue($value)),
                'last_contact_at' => $canonical->setLastContactAt($value instanceof DateTimeImmutable ? $value : $this->parseDate($value)),
                'next_action_at' => $canonical->setNextActionAt($value instanceof DateTimeImmutable ? $value : $this->parseDate($value)),
                'next_action' => $canonical->setNextAction($this->normalizeOptionalString($value)),
                'notes' => $canonical->setNotes($this->normalizeNotesValue($value, $canonical, $source, $choice)),
                'tags' => $canonical->setTags($this->normalizeTagsValue($value)),
                default => null,
            };
        }
    }

    private function resolveFieldValue(string $field, string $choice, Contact $leftContact, Contact $rightContact): mixed
    {
        $leftValue = $this->rawFieldValue($leftContact, $field);
        $rightValue = $this->rawFieldValue($rightContact, $field);

        return match ($field) {
            'source' => $choice === 'union' ? $this->mergeSourceValues($leftValue, $rightValue) : ($choice === 'right' ? $rightValue : $leftValue),
            'notes' => $choice === 'union' ? $this->mergeNotesValues($leftContact, $rightContact) : ($choice === 'right' ? $rightValue : $leftValue),
            'tags' => $choice === 'union' ? array_values(array_unique(array_merge($this->normalizeTagsValue($leftValue), $this->normalizeTagsValue($rightValue)))) : ($choice === 'right' ? $rightValue : $leftValue),
            'last_contact_at' => $choice === 'latest' ? $this->pickLatestDateValue($leftValue, $rightValue) : ($choice === 'right' ? $rightValue : $leftValue),
            'next_action_at' => $choice === 'earliest' ? $this->pickEarliestDateValue($leftValue, $rightValue) : ($choice === 'right' ? $rightValue : $leftValue),
            'priority' => $choice === 'highest' ? $this->pickHighestPriorityValue($leftContact->getPriority(), $rightContact->getPriority()) : ($choice === 'right' ? $rightContact->getPriority()->value : $leftContact->getPriority()->value),
            'relationship_status' => $choice === 'highest' ? $this->pickHighestRelationshipValue($leftContact->getRelationshipStatus(), $rightContact->getRelationshipStatus()) : ($choice === 'right' ? $rightContact->getRelationshipStatus()->value : $leftContact->getRelationshipStatus()->value),
            default => $choice === 'right' ? $rightValue : $leftValue,
        };
    }

    private function normalizeDisplayName(mixed $value, Contact $canonical, Contact $source): string
    {
        $value = $this->normalizeOptionalString($value);
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

        return $this->normalizeOptionalString($value);
    }

    /**
     * @return list<string>
     */
    private function normalizeTagsValue(mixed $value): array
    {
        if (is_string($value)) {
            $value = preg_split('/[,\n;]/', $value) ?: [];
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $tag): string => trim((string) $tag),
            $value,
        ), static fn (string $tag): bool => $tag !== '')));
    }

    private function normalizeOptionalString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
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

    private function buildCandidatePair(Contact $left, Contact $right): ?array
    {
        $exactScoreData = $this->computeExactScore($left, $right);
        $reviewScoreData = $this->computeReviewScore($left, $right, $exactScoreData['score']);

        if ($exactScoreData['score'] === 0 && $reviewScoreData['score'] < 40) {
            return null;
        }

        return [
            'score' => $exactScoreData['score'],
            'review_score' => $reviewScoreData['score'],
            'reasons' => array_values(array_unique(array_merge($exactScoreData['reasons'], $reviewScoreData['reasons']))),
        ];
    }

    /**
     * @return array{score: int, reasons: list<string>}
     */
    private function computeExactScore(Contact $left, Contact $right): array
    {
        $score = 0;
        $reasons = [];

        if ($this->normalizePhoneKey($left->getPhone()) !== '' && $this->normalizePhoneKey($left->getPhone()) === $this->normalizePhoneKey($right->getPhone())) {
            $score += 100;
            $reasons[] = 'Téléphone identique';
        }

        if ($this->normalizeComparableText($left->getEmail()) !== '' && $this->normalizeComparableText($left->getEmail()) === $this->normalizeComparableText($right->getEmail())) {
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

        if ($this->contactCompletenessScore($left) < 4 || $this->contactCompletenessScore($right) < 4) {
            $score += 10;
            $reasons[] = 'Fiche partielle';
        }

        return [
            'score' => min(100, $score),
            'reasons' => $reasons,
        ];
    }

    private function contactCompletenessScore(Contact $contact): int
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

    /**
     * @return list<string>
     */
    private function sourceTokens(?string $source): array
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

    private function textSimilarity(?string $left, ?string $right): int
    {
        $left = $this->normalizeComparableText($left);
        $right = $this->normalizeComparableText($right);

        if ($left === '' || $right === '') {
            return 0;
        }

        similar_text($left, $right, $percent);

        return (int) round($percent);
    }

    private function buildFingerprint(string $leftId, string $rightId): string
    {
        $ids = [$leftId, $rightId];
        sort($ids, SORT_STRING);

        return implode('|', $ids);
    }

    private function normalizeComparableText(mixed $value): string
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

    private function normalizePhoneKey(mixed $phone): string
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

    private function normalizeUrlKey(mixed $url): string
    {
        $url = $this->normalizeOptionalString($url);
        if ($url === null) {
            return '';
        }

        $url = mb_strtolower($url);
        $parts = parse_url($url);
        if ($parts === false) {
            return rtrim($url, '/');
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

    private function mergeSourceValues(?string $left, ?string $right): ?string
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

    private function mergeNotesValues(Contact $leftContact, Contact $rightContact): ?string
    {
        $blocks = [];

        $leftNotes = $this->normalizeOptionalString($leftContact->getNotes());
        if ($leftNotes !== null) {
            $blocks[] = sprintf('[%s] %s', $leftContact->getDisplayName(), $leftNotes);
        }

        $rightNotes = $this->normalizeOptionalString($rightContact->getNotes());
        if ($rightNotes !== null) {
            $blocks[] = sprintf('[%s] %s', $rightContact->getDisplayName(), $rightNotes);
        }

        return $blocks === [] ? null : implode("\n\n", $blocks);
    }

    /**
     * @param list<string> $tags
     *
     * @return list<string>
     */
    private function mergeTagLists(array $tags): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $tag): string => trim((string) $tag),
            $tags,
        ), static fn (string $tag): bool => $tag !== '')));
    }

    private function pickLatestDateValue(mixed $left, mixed $right): ?DateTimeImmutable
    {
        if ($left instanceof DateTimeImmutable && $right instanceof DateTimeImmutable) {
            return $right > $left ? $right : $left;
        }

        return $right instanceof DateTimeImmutable ? $right : ($left instanceof DateTimeImmutable ? $left : null);
    }

    private function pickEarliestDateValue(mixed $left, mixed $right): ?DateTimeImmutable
    {
        if ($left instanceof DateTimeImmutable && $right instanceof DateTimeImmutable) {
            return $right < $left ? $right : $left;
        }

        return $right instanceof DateTimeImmutable ? $right : ($left instanceof DateTimeImmutable ? $left : null);
    }

    private function pickHighestPriorityValue(ContactPriority $left, ContactPriority $right): string
    {
        return $this->priorityWeight($right->value) > $this->priorityWeight($left->value) ? $right->value : $left->value;
    }

    private function pickHighestRelationshipValue(ContactRelationshipStatus $left, ContactRelationshipStatus $right): string
    {
        return $this->relationshipWeight($right) > $this->relationshipWeight($left) ? $right->value : $left->value;
    }

    private function priorityFromValue(mixed $priority): ContactPriority
    {
        if ($priority instanceof ContactPriority) {
            return $priority;
        }

        $priority = $this->normalizeOptionalString($priority) ?? ContactPriority::default()->value;

        return ContactPriority::tryFrom($priority) ?? ContactPriority::default();
    }

    private function relationshipStatusFromValue(mixed $status): ContactRelationshipStatus
    {
        if ($status instanceof ContactRelationshipStatus) {
            return $status;
        }

        $status = $this->normalizeOptionalString($status) ?? ContactRelationshipStatus::default()->value;

        return ContactRelationshipStatus::tryFrom($status) ?? ContactRelationshipStatus::default();
    }

    private function relationshipWeight(ContactRelationshipStatus $status): int
    {
        return match ($status) {
            ContactRelationshipStatus::Priority => 5,
            ContactRelationshipStatus::FollowUp => 4,
            ContactRelationshipStatus::InProgress => 3,
            ContactRelationshipStatus::Waiting => 2,
            ContactRelationshipStatus::Cold => 1,
        };
    }

    private function priorityWeight(string $priority): int
    {
        return match ($priority) {
            'haute' => 3,
            'moyenne' => 2,
            'basse' => 1,
            default => 0,
        };
    }

    private function parseDate(mixed $value): ?DateTimeImmutable
    {
        $value = $this->normalizeOptionalString($value);
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

    private function formatDateTime(?DateTimeImmutable $value): ?string
    {
        return $value !== null ? $value->format(DATE_ATOM) : null;
    }

    private function moveInteractions(Contact $target, Contact $source): int
    {
        $movedInteractions = 0;

        foreach ($source->getInteractions()->toArray() as $interaction) {
            if (!$interaction instanceof Interaction) {
                continue;
            }

            $source->removeInteraction($interaction);
            $target->addInteraction($interaction);
            $movedInteractions++;
        }

        return $movedInteractions;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveContactView(ContactMergeReview $review, string $side): array
    {
        $contact = $side === 'right' ? $review->getRightContact() : $review->getLeftContact();

        if ($contact instanceof Contact) {
            return $this->decorateContact($contact);
        }

        $snapshot = $side === 'right' ? $review->getRightSnapshot() : $review->getLeftSnapshot();

        return $this->decorateContactSnapshot($snapshot);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCandidatePairData(Contact $left, Contact $right): array
    {
        return $this->buildCandidatePair($left, $right) ?? [];
    }

    private function normalizeFieldChoicesForStorage(array $choices): array
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

    private function recommendedCanonicalSide(Contact $leftContact, Contact $rightContact): string
    {
        return $this->contactCompletenessScore($rightContact) > $this->contactCompletenessScore($leftContact) ? 'right' : 'left';
    }

    private function sortReviews(array $reviews): array
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

    private function generateId(string $prefix): string
    {
        return sprintf('%s_%s', $prefix, bin2hex(random_bytes(8)));
    }
}

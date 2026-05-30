<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

use App\Entity\Network\Contact;
use App\Entity\Network\ContactMergeReview;
use App\Entity\Network\Interaction;
use App\Enum\Network\ContactMergeReviewStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ContactMergeReviewService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContactService $contactService,
        private readonly ContactMergeReviewScoringService $scoringService,
        private readonly ContactMergeReviewFieldService $fieldService,
        private readonly ContactMergeReviewViewService $viewService,
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
                fn (ContactMergeReview $review): array => $this->viewService->decorateReview($review),
                $this->viewService->sortReviews($pendingReviews),
            ),
        ];
    }

    /**
     * @return array{created: int, updated: int, skipped: int, considered: int, total: int}
     */
    public function refreshCandidates(): array
    {
        $autoMergeSummary = $this->contactService->autoMergeContacts();
        $purgedPendingReviews = $this->purgeOrphanedPendingReviews();
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
                $fingerprint = $this->scoringService->buildFingerprint($left->getId(), $right->getId());
                $existingReview = $reviews[$fingerprint] ?? null;

                $pair = $this->scoringService->buildCandidatePair($left, $right);
                if ($pair === null) {
                    if ($existingReview instanceof ContactMergeReview && $existingReview->getStatus() === ContactMergeReviewStatus::Pending) {
                        $this->entityManager->remove($existingReview);
                        $skipped++;
                    }

                    continue;
                }

                $considered++;

                if ($existingReview instanceof ContactMergeReview) {
                    if ($existingReview->getStatus() !== ContactMergeReviewStatus::Pending) {
                        $skipped++;
                        continue;
                    }

                    $existingReview
                        ->setScore($pair['score'])
                        ->setReviewScore($pair['review_score'])
                        ->setReasons($pair['reasons'])
                        ->setLeftSnapshot($this->viewService->decorateContact($left))
                        ->setRightSnapshot($this->viewService->decorateContact($right))
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
                    ->setLeftSnapshot($this->viewService->decorateContact($left))
                    ->setRightSnapshot($this->viewService->decorateContact($right))
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
            'auto_merged_contacts' => $autoMergeSummary['merged_contacts'],
            'auto_merged_groups' => $autoMergeSummary['merged_groups'],
            'auto_merged_interactions' => $autoMergeSummary['moved_interactions'],
            'purged_pending_reviews' => $purgedPendingReviews['deleted'],
        ];
    }

    /**
     * @return array{deleted: int}
     */
    public function purgePendingReviews(): array
    {
        /** @noinspection SqlNoDataSourceInspection */
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
     * @return array{contacts: int, interactions: int, imports: int, reviews: int}
     */
    public function resetNetworkData(): array
    {
        return $this->contactService->resetNetworkData();
    }

    /**
     * @return array{deleted: int}
     */
    private function purgeOrphanedPendingReviews(): array
    {
        $deleted = 0;

        /** @var list<ContactMergeReview> $pendingReviews */
        $pendingReviews = $this->entityManager->getRepository(ContactMergeReview::class)->findBy([
            'status' => ContactMergeReviewStatus::Pending,
        ]);

        foreach ($pendingReviews as $review) {
            if (!$review instanceof ContactMergeReview) {
                continue;
            }

            if ($review->getLeftContact() instanceof Contact && $review->getRightContact() instanceof Contact) {
                continue;
            }

            $this->entityManager->remove($review);
            $deleted++;
        }

        if ($deleted > 0) {
            $this->entityManager->flush();
        }

        return [
            'deleted' => $deleted,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getReview(string $id): array
    {
        $review = $this->loadReview($id);

        return $this->viewService->decorateReview($review);
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

        $canonicalSide = strtolower(trim($canonicalSide)) === 'right' ? 'right' : 'left';
        $canonical = $canonicalSide === 'right' ? $rightContact : $leftContact;
        $source = $canonicalSide === 'right' ? $leftContact : $rightContact;
        $choices = $this->fieldService->normalizeFieldChoices($fieldChoices, $leftContact, $rightContact);

        $movedInteractions = 0;

        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use (
            $review,
            $canonical,
            $source,
            $choices,
            &$movedInteractions,
        ): void {
            $this->fieldService->applyMergeChoices($canonical, $source, $choices);
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
            'review' => $this->viewService->decorateReview($review),
            'resolved_contact' => $this->viewService->decorateContact($review->getResolvedContact() ?? $canonical),
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

        return $this->viewService->decorateReview($review);
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

    private function generateId(string $prefix): string
    {
        return sprintf('%s_%s', $prefix, bin2hex(random_bytes(8)));
    }
}

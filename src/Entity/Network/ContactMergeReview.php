<?php

declare(strict_types=1);

namespace App\Entity\Network;

use App\Enum\Network\ContactMergeReviewStatus;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'network_contact_merge_reviews')]
#[ORM\UniqueConstraint(name: 'uniq_network_contact_merge_reviews_fingerprint', columns: ['fingerprint'])]
class ContactMergeReview
{
    #[ORM\Id]
    #[ORM\Column(length: 120)]
    private string $id;

    #[ORM\Column(length: 255)]
    private string $fingerprint;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Contact $leftContact = null;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Contact $rightContact = null;

    #[ORM\Column(length: 32, enumType: ContactMergeReviewStatus::class)]
    private ContactMergeReviewStatus $status;

    #[ORM\Column(type: Types::INTEGER)]
    private int $score = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $reviewScore = 0;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $reasons = [];

    /**
     * @var array<string, string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $fieldChoices = [];

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $leftSnapshot = [];

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $rightSnapshot = [];

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Contact $resolvedContact = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $reviewedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $resolvedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $ignoredAt = null;

    public function __construct(string $id, string $fingerprint, Contact $leftContact, Contact $rightContact)
    {
        $this->id = $id;
        $this->fingerprint = $fingerprint;
        $this->leftContact = $leftContact;
        $this->rightContact = $rightContact;
        $this->status = ContactMergeReviewStatus::default();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getFingerprint(): string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(string $fingerprint): self
    {
        $this->fingerprint = $fingerprint;

        return $this;
    }

    public function getLeftContact(): ?Contact
    {
        return $this->leftContact;
    }

    public function setLeftContact(?Contact $leftContact): self
    {
        $this->leftContact = $leftContact;

        return $this;
    }

    public function getRightContact(): ?Contact
    {
        return $this->rightContact;
    }

    public function setRightContact(?Contact $rightContact): self
    {
        $this->rightContact = $rightContact;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getLeftSnapshot(): array
    {
        return $this->leftSnapshot;
    }

    /**
     * @param array<string, mixed> $leftSnapshot
     */
    public function setLeftSnapshot(array $leftSnapshot): self
    {
        $this->leftSnapshot = $leftSnapshot;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRightSnapshot(): array
    {
        return $this->rightSnapshot;
    }

    /**
     * @param array<string, mixed> $rightSnapshot
     */
    public function setRightSnapshot(array $rightSnapshot): self
    {
        $this->rightSnapshot = $rightSnapshot;

        return $this;
    }

    public function getStatus(): ContactMergeReviewStatus
    {
        return $this->status;
    }

    public function setStatus(ContactMergeReviewStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusLabel(): string
    {
        return $this->status->label();
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = max(0, min(100, $score));

        return $this;
    }

    public function getReviewScore(): int
    {
        return $this->reviewScore;
    }

    public function setReviewScore(int $reviewScore): self
    {
        $this->reviewScore = max(0, min(100, $reviewScore));

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getReasons(): array
    {
        return $this->reasons;
    }

    /**
     * @param list<string> $reasons
     */
    public function setReasons(array $reasons): self
    {
        $this->reasons = array_values(array_filter(array_map(
            static fn (mixed $reason): string => trim((string) $reason),
            $reasons,
        ), static fn (string $reason): bool => $reason !== ''));

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getFieldChoices(): array
    {
        return $this->fieldChoices;
    }

    /**
     * @param array<string, string> $fieldChoices
     */
    public function setFieldChoices(array $fieldChoices): self
    {
        $cleaned = [];

        foreach ($fieldChoices as $field => $choice) {
            $field = trim((string) $field);
            $choice = trim((string) $choice);

            if ($field === '' || $choice === '') {
                continue;
            }

            $cleaned[$field] = $choice;
        }

        $this->fieldChoices = $cleaned;

        return $this;
    }

    public function getResolvedContact(): ?Contact
    {
        return $this->resolvedContact;
    }

    public function setResolvedContact(?Contact $resolvedContact): self
    {
        $this->resolvedContact = $resolvedContact;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getReviewedAt(): ?DateTimeImmutable
    {
        return $this->reviewedAt;
    }

    public function setReviewedAt(?DateTimeImmutable $reviewedAt): self
    {
        $this->reviewedAt = $reviewedAt;

        return $this;
    }

    public function getResolvedAt(): ?DateTimeImmutable
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?DateTimeImmutable $resolvedAt): self
    {
        $this->resolvedAt = $resolvedAt;

        return $this;
    }

    public function getIgnoredAt(): ?DateTimeImmutable
    {
        return $this->ignoredAt;
    }

    public function setIgnoredAt(?DateTimeImmutable $ignoredAt): self
    {
        $this->ignoredAt = $ignoredAt;

        return $this;
    }
}

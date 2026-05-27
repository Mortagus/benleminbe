<?php

declare(strict_types=1);

namespace App\Entity\Network;

use App\Enum\Network\PlatformStatus;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'network_platforms')]
class Platform
{
    #[ORM\Id]
    #[ORM\Column(length: 120)]
    private string $slug = '';

    #[ORM\Column(length: 180)]
    private string $name = '';

    #[ORM\Column(length: 80)]
    private string $category = 'reseau';

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $profileUrl = null;

    #[ORM\Column(enumType: PlatformStatus::class, length: 32)]
    private PlatformStatus $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $note = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastReviewedAt = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $active;

    public function __construct()
    {
        $this->status = PlatformStatus::default();
        $this->active = true;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getProfileUrl(): ?string
    {
        return $this->profileUrl;
    }

    public function setProfileUrl(?string $profileUrl): self
    {
        $this->profileUrl = $profileUrl !== null && $profileUrl !== '' ? $profileUrl : null;

        return $this;
    }

    public function getStatus(): PlatformStatus
    {
        return $this->status;
    }

    public function setStatus(PlatformStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusLabel(): string
    {
        return $this->status->label();
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): self
    {
        $this->note = $note !== null && $note !== '' ? $note : null;

        return $this;
    }

    public function getLastReviewedAt(): ?DateTimeImmutable
    {
        return $this->lastReviewedAt;
    }

    public function setLastReviewedAt(?DateTimeImmutable $lastReviewedAt): self
    {
        $this->lastReviewedAt = $lastReviewedAt;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }
}

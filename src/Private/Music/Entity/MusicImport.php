<?php

declare(strict_types=1);

namespace App\Private\Music\Entity;

use App\Private\Music\Enum\MusicImportSourceType;
use App\Private\Music\Enum\MusicImportStatus;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'music_imports')]
#[ORM\UniqueConstraint(name: 'uniq_music_imports_archive_checksum', columns: ['archive_checksum'])]
class MusicImport
{
    #[ORM\Id]
    #[ORM\Column(length: 120)]
    private string $id;

    #[ORM\Column(length: 255)]
    private string $originalFilename;

    #[ORM\Column(length: 80, enumType: MusicImportSourceType::class)]
    private MusicImportSourceType $sourceType;

    #[ORM\Column(length: 64)]
    private string $archiveChecksum;

    #[ORM\Column(length: 32, enumType: MusicImportStatus::class)]
    private MusicImportStatus $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $importedAt;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $summary = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    public function __construct(string $id, string $originalFilename, string $archiveChecksum)
    {
        $this->id = $id;
        $this->originalFilename = $originalFilename;
        $this->archiveChecksum = $archiveChecksum;
        $this->sourceType = MusicImportSourceType::default();
        $this->status = MusicImportStatus::default();
        $this->importedAt = new DateTimeImmutable();
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

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function setOriginalFilename(string $originalFilename): self
    {
        $this->originalFilename = $originalFilename;

        return $this;
    }

    public function getSourceType(): MusicImportSourceType
    {
        return $this->sourceType;
    }

    public function setSourceType(MusicImportSourceType $sourceType): self
    {
        $this->sourceType = $sourceType;

        return $this;
    }

    public function getArchiveChecksum(): string
    {
        return $this->archiveChecksum;
    }

    public function setArchiveChecksum(string $archiveChecksum): self
    {
        $this->archiveChecksum = $archiveChecksum;

        return $this;
    }

    public function getStatus(): MusicImportStatus
    {
        return $this->status;
    }

    public function setStatus(MusicImportStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getImportedAt(): DateTimeImmutable
    {
        return $this->importedAt;
    }

    public function setImportedAt(DateTimeImmutable $importedAt): self
    {
        $this->importedAt = $importedAt;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return $this->summary;
    }

    /**
     * @param array<string, mixed> $summary
     */
    public function setSummary(array $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage !== null && $errorMessage !== '' ? $errorMessage : null;

        return $this;
    }
}

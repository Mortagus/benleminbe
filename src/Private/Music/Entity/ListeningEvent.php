<?php

declare(strict_types=1);

namespace App\Private\Music\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'music_listening_events')]
#[ORM\UniqueConstraint(name: 'uniq_music_listening_events_fingerprint', columns: ['fingerprint'])]
class ListeningEvent
{
    #[ORM\Id]
    #[ORM\Column(length: 120)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: MusicImport::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?MusicImport $import = null;

    #[ORM\ManyToOne(targetEntity: Artist::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Artist $artist = null;

    #[ORM\ManyToOne(targetEntity: Track::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Track $track = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $playedAt;

    #[ORM\Column(length: 255)]
    private string $trackName;

    #[ORM\Column(length: 255)]
    private string $artistNameRaw;

    #[ORM\Column(length: 255)]
    private string $artistNameNormalized;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $albumName = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $playedDurationMs = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trackUri = null;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $sourcePayloadVersion = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sourceFileName = null;

    #[ORM\Column(type: Types::INTEGER)]
    private int $sourceRecordIndex = 0;

    #[ORM\Column(length: 128)]
    private string $fingerprint;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $rawPayload = [];

    public function __construct(
        string $id,
        MusicImport $import,
        Artist $artist,
        Track $track,
        DateTimeImmutable $playedAt,
        string $fingerprint,
    ) {
        $this->id = $id;
        $this->import = $import;
        $this->artist = $artist;
        $this->track = $track;
        $this->playedAt = $playedAt;
        $this->fingerprint = $fingerprint;
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

    public function getImport(): ?MusicImport
    {
        return $this->import;
    }

    public function setImport(?MusicImport $import): self
    {
        $this->import = $import;

        return $this;
    }

    public function getArtist(): ?Artist
    {
        return $this->artist;
    }

    public function setArtist(?Artist $artist): self
    {
        $this->artist = $artist;

        return $this;
    }

    public function getTrack(): ?Track
    {
        return $this->track;
    }

    public function setTrack(?Track $track): self
    {
        $this->track = $track;

        return $this;
    }

    public function getPlayedAt(): DateTimeImmutable
    {
        return $this->playedAt;
    }

    public function setPlayedAt(DateTimeImmutable $playedAt): self
    {
        $this->playedAt = $playedAt;

        return $this;
    }

    public function getTrackName(): string
    {
        return $this->trackName;
    }

    public function setTrackName(string $trackName): self
    {
        $this->trackName = $trackName;

        return $this;
    }

    public function getArtistNameRaw(): string
    {
        return $this->artistNameRaw;
    }

    public function setArtistNameRaw(string $artistNameRaw): self
    {
        $this->artistNameRaw = $artistNameRaw;

        return $this;
    }

    public function getArtistNameNormalized(): string
    {
        return $this->artistNameNormalized;
    }

    public function setArtistNameNormalized(string $artistNameNormalized): self
    {
        $this->artistNameNormalized = $artistNameNormalized;

        return $this;
    }

    public function getAlbumName(): ?string
    {
        return $this->albumName;
    }

    public function setAlbumName(?string $albumName): self
    {
        $this->albumName = $albumName !== null && $albumName !== '' ? $albumName : null;

        return $this;
    }

    public function getPlayedDurationMs(): ?int
    {
        return $this->playedDurationMs;
    }

    public function setPlayedDurationMs(?int $playedDurationMs): self
    {
        $this->playedDurationMs = $playedDurationMs !== null && $playedDurationMs >= 0 ? $playedDurationMs : null;

        return $this;
    }

    public function getTrackUri(): ?string
    {
        return $this->trackUri;
    }

    public function setTrackUri(?string $trackUri): self
    {
        $this->trackUri = $trackUri !== null && $trackUri !== '' ? $trackUri : null;

        return $this;
    }

    public function getSourcePayloadVersion(): ?string
    {
        return $this->sourcePayloadVersion;
    }

    public function setSourcePayloadVersion(?string $sourcePayloadVersion): self
    {
        $this->sourcePayloadVersion = $sourcePayloadVersion !== null && $sourcePayloadVersion !== '' ? $sourcePayloadVersion : null;

        return $this;
    }

    public function getSourceFileName(): ?string
    {
        return $this->sourceFileName;
    }

    public function setSourceFileName(?string $sourceFileName): self
    {
        $this->sourceFileName = $sourceFileName !== null && $sourceFileName !== '' ? $sourceFileName : null;

        return $this;
    }

    public function getSourceRecordIndex(): int
    {
        return $this->sourceRecordIndex;
    }

    public function setSourceRecordIndex(int $sourceRecordIndex): self
    {
        $this->sourceRecordIndex = max(0, $sourceRecordIndex);

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

    /**
     * @return array<string, mixed>
     */
    public function getRawPayload(): array
    {
        return $this->rawPayload;
    }

    /**
     * @param array<string, mixed> $rawPayload
     */
    public function setRawPayload(array $rawPayload): self
    {
        $this->rawPayload = $rawPayload;

        return $this;
    }
}

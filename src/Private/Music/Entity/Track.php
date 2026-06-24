<?php

declare(strict_types=1);

namespace App\Private\Music\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'music_tracks')]
#[ORM\UniqueConstraint(name: 'uniq_music_tracks_artist_title', columns: ['artist_id', 'normalized_title'])]
class Track
{
    #[ORM\Id]
    #[ORM\Column(length: 120)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Artist::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Artist $artist = null;

    #[ORM\Column(length: 255)]
    private string $normalizedTitle;

    #[ORM\Column(length: 255)]
    private string $displayTitle;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $albumName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $spotifyUri = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $firstPlayedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastPlayedAt = null;

    #[ORM\Column]
    private int $listeningCount = 0;

    #[ORM\Column]
    private int $totalPlayedMs = 0;

    public function __construct(string $id, Artist $artist, string $normalizedTitle, string $displayTitle)
    {
        $this->id = $id;
        $this->artist = $artist;
        $this->normalizedTitle = $normalizedTitle;
        $this->displayTitle = $displayTitle;
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

    public function getArtist(): ?Artist
    {
        return $this->artist;
    }

    public function setArtist(?Artist $artist): self
    {
        $this->artist = $artist;

        return $this;
    }

    public function getNormalizedTitle(): string
    {
        return $this->normalizedTitle;
    }

    public function setNormalizedTitle(string $normalizedTitle): self
    {
        $this->normalizedTitle = $normalizedTitle;

        return $this;
    }

    public function getDisplayTitle(): string
    {
        return $this->displayTitle;
    }

    public function setDisplayTitle(string $displayTitle): self
    {
        $this->displayTitle = $displayTitle;

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

    public function getSpotifyUri(): ?string
    {
        return $this->spotifyUri;
    }

    public function setSpotifyUri(?string $spotifyUri): self
    {
        $this->spotifyUri = $spotifyUri !== null && $spotifyUri !== '' ? $spotifyUri : null;

        return $this;
    }

    public function getFirstPlayedAt(): ?DateTimeImmutable
    {
        return $this->firstPlayedAt;
    }

    public function setFirstPlayedAt(?DateTimeImmutable $firstPlayedAt): self
    {
        $this->firstPlayedAt = $firstPlayedAt;

        return $this;
    }

    public function getLastPlayedAt(): ?DateTimeImmutable
    {
        return $this->lastPlayedAt;
    }

    public function setLastPlayedAt(?DateTimeImmutable $lastPlayedAt): self
    {
        $this->lastPlayedAt = $lastPlayedAt;

        return $this;
    }

    public function getListeningCount(): int
    {
        return $this->listeningCount;
    }

    public function setListeningCount(int $listeningCount): self
    {
        $this->listeningCount = max(0, $listeningCount);

        return $this;
    }

    public function incrementListeningCount(int $by = 1): self
    {
        $this->listeningCount = max(0, $this->listeningCount + $by);

        return $this;
    }

    public function getTotalPlayedMs(): int
    {
        return $this->totalPlayedMs;
    }

    public function setTotalPlayedMs(int $totalPlayedMs): self
    {
        $this->totalPlayedMs = max(0, $totalPlayedMs);

        return $this;
    }

    public function addPlayedMs(int $playedMs): self
    {
        $this->totalPlayedMs = max(0, $this->totalPlayedMs + max(0, $playedMs));

        return $this;
    }
}

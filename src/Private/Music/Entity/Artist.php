<?php

declare(strict_types=1);

namespace App\Private\Music\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'music_artists')]
#[ORM\UniqueConstraint(name: 'uniq_music_artists_normalized_name', columns: ['normalized_name'])]
class Artist
{
    #[ORM\Id]
    #[ORM\Column(length: 120)]
    private string $id;

    #[ORM\Column(length: 255)]
    private string $normalizedName;

    #[ORM\Column(length: 255)]
    private string $displayName;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $firstPlayedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastPlayedAt = null;

    #[ORM\Column]
    private int $listeningCount = 0;

    #[ORM\Column]
    private int $totalPlayedMs = 0;

    /**
     * @var Collection<int, ListeningEvent>
     */
    #[ORM\OneToMany(mappedBy: 'artist', targetEntity: ListeningEvent::class)]
    private Collection $events;

    /**
     * @var Collection<int, ArtistGenre>
     */
    #[ORM\OneToMany(mappedBy: 'artist', targetEntity: ArtistGenre::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $artistGenres;

    public function __construct(string $id, string $normalizedName, string $displayName)
    {
        $this->id = $id;
        $this->normalizedName = $normalizedName;
        $this->displayName = $displayName;
        $this->events = new ArrayCollection();
        $this->artistGenres = new ArrayCollection();
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

    public function getNormalizedName(): string
    {
        return $this->normalizedName;
    }

    public function setNormalizedName(string $normalizedName): self
    {
        $this->normalizedName = $normalizedName;

        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;

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

    /**
     * @return Collection<int, ArtistGenre>
     */
    public function getArtistGenres(): Collection
    {
        return $this->artistGenres;
    }

    /**
     * @return list<Genre>
     */
    public function getGenres(): array
    {
        $genres = [];

        foreach ($this->artistGenres as $artistGenre) {
            $genres[] = $artistGenre->getGenre();
        }

        return $genres;
    }

    public function addGenre(Genre $genre): ArtistGenre
    {
        foreach ($this->artistGenres as $artistGenre) {
            if ($artistGenre->getGenre() === $genre) {
                return $artistGenre;
            }
        }

        $artistGenre = new ArtistGenre($this, $genre);
        $this->artistGenres->add($artistGenre);
        $genre->getArtistGenres()->add($artistGenre);

        return $artistGenre;
    }
}

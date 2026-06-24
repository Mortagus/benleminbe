<?php

declare(strict_types=1);

namespace App\Private\Music\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'music_artist_genres')]
#[ORM\UniqueConstraint(name: 'uniq_music_artist_genres_artist_genre', columns: ['artist_id', 'genre_id'])]
class ArtistGenre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Artist::class, inversedBy: 'artistGenres')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Artist $artist;

    #[ORM\ManyToOne(targetEntity: Genre::class, inversedBy: 'artistGenres')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Genre $genre;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    public function __construct(Artist $artist, Genre $genre)
    {
        $this->artist = $artist;
        $this->genre = $genre;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArtist(): Artist
    {
        return $this->artist;
    }

    public function setArtist(Artist $artist): self
    {
        $this->artist = $artist;

        return $this;
    }

    public function getGenre(): Genre
    {
        return $this->genre;
    }

    public function setGenre(Genre $genre): self
    {
        $this->genre = $genre;

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
}

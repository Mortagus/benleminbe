<?php

declare(strict_types=1);

namespace App\Private\Music\Repository;

use App\Private\Music\Entity\Artist;
use App\Private\Music\Entity\Genre;
use App\Private\Music\Entity\ListeningEvent;
use App\Private\Music\Entity\MusicImport;
use App\Private\Music\Entity\Track;
use Doctrine\ORM\EntityManagerInterface;

final class MusicRepository
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findImportByChecksum(string $checksum): ?MusicImport
    {
        $import = $this->entityManager->getRepository(MusicImport::class)->findOneBy(['archiveChecksum' => $checksum]);

        return $import instanceof MusicImport ? $import : null;
    }

    public function findArtistByNormalizedName(string $normalizedName): ?Artist
    {
        $artist = $this->entityManager->getRepository(Artist::class)->findOneBy(['normalizedName' => $normalizedName]);

        return $artist instanceof Artist ? $artist : null;
    }

    public function findTrackByArtistAndNormalizedTitle(Artist $artist, string $normalizedTitle): ?Track
    {
        $track = $this->entityManager->getRepository(Track::class)->findOneBy([
            'artist' => $artist,
            'normalizedTitle' => $normalizedTitle,
        ]);

        return $track instanceof Track ? $track : null;
    }

    public function findGenreBySlug(string $slug): ?Genre
    {
        $genre = $this->entityManager->getRepository(Genre::class)->findOneBy(['slug' => $slug]);

        return $genre instanceof Genre ? $genre : null;
    }

    public function findGenreByName(string $name): ?Genre
    {
        $genre = $this->entityManager->getRepository(Genre::class)->findOneBy(['name' => $name]);

        return $genre instanceof Genre ? $genre : null;
    }

    public function hasListeningEvents(): bool
    {
        return $this->entityManager->getRepository(ListeningEvent::class)->count([]) > 0;
    }
}

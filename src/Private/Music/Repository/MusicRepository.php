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

    public function hasMusicImportHistory(): bool
    {
        return $this->entityManager->getRepository(MusicImport::class)->count([]) > 0;
    }

    /**
     * @return array<string, string>
     */
    public function loadArtistReferenceIndex(): array
    {
        $rows = $this->entityManager->getConnection()->executeQuery('SELECT id, normalized_name FROM music_artists')->fetchAllAssociative();

        $index = [];
        foreach ($rows as $row) {
            $id = (string) ($row['id'] ?? '');
            $normalizedName = (string) ($row['normalized_name'] ?? '');

            if ($id === '' || $normalizedName === '') {
                continue;
            }

            $index[$normalizedName] = $id;
        }

        return $index;
    }

    /**
     * @return array<string, string>
     */
    public function loadTrackReferenceIndex(): array
    {
        $rows = $this->entityManager->getConnection()->executeQuery('SELECT id, artist_id, normalized_title FROM music_tracks')->fetchAllAssociative();

        $index = [];
        foreach ($rows as $row) {
            $id = (string) ($row['id'] ?? '');
            $artistId = (string) ($row['artist_id'] ?? '');
            $normalizedTitle = (string) ($row['normalized_title'] ?? '');

            if ($id === '' || $artistId === '' || $normalizedTitle === '') {
                continue;
            }

            $index[$artistId . '|' . $normalizedTitle] = $id;
        }

        return $index;
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

    /**
     * @return array<string, int>
     */
    public function hardResetMusicData(): array
    {
        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();

        try {
            $deletedRows = [
                'artist_genres' => $connection->executeStatement('DELETE FROM music_artist_genres'),
                'listening_events' => $connection->executeStatement('DELETE FROM music_listening_events'),
                'tracks' => $connection->executeStatement('DELETE FROM music_tracks'),
                'artists' => $connection->executeStatement('DELETE FROM music_artists'),
                'imports' => $connection->executeStatement('DELETE FROM music_imports'),
            ];

            $connection->commit();
            $this->entityManager->clear();

            return $deletedRows;
        } catch (\Throwable $exception) {
            $connection->rollBack();

            throw $exception;
        }
    }
}

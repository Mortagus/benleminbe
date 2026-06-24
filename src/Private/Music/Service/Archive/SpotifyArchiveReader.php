<?php

declare(strict_types=1);

namespace App\Private\Music\Service\Archive;

use App\Private\Music\Dto\SpotifyArchiveImportPlan;
use App\Private\Music\Service\Normalization\MusicNormalizationService;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

final class SpotifyArchiveReader
{
    private const int MAX_ARCHIVE_SIZE_BYTES = 50_000_000;
    private const int MAX_ARCHIVE_FILES = 200;

    public function __construct(
        private readonly MusicNormalizationService $normalizationService,
    ) {
    }

    public function readUploadedArchive(UploadedFile $uploadedFile): SpotifyArchiveImportPlan
    {
        if (!$uploadedFile->isValid()) {
            throw new InvalidArgumentException('Le fichier ZIP importe est invalide.');
        }

        if ($uploadedFile->getSize() !== null && $uploadedFile->getSize() > self::MAX_ARCHIVE_SIZE_BYTES) {
            throw new InvalidArgumentException(sprintf('L archive ZIP depasse la limite autorisee de %d Mo.', (int) (self::MAX_ARCHIVE_SIZE_BYTES / 1024 / 1024)));
        }

        $originalFilename = $uploadedFile->getClientOriginalName() !== '' ? $uploadedFile->getClientOriginalName() : $uploadedFile->getFilename();
        if (strtolower($uploadedFile->getClientOriginalExtension()) !== 'zip') {
            throw new InvalidArgumentException('Le fichier importe doit etre un ZIP Spotify.');
        }

        $archiveChecksum = hash_file('sha256', $uploadedFile->getPathname());
        if ($archiveChecksum === false) {
            throw new InvalidArgumentException('Impossible de calculer le hash de l archive ZIP.');
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($uploadedFile->getPathname());
        if ($openResult !== true) {
            throw new InvalidArgumentException('Le fichier ZIP importe ne peut pas etre ouvert.');
        }

        try {
            if ($zip->numFiles > self::MAX_ARCHIVE_FILES) {
                throw new InvalidArgumentException(sprintf('L archive contient trop de fichiers (%d maximum).', self::MAX_ARCHIVE_FILES));
            }

            $musicFileNames = [];
            $ignoredFiles = [];
            $libraryIndex = [];

            for ($index = 0; $index < $zip->numFiles; ++$index) {
                $stat = $zip->statIndex($index);
                if (!is_array($stat)) {
                    continue;
                }

                $name = (string) ($stat['name'] ?? '');
                if ($name === '' || str_ends_with($name, '/')) {
                    continue;
                }

                if (!$this->isSafeArchivePath($name)) {
                    throw new InvalidArgumentException(sprintf('Le chemin "%s" dans le ZIP est refuse pour raisons de securite.', $name));
                }

                if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'json') {
                    $ignoredFiles[] = $name;
                    continue;
                }

                if (preg_match('#^Spotify Account Data/StreamingHistory_music_(\d+)\.json$#', $name, $matches) === 1) {
                    $musicFileNames[] = $name;
                    continue;
                }

                if ($name === 'Spotify Account Data/YourLibrary.json') {
                    $libraryIndex = $this->readLibraryIndex($zip, $name);
                    continue;
                }

                $ignoredFiles[] = $name;
            }

            if ($musicFileNames === []) {
                throw new InvalidArgumentException('Aucun fichier StreamingHistory_music_*.json exploitable n a ete trouve dans l archive.');
            }

            return new SpotifyArchiveImportPlan(
                $this->normalizationService,
                $uploadedFile->getPathname(),
                $archiveChecksum,
                $originalFilename,
                'spotify_archive',
                $zip->numFiles,
                array_values(array_unique($musicFileNames)),
                array_values(array_unique($ignoredFiles)),
                $libraryIndex,
            );
        } finally {
            $zip->close();
        }
    }

    private function isSafeArchivePath(string $path): bool
    {
        if ($path === '' || str_contains($path, "\0")) {
            return false;
        }

        if (str_starts_with($path, '/') || preg_match('#^[A-Za-z]:#', $path) === 1) {
            return false;
        }

        if (str_contains($path, '../') || str_contains($path, '..\\')) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, array{album_name: string|null, track_uri: string|null}>
     */
    private function readLibraryIndex(ZipArchive $zip, string $name): array
    {
        $contents = $zip->getFromName($name);
        if ($contents === false) {
            throw new InvalidArgumentException(sprintf('Le fichier "%s" est illisible dans le ZIP.', $name));
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException(sprintf('Le fichier "%s" contient un JSON invalide.', $name), previous: $exception);
        }

        if (!is_array($decoded) || !isset($decoded['tracks']) || !is_array($decoded['tracks'])) {
            return [];
        }

        $index = [];
        $albumsByKey = [];
        $urisByKey = [];

        foreach ($decoded['tracks'] as $row) {
            if (!is_array($row)) {
                continue;
            }

            $artist = $this->normalizationService->normalizeText($row['artist'] ?? null);
            $track = $this->normalizationService->normalizeText($row['track'] ?? null);
            if ($artist === '' || $track === '') {
                continue;
            }

            $key = $this->normalizationService->buildTrackKey($artist, $track);
            $album = $this->normalizationService->normalizeText($row['album'] ?? null);
            $uri = $this->normalizationService->normalizeText($row['uri'] ?? null);

            if ($album !== '') {
                $albumsByKey[$key][(string) $album] = true;
            }

            if ($uri !== '') {
                $urisByKey[$key][(string) $uri] = true;
            }
        }

        foreach (array_keys($albumsByKey + $urisByKey) as $key) {
            $albums = array_map(static fn ($album): string => (string) $album, array_keys($albumsByKey[$key] ?? []));
            $uris = array_map(static fn ($uri): string => (string) $uri, array_keys($urisByKey[$key] ?? []));

            $index[$key] = [
                'album_name' => count($albums) === 1 ? $albums[0] : null,
                'track_uri' => count($uris) === 1 ? $uris[0] : null,
            ];
        }

        return $index;
    }
}

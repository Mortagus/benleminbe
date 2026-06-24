<?php

declare(strict_types=1);

namespace App\Private\Music\Service\Archive;

use App\Private\Music\Service\Normalization\MusicNormalizationService;
use DateTimeImmutable;
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

    /**
     * @return array{
     *     archive_checksum: string,
     *     original_filename: string,
     *     source_type: string,
     *     file_count: int,
     *     music_files: list<array<string, mixed>>,
     *     ignored_files: list<string>,
     *     music_events: list<array<string, mixed>>,
     *     library_index: array<string, array{album_name: string|null, track_uri: string|null}>,
     *     total_entries: int,
     *     ignored_entries: int,
     *     error_entries: int,
     *     period_start: DateTimeImmutable|null,
     *     period_end: DateTimeImmutable|null
     * }
     */
    public function readUploadedArchive(UploadedFile $uploadedFile): array
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

            $musicFiles = [];
            $musicEvents = [];
            $ignoredFiles = [];
            $libraryIndex = [];
            $periodStart = null;
            $periodEnd = null;
            $ignoredEntries = 0;
            $errorEntries = 0;

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
                    $fileReport = $this->readStreamingHistoryFile($zip, $name, $archiveChecksum, $periodStart, $periodEnd, $ignoredEntries, $errorEntries);
                    $musicFiles[] = $fileReport['report'];
                    $musicEvents = array_merge($musicEvents, $fileReport['events']);
                    $periodStart = $fileReport['period_start'];
                    $periodEnd = $fileReport['period_end'];
                    $ignoredEntries = $fileReport['ignored_entries'];
                    $errorEntries = $fileReport['error_entries'];
                    continue;
                }

                if ($name === 'Spotify Account Data/YourLibrary.json') {
                    $libraryIndex = $this->readLibraryIndex($zip, $name);
                    continue;
                }

                $ignoredFiles[] = $name;
            }

            if ($musicEvents === []) {
                throw new InvalidArgumentException('Aucun fichier StreamingHistory_music_*.json exploitable n a ete trouve dans l archive.');
            }

            usort($musicEvents, static function (array $left, array $right): int {
                return $left['played_at'] <=> $right['played_at'];
            });

            return [
                'archive_checksum' => $archiveChecksum,
                'original_filename' => $originalFilename,
                'source_type' => 'spotify_archive',
                'file_count' => $zip->numFiles,
                'music_files' => $musicFiles,
                'ignored_files' => array_values(array_unique($ignoredFiles)),
                'music_events' => $musicEvents,
                'library_index' => $libraryIndex,
                'total_entries' => count($musicEvents),
                'ignored_entries' => $ignoredEntries,
                'error_entries' => $errorEntries,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ];
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
     * @return array{
     *     report: array<string, mixed>,
     *     events: list<array<string, mixed>>,
     *     period_start: DateTimeImmutable|null,
     *     period_end: DateTimeImmutable|null,
     *     ignored_entries: int,
     *     error_entries: int
     * }
     */
    private function readStreamingHistoryFile(
        ZipArchive $zip,
        string $name,
        string $archiveChecksum,
        ?DateTimeImmutable $periodStart,
        ?DateTimeImmutable $periodEnd,
        int $ignoredEntries,
        int $errorEntries,
    ): array {
        $contents = $zip->getFromName($name);
        if ($contents === false) {
            throw new InvalidArgumentException(sprintf('Le fichier "%s" est illisible dans le ZIP.', $name));
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException(sprintf('Le fichier "%s" contient un JSON invalide.', $name), previous: $exception);
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException(sprintf('Le fichier "%s" ne contient pas une liste d ecoutes.', $name));
        }

        $events = [];
        $recordIndex = 0;
        $fileIgnoredEntries = 0;
        $fileErrorEntries = 0;
        $fileStart = null;
        $fileEnd = null;

        foreach ($decoded as $row) {
            ++$recordIndex;
            if (!is_array($row)) {
                ++$fileIgnoredEntries;
                continue;
            }

            $artistName = $this->normalizationService->normalizeText($row['artistName'] ?? null);
            $trackName = $this->normalizationService->normalizeText($row['trackName'] ?? null);
            $endTime = $this->normalizationService->normalizeText($row['endTime'] ?? null);
            $playedDurationMs = isset($row['msPlayed']) && is_numeric($row['msPlayed']) ? max(0, (int) $row['msPlayed']) : null;

            if ($artistName === '' || $trackName === '' || $endTime === '') {
                ++$fileErrorEntries;
                continue;
            }

            try {
                $playedAt = $this->normalizationService->parseArchiveDateTime($endTime);
            } catch (InvalidArgumentException) {
                ++$fileErrorEntries;
                continue;
            }

            $fileStart = $fileStart === null || $playedAt < $fileStart ? $playedAt : $fileStart;
            $fileEnd = $fileEnd === null || $playedAt > $fileEnd ? $playedAt : $fileEnd;

            $rawPayload = is_array($row) ? $row : [];
            $events[] = [
                'archive_checksum' => $archiveChecksum,
                'source_file_name' => $name,
                'source_payload_version' => 'spotify_streaming_history_v1',
                'source_record_index' => $recordIndex,
                'played_at' => $playedAt,
                'artist_name_raw' => $artistName,
                'artist_name_normalized' => $this->normalizationService->buildArtistKey($artistName),
                'track_name' => $trackName,
                'track_name_normalized' => $this->normalizationService->normalizeKey($trackName),
                'played_duration_ms' => $playedDurationMs,
                'album_name' => null,
                'track_uri' => null,
                'raw_payload' => $rawPayload,
                'fingerprint' => hash('sha256', implode('|', [
                    $archiveChecksum,
                    $name,
                    (string) $recordIndex,
                ])),
            ];
        }

        return [
            'report' => [
                'file_name' => $name,
                'record_count' => count($decoded),
                'processed_entries' => count($events),
                'ignored_entries' => $fileIgnoredEntries,
                'error_entries' => $fileErrorEntries,
                'period_start' => $fileStart,
                'period_end' => $fileEnd,
            ],
            'events' => $events,
            'period_start' => $periodStart === null || ($fileStart !== null && $fileStart < $periodStart) ? $fileStart : $periodStart,
            'period_end' => $periodEnd === null || ($fileEnd !== null && $fileEnd > $periodEnd) ? $fileEnd : $periodEnd,
            'ignored_entries' => $ignoredEntries + $fileIgnoredEntries,
            'error_entries' => $errorEntries + $fileErrorEntries,
        ];
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

<?php

declare(strict_types=1);

namespace App\Private\Music\Dto;

use App\Private\Music\Service\Normalization\MusicNormalizationService;
use DateTimeImmutable;
use InvalidArgumentException;
use ZipArchive;

final class SpotifyArchiveImportPlan
{
    /**
     * @param list<string> $musicFileNames
     * @param list<string> $ignoredFiles
     * @param array<string, array{album_name: string|null, track_uri: string|null}> $libraryIndex
     */
    public function __construct(
        private readonly MusicNormalizationService $normalizationService,
        private readonly string $archivePath,
        private readonly string $archiveChecksum,
        private readonly string $originalFilename,
        private readonly string $sourceType,
        private readonly int $fileCount,
        private readonly array $musicFileNames,
        private readonly array $ignoredFiles,
        private readonly array $libraryIndex,
    ) {
    }

    /**
     * @var list<SpotifyArchiveFileReport>
     */
    private array $musicFileReports = [];

    private int $totalEntries = 0;

    private int $ignoredEntries = 0;

    private int $errorEntries = 0;

    private ?DateTimeImmutable $periodStart = null;

    private ?DateTimeImmutable $periodEnd = null;

    private bool $summaryCollected = false;

    public function getArchiveChecksum(): string
    {
        return $this->archiveChecksum;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function getSourceType(): string
    {
        return $this->sourceType;
    }

    public function getFileCount(): int
    {
        return $this->fileCount;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getMusicFiles(): array
    {
        return array_map(static fn (SpotifyArchiveFileReport $report): array => $report->toArray(), $this->musicFileReports);
    }

    /**
     * @return list<string>
     */
    public function getIgnoredFiles(): array
    {
        return $this->ignoredFiles;
    }

    /**
     * @return array<string, array{album_name: string|null, track_uri: string|null}>
     */
    public function getLibraryIndex(): array
    {
        return $this->libraryIndex;
    }

    public function getTotalEntries(): int
    {
        return $this->totalEntries;
    }

    public function getIgnoredEntries(): int
    {
        return $this->ignoredEntries;
    }

    public function getErrorEntries(): int
    {
        return $this->errorEntries;
    }

    public function getPeriodStart(): ?DateTimeImmutable
    {
        return $this->periodStart;
    }

    public function getPeriodEnd(): ?DateTimeImmutable
    {
        return $this->periodEnd;
    }

    /**
     * @return iterable<SpotifyListeningEventRow>
     */
    public function iterateEvents(): iterable
    {
        $zip = new ZipArchive();
        $openResult = $zip->open($this->archivePath);
        if ($openResult !== true) {
            throw new InvalidArgumentException('Le fichier ZIP importe ne peut pas etre ouvert.');
        }

        try {
            foreach ($this->musicFileNames as $fileName) {
                yield from $this->iterateMusicFileRows($zip, $fileName);
            }
        } finally {
            $zip->close();
        }
    }

    public function collectSummary(): void
    {
        $this->musicFileReports = [];
        $this->totalEntries = 0;
        $this->ignoredEntries = 0;
        $this->errorEntries = 0;
        $this->periodStart = null;
        $this->periodEnd = null;

        $zip = new ZipArchive();
        $openResult = $zip->open($this->archivePath);
        if ($openResult !== true) {
            throw new InvalidArgumentException('Le fichier ZIP importe ne peut pas etre ouvert.');
        }

        try {
            foreach ($this->musicFileNames as $fileName) {
                $this->scanMusicFileSummary($zip, $fileName);
            }
        } finally {
            $zip->close();
        }

        $this->summaryCollected = true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toSummary(bool $duplicate = false): array
    {
        if (!$this->summaryCollected) {
            $this->collectSummary();
        }

        return [
            'duplicate' => $duplicate,
            'archive_checksum' => $this->archiveChecksum,
            'original_filename' => $this->originalFilename,
            'source_type' => $this->sourceType,
            'file_count' => $this->fileCount,
            'music_files' => $this->getMusicFiles(),
            'ignored_files' => $this->ignoredFiles,
            'processed_entries' => $this->totalEntries,
            'ignored_entries' => $this->ignoredEntries,
            'error_entries' => $this->errorEntries,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
        ];
    }

    /**
     * @return iterable<SpotifyListeningEventRow>
     */
    private function iterateMusicFileRows(ZipArchive $zip, string $fileName): iterable
    {
        $contents = $zip->getFromName($fileName);
        if ($contents === false) {
            throw new InvalidArgumentException(sprintf('Le fichier "%s" est illisible dans le ZIP.', $fileName));
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException(sprintf('Le fichier "%s" contient un JSON invalide.', $fileName), previous: $exception);
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException(sprintf('Le fichier "%s" ne contient pas une liste d ecoutes.', $fileName));
        }

        $recordIndex = 0;

        foreach ($decoded as $row) {
            ++$recordIndex;
            if (!is_array($row)) {
                continue;
            }

            $artistName = $this->normalizationService->normalizeText($row['artistName'] ?? null);
            $trackName = $this->normalizationService->normalizeText($row['trackName'] ?? null);
            $endTime = $this->normalizationService->normalizeText($row['endTime'] ?? null);
            $playedDurationMs = isset($row['msPlayed']) && is_numeric($row['msPlayed']) ? max(0, (int) $row['msPlayed']) : null;

            if ($artistName === '' || $trackName === '' || $endTime === '') {
                continue;
            }

            try {
                $playedAt = $this->normalizationService->parseArchiveDateTime($endTime);
            } catch (InvalidArgumentException) {
                continue;
            }

            $artistNameNormalized = $this->normalizationService->buildArtistKey($artistName);
            $trackNameNormalized = $this->normalizationService->normalizeKey($trackName);
            $libraryMetadata = $this->libraryIndex[$artistNameNormalized . '|' . $trackNameNormalized] ?? [
                'album_name' => null,
                'track_uri' => null,
            ];

            yield new SpotifyListeningEventRow(
                playedAt: $playedAt,
                artistNameRaw: $artistName,
                artistNameNormalized: $artistNameNormalized,
                trackName: $trackName,
                trackNameNormalized: $trackNameNormalized,
                playedDurationMs: $playedDurationMs,
                albumName: $this->normalizeOptionalText($libraryMetadata['album_name'] ?? null),
                trackUri: $this->normalizeOptionalText($libraryMetadata['track_uri'] ?? null),
                sourcePayloadVersion: 'spotify_streaming_history_v1',
                sourceFileName: $fileName,
                sourceRecordIndex: $recordIndex,
                fingerprint: hash('sha256', implode('|', [
                    $this->archiveChecksum,
                    $fileName,
                    (string) $recordIndex,
                ])),
                rawPayload: $row,
            );
        }
    }

    private function scanMusicFileSummary(ZipArchive $zip, string $fileName): void
    {
        $contents = $zip->getFromName($fileName);
        if ($contents === false) {
            throw new InvalidArgumentException(sprintf('Le fichier "%s" est illisible dans le ZIP.', $fileName));
        }

        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException(sprintf('Le fichier "%s" contient un JSON invalide.', $fileName), previous: $exception);
        }

        if (!is_array($decoded)) {
            throw new InvalidArgumentException(sprintf('Le fichier "%s" ne contient pas une liste d ecoutes.', $fileName));
        }

        $recordCount = count($decoded);
        $processedEntries = 0;
        $ignoredEntries = 0;
        $errorEntries = 0;
        $fileStart = null;
        $fileEnd = null;
        $recordIndex = 0;

        foreach ($decoded as $row) {
            ++$recordIndex;
            if (!is_array($row)) {
                ++$ignoredEntries;
                continue;
            }

            $artistName = $this->normalizationService->normalizeText($row['artistName'] ?? null);
            $trackName = $this->normalizationService->normalizeText($row['trackName'] ?? null);
            $endTime = $this->normalizationService->normalizeText($row['endTime'] ?? null);

            if ($artistName === '' || $trackName === '' || $endTime === '') {
                ++$errorEntries;
                continue;
            }

            try {
                $playedAt = $this->normalizationService->parseArchiveDateTime($endTime);
            } catch (InvalidArgumentException) {
                ++$errorEntries;
                continue;
            }

            $fileStart = $fileStart === null || $playedAt < $fileStart ? $playedAt : $fileStart;
            $fileEnd = $fileEnd === null || $playedAt > $fileEnd ? $playedAt : $fileEnd;
            ++$processedEntries;
            ++$this->totalEntries;
        }

        $this->ignoredEntries += $ignoredEntries;
        $this->errorEntries += $errorEntries;
        $this->periodStart = $this->periodStart === null || ($fileStart !== null && $fileStart < $this->periodStart) ? $fileStart : $this->periodStart;
        $this->periodEnd = $this->periodEnd === null || ($fileEnd !== null && $fileEnd > $this->periodEnd) ? $fileEnd : $this->periodEnd;
        $this->musicFileReports[] = new SpotifyArchiveFileReport($fileName, $recordCount, $processedEntries, $ignoredEntries, $errorEntries, $fileStart, $fileEnd);
    }

    private function normalizeOptionalText(mixed $value): ?string
    {
        $normalized = $this->normalizationService->normalizeText($value);

        return $normalized === '' ? null : $normalized;
    }
}

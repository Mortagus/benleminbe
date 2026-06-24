<?php

declare(strict_types=1);

namespace App\Private\Music\Service\Import;

use App\Private\Music\Dto\MusicImportBatch;
use App\Private\Music\Dto\SpotifyArchiveInspection;
use App\Private\Music\Service\Normalization\MusicNormalizationService;
use DateTimeImmutable;

final class MusicImportMetrics
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $fileReports = [];

    /**
     * @var list<string>
     */
    private array $musicFileNames = [];

    /**
     * @var list<string>
     */
    private array $podcastFileNames = [];

    /**
     * @var list<string>
     */
    private array $ignoredFiles = [];

    private string $archiveChecksum = '';

    private string $originalFilename = '';

    private int $fileCount = 0;

    private string $yourLibraryStatus = 'absent';

    private int $batchCount = 0;

    private int $batchSize = 100;

    private int $linesRead = 0;

    private int $validLines = 0;

    private int $ignoredLines = 0;

    private int $errorLines = 0;

    private int $artistsCreated = 0;

    private int $tracksCreated = 0;

    private int $listeningEventsCreated = 0;

    private int $listeningEventsAlreadyPresent = 0;

    private int $totalDurationMs = 0;

    private ?DateTimeImmutable $periodStart = null;

    private ?DateTimeImmutable $periodEnd = null;

    private int $memoryCurrentUsageBytes = 0;

    private int $memoryPeakUsageBytes = 0;

    private ?DateTimeImmutable $startedAt = null;

    private ?DateTimeImmutable $finishedAt = null;

    private float $startedAtMicrotime = 0.0;

    private float $finishedAtMicrotime = 0.0;

    public function registerInspection(SpotifyArchiveInspection $inspection): void
    {
        $this->archiveChecksum = $inspection->getArchiveChecksum();
        $this->originalFilename = $inspection->getOriginalFilename();
        $this->fileCount = $inspection->getFileCount();
        $this->musicFileNames = $inspection->getMusicFileNames();
        $this->podcastFileNames = $inspection->getPodcastFileNames();
        $this->ignoredFiles = $inspection->getIgnoredFiles();
        $this->yourLibraryStatus = $inspection->getYourLibraryStatus();
    }

    public function setBatchSize(int $batchSize): void
    {
        $this->batchSize = max(1, $batchSize);
    }

    public function start(): void
    {
        $this->startedAt = new DateTimeImmutable();
        $this->startedAtMicrotime = microtime(true);
        $this->observeMemory();
    }

    public function startMusicFile(string $fileName): void
    {
        $this->fileReports[$fileName] ??= [
            'file_name' => $fileName,
            'record_count' => 0,
            'lines_read' => 0,
            'valid_lines' => 0,
            'processed_entries' => 0,
            'ignored_entries' => 0,
            'error_entries' => 0,
            'created_entries' => 0,
            'already_present_entries' => 0,
            'duration_total_ms' => 0,
            'period_start' => null,
            'period_end' => null,
        ];
    }

    public function recordLineRead(string $fileName): void
    {
        $this->startMusicFile($fileName);
        ++$this->linesRead;
        ++$this->fileReports[$fileName]['record_count'];
        ++$this->fileReports[$fileName]['lines_read'];
    }

    public function recordValidLine(string $fileName, DateTimeImmutable $playedAt, ?int $playedDurationMs): void
    {
        $this->startMusicFile($fileName);
        ++$this->validLines;
        ++$this->fileReports[$fileName]['valid_lines'];
        $this->totalDurationMs += max(0, $playedDurationMs ?? 0);
        $this->fileReports[$fileName]['duration_total_ms'] += max(0, $playedDurationMs ?? 0);

        $this->periodStart = $this->periodStart === null || $playedAt < $this->periodStart ? $playedAt : $this->periodStart;
        $this->periodEnd = $this->periodEnd === null || $playedAt > $this->periodEnd ? $playedAt : $this->periodEnd;
        $this->fileReports[$fileName]['period_start'] = $this->fileReports[$fileName]['period_start'] === null || $playedAt < $this->fileReports[$fileName]['period_start']
            ? $playedAt
            : $this->fileReports[$fileName]['period_start'];
        $this->fileReports[$fileName]['period_end'] = $this->fileReports[$fileName]['period_end'] === null || $playedAt > $this->fileReports[$fileName]['period_end']
            ? $playedAt
            : $this->fileReports[$fileName]['period_end'];
    }

    public function recordIgnoredLine(string $fileName): void
    {
        $this->startMusicFile($fileName);
        ++$this->ignoredLines;
        ++$this->fileReports[$fileName]['ignored_entries'];
    }

    public function recordErrorLine(string $fileName): void
    {
        $this->startMusicFile($fileName);
        ++$this->errorLines;
        ++$this->fileReports[$fileName]['error_entries'];
    }

    public function recordBatchCommitted(MusicImportBatch $batch, int $createdArtists, int $createdTracks, int $createdEvents): void
    {
        $this->startMusicFile($batch->getSourceFileName());
        ++$this->batchCount;
        $this->artistsCreated += $createdArtists;
        $this->tracksCreated += $createdTracks;
        $this->listeningEventsCreated += $createdEvents;
        $this->fileReports[$batch->getSourceFileName()]['created_entries'] += $createdEvents;
        $this->fileReports[$batch->getSourceFileName()]['processed_entries'] += $createdEvents;
        $this->observeMemory();
    }

    public function recordAlreadyPresentEvents(int $count): void
    {
        $this->listeningEventsAlreadyPresent += max(0, $count);
    }

    public function observeMemory(): void
    {
        $this->memoryCurrentUsageBytes = memory_get_usage(true);
        $this->memoryPeakUsageBytes = max($this->memoryPeakUsageBytes, memory_get_peak_usage(true), $this->memoryCurrentUsageBytes);
    }

    public function finish(): void
    {
        $this->finishedAt = new DateTimeImmutable();
        $this->finishedAtMicrotime = microtime(true);
        $this->observeMemory();
    }

    /**
     * @return array<string, mixed>
     */
    public function toSummary(MusicNormalizationService $normalizationService, bool $duplicate = false): array
    {
        $musicFiles = [];

        foreach ($this->musicFileNames as $fileName) {
            $report = $this->fileReports[$fileName] ?? [
                'file_name' => $fileName,
                'record_count' => 0,
                'lines_read' => 0,
                'valid_lines' => 0,
                'processed_entries' => 0,
                'ignored_entries' => 0,
                'error_entries' => 0,
                'created_entries' => 0,
                'already_present_entries' => 0,
                'duration_total_ms' => 0,
                'period_start' => null,
                'period_end' => null,
            ];

            if ($report['period_start'] instanceof DateTimeImmutable) {
                $report['period_start'] = $report['period_start']->format(DATE_ATOM);
            }

            if ($report['period_end'] instanceof DateTimeImmutable) {
                $report['period_end'] = $report['period_end']->format(DATE_ATOM);
            }

            $musicFiles[] = $report;
        }

        return [
            'duplicate' => $duplicate,
            'archive_checksum' => $this->archiveChecksum,
            'original_filename' => $this->originalFilename,
            'file_count' => $this->fileCount,
            'source_type' => 'spotify_archive',
            'source_type_label' => 'Archive Spotify',
            'batch_size' => $this->batchSize,
            'batch_count' => $this->batchCount,
            'music_files_detected' => count($this->musicFileNames),
            'podcast_files_ignored_count' => count($this->podcastFileNames),
            'music_files' => $musicFiles,
            'podcast_files_ignored' => $this->podcastFileNames,
            'ignored_files' => $this->ignoredFiles,
            'your_library_status' => $this->yourLibraryStatus,
            'lines_read' => $this->linesRead,
            'valid_lines' => $this->validLines,
            'ignored_lines' => $this->ignoredLines,
            'error_lines' => $this->errorLines,
            'artists_created' => $this->artistsCreated,
            'tracks_created' => $this->tracksCreated,
            'listening_events_created' => $this->listeningEventsCreated,
            'listening_events_already_present' => $this->listeningEventsAlreadyPresent,
            'processed_entries' => $this->listeningEventsCreated,
            'ignored_entries' => $this->ignoredLines,
            'error_entries' => $this->errorLines,
            'period_start' => $this->periodStart instanceof DateTimeImmutable ? $this->periodStart->format(DATE_ATOM) : null,
            'period_end' => $this->periodEnd instanceof DateTimeImmutable ? $this->periodEnd->format(DATE_ATOM) : null,
            'duration_total_ms' => $this->totalDurationMs,
            'duration_total_label' => $normalizationService->formatDurationLabel($this->totalDurationMs),
            'import_started_at' => $this->startedAt instanceof DateTimeImmutable ? $this->startedAt->format(DATE_ATOM) : null,
            'import_finished_at' => $this->finishedAt instanceof DateTimeImmutable ? $this->finishedAt->format(DATE_ATOM) : null,
            'import_duration_ms' => $this->startedAtMicrotime > 0.0 && $this->finishedAtMicrotime >= $this->startedAtMicrotime
                ? (int) max(0, round(($this->finishedAtMicrotime - $this->startedAtMicrotime) * 1000))
                : null,
            'memory_current_usage_bytes' => $this->memoryCurrentUsageBytes,
            'memory_peak_usage_bytes' => $this->memoryPeakUsageBytes,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Private\Music\Service\Import;

use App\Private\Music\Entity\MusicImport;
use App\Private\Music\Enum\MusicImportSourceType;
use App\Private\Music\Repository\MusicRepository;
use App\Private\Music\Service\Archive\SpotifyArchiveInspector;
use App\Private\Music\Service\Normalization\MusicNormalizationService;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MusicImportService
{
    public function __construct(
        private readonly MusicRepository $musicRepository,
        private readonly SpotifyArchiveInspector $archiveInspector,
        private readonly MusicImportPipeline $musicImportPipeline,
        private readonly MusicNormalizationService $normalizationService,
    ) {
    }

    /**
     * @return array{import: array<string, mixed>, summary: array<string, mixed>}
     */
    public function importSpotifyArchive(UploadedFile $uploadedFile): array
    {
        $inspection = $this->archiveInspector->inspectUploadedArchive($uploadedFile);

        $existingImport = $this->musicRepository->findImportByChecksum($inspection->getArchiveChecksum());
        if ($existingImport instanceof MusicImport) {
            return [
                'import' => $this->decorateImport($existingImport),
                'summary' => $this->decorateSummary($existingImport->getSummary(), true),
            ];
        }

        $this->musicImportPipeline->import($inspection);

        $import = $this->musicRepository->findImportByChecksum($inspection->getArchiveChecksum());
        if (!$import instanceof MusicImport) {
            throw new InvalidArgumentException('L import Spotify a echoue avant la creation du resume final.');
        }

        return [
            'import' => $this->decorateImport($import),
            'summary' => $this->decorateSummary($import->getSummary(), false),
        ];
    }

    /**
     * @return array{has_import_history: bool}
     */
    public function getImportPageContext(): array
    {
        return [
            'has_import_history' => $this->musicRepository->hasMusicImportHistory(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function hardResetMusicData(): array
    {
        return $this->musicRepository->hardResetMusicData();
    }

    /**
     * @return array<string, mixed>
     */
    private function decorateImport(MusicImport $import): array
    {
        return [
            'id' => $import->getId(),
            'original_filename' => $import->getOriginalFilename(),
            'source_type' => $import->getSourceType()->value,
            'source_type_label' => $import->getSourceType()->label(),
            'archive_checksum' => $import->getArchiveChecksum(),
            'status' => $import->getStatus()->value,
            'status_label' => $import->getStatus()->label(),
            'imported_at' => $import->getImportedAt(),
            'summary' => $import->getSummary(),
            'error_message' => $import->getErrorMessage(),
        ];
    }

    /**
     * @param array<string, mixed> $summary
     *
     * @return array<string, mixed>
     */
    private function decorateSummary(array $summary, bool $duplicate): array
    {
        $defaults = [
            'duplicate' => $duplicate,
            'archive_checksum' => '',
            'original_filename' => '',
            'file_count' => 0,
            'source_type' => MusicImportSourceType::SpotifyArchive->value,
            'source_type_label' => MusicImportSourceType::SpotifyArchive->label(),
            'batch_size' => 0,
            'batch_count' => 0,
            'music_files_detected' => 0,
            'podcast_files_ignored_count' => 0,
            'music_files' => [],
            'podcast_files_ignored' => [],
            'ignored_files' => [],
            'your_library_status' => 'absent',
            'lines_read' => 0,
            'valid_lines' => 0,
            'ignored_lines' => 0,
            'error_lines' => 0,
            'artists_created' => 0,
            'tracks_created' => 0,
            'listening_events_created' => 0,
            'listening_events_already_present' => 0,
            'processed_entries' => 0,
            'ignored_entries' => 0,
            'error_entries' => 0,
            'period_start' => null,
            'period_end' => null,
            'duration_total_ms' => 0,
            'duration_total_label' => '—',
            'import_started_at' => null,
            'import_finished_at' => null,
            'import_duration_ms' => null,
            'memory_current_usage_bytes' => 0,
            'memory_peak_usage_bytes' => 0,
        ];

        $summary = array_replace($defaults, $summary);
        $summary['duplicate'] = $duplicate;
        if (!is_string($summary['duration_total_label']) || $summary['duration_total_label'] === '' || $summary['duration_total_label'] === '—') {
            $summary['duration_total_label'] = $this->normalizationService->formatDurationLabel($summary['duration_total_ms'] ?? null);
        }

        return $summary;
    }
}

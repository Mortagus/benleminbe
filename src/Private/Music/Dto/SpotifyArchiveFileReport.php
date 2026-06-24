<?php

declare(strict_types=1);

namespace App\Private\Music\Dto;

use DateTimeImmutable;

final class SpotifyArchiveFileReport
{
    public function __construct(
        public readonly string $fileName,
        public readonly int $recordCount,
        public readonly int $processedEntries,
        public readonly int $ignoredEntries,
        public readonly int $errorEntries,
        public readonly ?DateTimeImmutable $periodStart,
        public readonly ?DateTimeImmutable $periodEnd,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'file_name' => $this->fileName,
            'record_count' => $this->recordCount,
            'processed_entries' => $this->processedEntries,
            'ignored_entries' => $this->ignoredEntries,
            'error_entries' => $this->errorEntries,
            'period_start' => $this->periodStart,
            'period_end' => $this->periodEnd,
        ];
    }
}

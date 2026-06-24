<?php

declare(strict_types=1);

namespace App\Private\Music\Dto;

use DateTimeImmutable;

final class ParsedListeningEvent
{
    public function __construct(
        public readonly string $sourceFileName,
        public readonly int $sourceOffset,
        public readonly DateTimeImmutable $playedAt,
        public readonly string $artistDisplayName,
        public readonly string $normalizedArtistName,
        public readonly string $trackDisplayName,
        public readonly string $normalizedTrackName,
        public readonly ?int $playedDurationMs,
        public readonly string $sourceFingerprint,
    ) {
    }

    public function getArtistKey(): string
    {
        return $this->normalizedArtistName;
    }

    public function getTrackKey(): string
    {
        return $this->normalizedArtistName . '|' . $this->normalizedTrackName;
    }
}

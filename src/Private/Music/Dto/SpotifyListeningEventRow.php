<?php

declare(strict_types=1);

namespace App\Private\Music\Dto;

use DateTimeImmutable;

final class SpotifyListeningEventRow
{
    /**
     * @param array<string, mixed> $rawPayload
     */
    public function __construct(
        public readonly DateTimeImmutable $playedAt,
        public readonly string $artistNameRaw,
        public readonly string $artistNameNormalized,
        public readonly string $trackName,
        public readonly string $trackNameNormalized,
        public readonly ?int $playedDurationMs,
        public readonly ?string $albumName,
        public readonly ?string $trackUri,
        public readonly string $sourcePayloadVersion,
        public readonly string $sourceFileName,
        public readonly int $sourceRecordIndex,
        public readonly string $fingerprint,
        public readonly array $rawPayload,
    ) {
    }
}

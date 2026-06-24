<?php

declare(strict_types=1);

namespace App\Private\Music\Service\Import;

use App\Private\Music\Repository\MusicRepository;

final class MusicReferenceIndex
{
    /**
     * @var array<string, string>
     */
    private array $artistIdsByNormalizedName = [];

    /**
     * @var array<string, string>
     */
    private array $trackIdsByKey = [];

    public function __construct(
        private readonly MusicRepository $musicRepository,
    ) {
    }

    public function prime(): void
    {
        $this->artistIdsByNormalizedName = $this->musicRepository->loadArtistReferenceIndex();
        $this->trackIdsByKey = $this->musicRepository->loadTrackReferenceIndex();
    }

    public function getArtistId(string $normalizedName): ?string
    {
        $artistId = $this->artistIdsByNormalizedName[$normalizedName] ?? null;

        return is_string($artistId) && $artistId !== '' ? $artistId : null;
    }

    public function registerArtist(string $normalizedName, string $artistId): void
    {
        $this->artistIdsByNormalizedName[$normalizedName] = $artistId;
    }

    public function getTrackId(string $artistId, string $normalizedTitle): ?string
    {
        $trackId = $this->trackIdsByKey[$this->buildTrackKey($artistId, $normalizedTitle)] ?? null;

        return is_string($trackId) && $trackId !== '' ? $trackId : null;
    }

    public function registerTrack(string $artistId, string $normalizedTitle, string $trackId): void
    {
        $this->trackIdsByKey[$this->buildTrackKey($artistId, $normalizedTitle)] = $trackId;
    }

    private function buildTrackKey(string $artistId, string $normalizedTitle): string
    {
        return $artistId . '|' . $normalizedTitle;
    }
}

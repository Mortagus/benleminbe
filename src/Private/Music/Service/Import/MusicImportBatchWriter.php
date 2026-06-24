<?php

declare(strict_types=1);

namespace App\Private\Music\Service\Import;

use App\Private\Music\Dto\MusicImportBatch;
use App\Private\Music\Dto\ParsedListeningEvent;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;

final class MusicImportBatchWriter
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @param array<string, array{album_name: string|null, track_uri: string|null}> $libraryIndex
     */
    public function writeBatch(
        MusicImportBatch $batch,
        string $importId,
        MusicReferenceIndex $referenceIndex,
        array $libraryIndex,
        MusicImportMetrics $metrics,
    ): void {
        if ($batch->isEmpty()) {
            return;
        }

        $localArtistsByNormalizedName = [];
        $localTracksByKey = [];
        $artistStatsById = [];
        $trackStatsById = [];
        $newArtistIds = [];
        $newTrackIds = [];
        $eventRows = [];

        foreach ($batch->getEvents() as $event) {
            $artistId = $this->resolveArtistId(
                $event,
                $referenceIndex,
                $localArtistsByNormalizedName,
                $newArtistIds,
            );

            $trackId = $this->resolveTrackId(
                $event,
                $artistId,
                $referenceIndex,
                $localTracksByKey,
                $newTrackIds,
            );

            $libraryMetadata = $libraryIndex[$event->getTrackKey()] ?? [
                'album_name' => null,
                'track_uri' => null,
            ];

            $artistStatsById[$artistId] = $this->accumulateArtistStats(
                $artistStatsById[$artistId] ?? null,
                $event,
                $event->artistDisplayName,
            );

            $trackStatsById[$trackId] = $this->accumulateTrackStats(
                $trackStatsById[$trackId] ?? null,
                $event,
                $artistId,
                $event->trackDisplayName,
                is_array($libraryMetadata) ? $libraryMetadata : [],
            );

            $eventRows[] = [
                'id' => $this->generateId('music_event'),
                'import_id' => $importId,
                'artist_id' => $artistId,
                'track_id' => $trackId,
                'played_at' => $this->formatDateTime($event->playedAt),
                'track_name' => $event->trackDisplayName,
                'artist_name_raw' => $event->artistDisplayName,
                'artist_name_normalized' => $event->normalizedArtistName,
                'album_name' => $this->normalizeOptionalValue($libraryMetadata['album_name'] ?? null),
                'played_duration_ms' => $event->playedDurationMs,
                'track_uri' => $this->normalizeOptionalValue($libraryMetadata['track_uri'] ?? null),
                'source_payload_version' => 'spotify_streaming_history_v2',
                'source_file_name' => $event->sourceFileName,
                'source_record_index' => $event->sourceOffset + 1,
                'fingerprint' => $event->sourceFingerprint,
                'raw_payload' => json_encode([
                    'endTime' => $event->playedAt->format('Y-m-d H:i'),
                    'artistName' => $event->artistDisplayName,
                    'trackName' => $event->trackDisplayName,
                    'msPlayed' => $event->playedDurationMs,
                ], JSON_THROW_ON_ERROR),
            ];
        }

        $this->insertRows('music_artists', $this->buildArtistInsertRows($artistStatsById, $newArtistIds));

        foreach ($artistStatsById as $artistId => $stats) {
            if (isset($newArtistIds[$artistId])) {
                continue;
            }

            $this->updateArtist($artistId, $stats);
        }

        $this->insertRows('music_tracks', $this->buildTrackInsertRows($trackStatsById, $newTrackIds));

        foreach ($trackStatsById as $trackId => $stats) {
            if (isset($newTrackIds[$trackId])) {
                continue;
            }

            $this->updateTrack($trackId, $stats);
        }

        $this->insertRows('music_listening_events', $eventRows);

        foreach (array_keys($newArtistIds) as $artistId) {
            $referenceIndex->registerArtist(
                (string) $artistStatsById[$artistId]['normalized_name'],
                $artistId,
            );
        }

        foreach (array_keys($newTrackIds) as $trackId) {
            $artistId = (string) $trackStatsById[$trackId]['artist_id'];
            $referenceIndex->registerTrack(
                $artistId,
                (string) $trackStatsById[$trackId]['normalized_title'],
                $trackId,
            );
        }

        $metrics->recordBatchCommitted($batch, count($newArtistIds), count($newTrackIds), count($eventRows));
    }

    /**
     * @param array<string, string> $localArtistsByNormalizedName
     * @param array<string, true> $newArtistIds
     */
    private function resolveArtistId(
        ParsedListeningEvent $event,
        MusicReferenceIndex $referenceIndex,
        array &$localArtistsByNormalizedName,
        array &$newArtistIds,
    ): string {
        if (isset($localArtistsByNormalizedName[$event->normalizedArtistName])) {
            return $localArtistsByNormalizedName[$event->normalizedArtistName];
        }

        $artistId = $referenceIndex->getArtistId($event->normalizedArtistName);
        if ($artistId !== null) {
            $localArtistsByNormalizedName[$event->normalizedArtistName] = $artistId;

            return $artistId;
        }

        $artistId = $this->generateId('music_artist');
        $localArtistsByNormalizedName[$event->normalizedArtistName] = $artistId;
        $newArtistIds[$artistId] = true;

        return $artistId;
    }

    /**
     * @param array<string, string> $localTracksByKey
     * @param array<string, true> $newTrackIds
     */
    private function resolveTrackId(
        ParsedListeningEvent $event,
        string $artistId,
        MusicReferenceIndex $referenceIndex,
        array &$localTracksByKey,
        array &$newTrackIds,
    ): string {
        $key = $artistId . '|' . $event->normalizedTrackName;
        if (isset($localTracksByKey[$key])) {
            return $localTracksByKey[$key];
        }

        $trackId = $referenceIndex->getTrackId($artistId, $event->normalizedTrackName);
        if ($trackId !== null) {
            $localTracksByKey[$key] = $trackId;

            return $trackId;
        }

        $trackId = $this->generateId('music_track');
        $localTracksByKey[$key] = $trackId;
        $newTrackIds[$trackId] = true;

        return $trackId;
    }

    /**
     * @param array{
     *     normalized_name: string,
     *     display_name: string,
     *     first_played_at: DateTimeImmutable,
     *     last_played_at: DateTimeImmutable,
     *     listening_count: int,
     *     total_played_ms: int
     * }|null $current
     *
     * @return array{
     *     normalized_name: string,
     *     display_name: string,
     *     first_played_at: DateTimeImmutable,
     *     last_played_at: DateTimeImmutable,
     *     listening_count: int,
     *     total_played_ms: int
     * }
     */
    private function accumulateArtistStats(?array $current, ParsedListeningEvent $event, string $displayName): array
    {
        $playedMs = max(0, $event->playedDurationMs ?? 0);

        if ($current === null) {
            return [
                'normalized_name' => $event->normalizedArtistName,
                'display_name' => $displayName,
                'first_played_at' => $event->playedAt,
                'last_played_at' => $event->playedAt,
                'listening_count' => 1,
                'total_played_ms' => $playedMs,
            ];
        }

        $current['first_played_at'] = $event->playedAt < $current['first_played_at'] ? $event->playedAt : $current['first_played_at'];
        $current['last_played_at'] = $event->playedAt > $current['last_played_at'] ? $event->playedAt : $current['last_played_at'];
        $current['listening_count'] = (int) $current['listening_count'] + 1;
        $current['total_played_ms'] = (int) $current['total_played_ms'] + $playedMs;

        return $current;
    }

    /**
     * @param array<string, mixed>|null $libraryMetadata
     *
     * @return array{
     *     artist_id: string,
     *     normalized_title: string,
     *     display_title: string,
     *     album_name: string|null,
     *     spotify_uri: string|null,
     *     first_played_at: DateTimeImmutable,
     *     last_played_at: DateTimeImmutable,
     *     listening_count: int,
     *     total_played_ms: int
     * }
     */
    private function accumulateTrackStats(
        ?array $current,
        ParsedListeningEvent $event,
        string $artistId,
        string $displayTitle,
        array $libraryMetadata,
    ): array {
        $playedMs = max(0, $event->playedDurationMs ?? 0);
        $albumName = $this->normalizeOptionalValue($libraryMetadata['album_name'] ?? null);
        $spotifyUri = $this->normalizeOptionalValue($libraryMetadata['track_uri'] ?? null);

        if ($current === null) {
            return [
                'artist_id' => $artistId,
                'normalized_title' => $event->normalizedTrackName,
                'display_title' => $displayTitle,
                'album_name' => $albumName,
                'spotify_uri' => $spotifyUri,
                'first_played_at' => $event->playedAt,
                'last_played_at' => $event->playedAt,
                'listening_count' => 1,
                'total_played_ms' => $playedMs,
            ];
        }

        if ($current['album_name'] === null && $albumName !== null) {
            $current['album_name'] = $albumName;
        }

        if ($current['spotify_uri'] === null && $spotifyUri !== null) {
            $current['spotify_uri'] = $spotifyUri;
        }

        $current['first_played_at'] = $event->playedAt < $current['first_played_at'] ? $event->playedAt : $current['first_played_at'];
        $current['last_played_at'] = $event->playedAt > $current['last_played_at'] ? $event->playedAt : $current['last_played_at'];
        $current['listening_count'] = (int) $current['listening_count'] + 1;
        $current['total_played_ms'] = (int) $current['total_played_ms'] + $playedMs;

        return $current;
    }

    /**
     * @param array<string, array{
     *     normalized_name: string,
     *     display_name: string,
     *     first_played_at: DateTimeImmutable,
     *     last_played_at: DateTimeImmutable,
     *     listening_count: int,
     *     total_played_ms: int
     * }> $artistStatsById
     * @param array<string, true> $newArtistIds
     *
     * @return list<array<string, mixed>>
     */
    private function buildArtistInsertRows(array $artistStatsById, array $newArtistIds): array
    {
        $rows = [];

        foreach (array_keys($newArtistIds) as $artistId) {
            $stats = $artistStatsById[$artistId];
            $rows[] = [
                'id' => $artistId,
                'normalized_name' => $stats['normalized_name'],
                'display_name' => $stats['display_name'],
                'first_played_at' => $this->formatDateTime($stats['first_played_at']),
                'last_played_at' => $this->formatDateTime($stats['last_played_at']),
                'listening_count' => $stats['listening_count'],
                'total_played_ms' => $stats['total_played_ms'],
            ];
        }

        return $rows;
    }

    /**
     * @param array<string, array{
     *     artist_id: string,
     *     normalized_title: string,
     *     display_title: string,
     *     album_name: string|null,
     *     spotify_uri: string|null,
     *     first_played_at: DateTimeImmutable,
     *     last_played_at: DateTimeImmutable,
     *     listening_count: int,
     *     total_played_ms: int
     * }> $trackStatsById
     * @param array<string, true> $newTrackIds
     *
     * @return list<array<string, mixed>>
     */
    private function buildTrackInsertRows(array $trackStatsById, array $newTrackIds): array
    {
        $rows = [];

        foreach (array_keys($newTrackIds) as $trackId) {
            $stats = $trackStatsById[$trackId];
            $rows[] = [
                'id' => $trackId,
                'artist_id' => $stats['artist_id'],
                'normalized_title' => $stats['normalized_title'],
                'display_title' => $stats['display_title'],
                'album_name' => $stats['album_name'],
                'spotify_uri' => $stats['spotify_uri'],
                'first_played_at' => $this->formatDateTime($stats['first_played_at']),
                'last_played_at' => $this->formatDateTime($stats['last_played_at']),
                'listening_count' => $stats['listening_count'],
                'total_played_ms' => $stats['total_played_ms'],
            ];
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function insertRows(string $table, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $columns = array_keys($rows[0]);
        $placeholders = [];
        $params = [];

        foreach ($rows as $row) {
            $rowPlaceholders = [];
            foreach ($columns as $column) {
                $rowPlaceholders[] = '?';
                $params[] = $row[$column];
            }

            $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES %s',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders),
        );

        $this->connection->executeStatement($sql, $params);
    }

    /**
     * @param array{
     *     normalized_name: string,
     *     display_name: string,
     *     first_played_at: DateTimeImmutable,
     *     last_played_at: DateTimeImmutable,
     *     listening_count: int,
     *     total_played_ms: int
     * } $stats
     */
    private function updateArtist(string $artistId, array $stats): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
UPDATE music_artists
SET listening_count = listening_count + ?,
    total_played_ms = total_played_ms + ?,
    first_played_at = CASE
        WHEN first_played_at IS NULL OR ? < first_played_at THEN ?
        ELSE first_played_at
    END,
    last_played_at = CASE
        WHEN last_played_at IS NULL OR ? > last_played_at THEN ?
        ELSE last_played_at
    END
WHERE id = ?
SQL,
            [
                $stats['listening_count'],
                $stats['total_played_ms'],
                $this->formatDateTime($stats['first_played_at']),
                $this->formatDateTime($stats['first_played_at']),
                $this->formatDateTime($stats['last_played_at']),
                $this->formatDateTime($stats['last_played_at']),
                $artistId,
            ],
        );
    }

    /**
     * @param array{
     *     artist_id: string,
     *     normalized_title: string,
     *     display_title: string,
     *     album_name: string|null,
     *     spotify_uri: string|null,
     *     first_played_at: DateTimeImmutable,
     *     last_played_at: DateTimeImmutable,
     *     listening_count: int,
     *     total_played_ms: int
     * } $stats
     */
    private function updateTrack(string $trackId, array $stats): void
    {
        $this->connection->executeStatement(
            <<<'SQL'
UPDATE music_tracks
SET listening_count = listening_count + ?,
    total_played_ms = total_played_ms + ?,
    first_played_at = CASE
        WHEN first_played_at IS NULL OR ? < first_played_at THEN ?
        ELSE first_played_at
    END,
    last_played_at = CASE
        WHEN last_played_at IS NULL OR ? > last_played_at THEN ?
        ELSE last_played_at
    END,
    album_name = COALESCE(album_name, ?),
    spotify_uri = COALESCE(spotify_uri, ?)
WHERE id = ?
SQL,
            [
                $stats['listening_count'],
                $stats['total_played_ms'],
                $this->formatDateTime($stats['first_played_at']),
                $this->formatDateTime($stats['first_played_at']),
                $this->formatDateTime($stats['last_played_at']),
                $this->formatDateTime($stats['last_played_at']),
                $stats['album_name'],
                $stats['spotify_uri'],
                $trackId,
            ],
        );
    }

    private function generateId(string $prefix): string
    {
        return sprintf('%s_%s', $prefix, bin2hex(random_bytes(8)));
    }

    private function formatDateTime(DateTimeImmutable $dateTime): string
    {
        return $dateTime->format('Y-m-d H:i:s');
    }

    private function normalizeOptionalValue(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}

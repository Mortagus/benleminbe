<?php

declare(strict_types=1);

namespace App\Tests\Unit\Private\Music\Service\Import;

use App\Private\Music\Dto\MusicImportBatch;
use App\Private\Music\Dto\ParsedListeningEvent;
use App\Private\Music\Dto\SpotifyArchiveInspection;
use App\Private\Music\Repository\MusicRepository;
use App\Private\Music\Service\Import\MusicImportBatchWriter;
use App\Private\Music\Service\Import\MusicImportMetrics;
use App\Private\Music\Service\Import\MusicReferenceIndex;
use App\Private\Music\Service\Normalization\MusicNormalizationService;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class MusicImportBatchWriterTest extends TestCase
{
    public function testItPersistsANewArtistTrackAndListeningEventsThroughDbal(): void
    {
        $connection = $this->createMock(Connection::class);
        $referenceIndex = new MusicReferenceIndex(new MusicRepository($this->createMock(EntityManagerInterface::class)));
        $writer = new MusicImportBatchWriter($connection);
        $metrics = $this->createMetrics();

        $executeCalls = [];
        $connection->expects(self::exactly(3))
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql, array $params = [], array $types = []) use (&$executeCalls): int {
                $executeCalls[] = [
                    'sql' => $sql,
                    'params' => $params,
                    'types' => $types,
                ];

                return 1;
            });

        for ($index = 0; $index < 3; ++$index) {
            $metrics->recordLineRead('Spotify Account Data/StreamingHistory_music_0.json');
            $metrics->recordValidLine('Spotify Account Data/StreamingHistory_music_0.json', new DateTimeImmutable('2026-06-06 19:52'), 120000);
        }

        $writer->writeBatch(
            new MusicImportBatch(
                'Spotify Account Data/StreamingHistory_music_0.json',
                1,
                [
                    $this->makeRow('Alpha Artist', 'First Light', 0),
                    $this->makeRow('Alpha Artist', 'Second Light', 1),
                    $this->makeRow('Beta Artist', 'Third Light', 2),
                ],
            ),
            'import_test',
            $referenceIndex,
            [],
            $metrics,
        );

        $summary = $metrics->toSummary(new MusicNormalizationService());

        self::assertSame(3, $summary['listening_events_created']);
        self::assertSame(2, $summary['artists_created']);
        self::assertSame(3, $summary['tracks_created']);
        self::assertSame(360000, $summary['duration_total_ms']);
        self::assertCount(3, $executeCalls);
        self::assertStringContainsString('INSERT INTO music_artists', $executeCalls[0]['sql']);
        self::assertStringContainsString('INSERT INTO music_tracks', $executeCalls[1]['sql']);
        self::assertStringContainsString('INSERT INTO music_listening_events', $executeCalls[2]['sql']);
        self::assertSame(14, count($executeCalls[0]['params']));
        self::assertSame(30, count($executeCalls[1]['params']));
        self::assertSame(48, count($executeCalls[2]['params']));
        self::assertSame(1, substr_count($executeCalls[0]['sql'], '), ('));
        self::assertSame(2, substr_count($executeCalls[1]['sql'], '), ('));
        self::assertSame(2, substr_count($executeCalls[2]['sql'], '), ('));
        $firstArtistId = $executeCalls[0]['params'][0];
        $secondArtistId = $executeCalls[0]['params'][7];
        $firstTrackId = $executeCalls[1]['params'][0];
        $secondTrackId = $executeCalls[1]['params'][10];
        $thirdTrackId = $executeCalls[1]['params'][20];

        self::assertSame($firstArtistId, $referenceIndex->getArtistId('alpha artist'));
        self::assertSame($secondArtistId, $referenceIndex->getArtistId('beta artist'));
        self::assertSame($firstTrackId, $referenceIndex->getTrackId($firstArtistId, 'first light'));
        self::assertSame($secondTrackId, $referenceIndex->getTrackId($firstArtistId, 'second light'));
        self::assertSame($thirdTrackId, $referenceIndex->getTrackId($secondArtistId, 'third light'));
    }

    public function testItUpdatesExistingReferencesWithoutCreatingNewEntities(): void
    {
        $connection = $this->createMock(Connection::class);
        $referenceIndex = new MusicReferenceIndex(new MusicRepository($this->createMock(EntityManagerInterface::class)));
        $writer = new MusicImportBatchWriter($connection);
        $metrics = $this->createMetrics();

        $referenceIndex->registerArtist('alpha artist', 'artist_existing');
        $referenceIndex->registerTrack('artist_existing', 'first light', 'track_existing');

        $updateCalls = [];
        $connection->expects(self::exactly(3))
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql, array $params = [], array $types = []) use (&$updateCalls): int {
                $updateCalls[] = [
                    'sql' => $sql,
                    'params' => $params,
                    'types' => $types,
                ];

                return 1;
            });

        $writer->writeBatch(
            new MusicImportBatch(
                'Spotify Account Data/StreamingHistory_music_0.json',
                1,
                [
                    new ParsedListeningEvent(
                        sourceFileName: 'Spotify Account Data/StreamingHistory_music_0.json',
                        sourceOffset: 0,
                        playedAt: new DateTimeImmutable('2026-06-06 19:52'),
                        artistDisplayName: 'Alpha Artist',
                        normalizedArtistName: 'alpha artist',
                        trackDisplayName: 'First Light',
                        normalizedTrackName: 'first light',
                        playedDurationMs: 90000,
                        sourceFingerprint: 'fingerprint-existing',
                    ),
                ],
            ),
            'import_existing',
            $referenceIndex,
            [
                'alpha artist|first light' => [
                    'album_name' => 'Album One',
                    'track_uri' => 'spotify:track:123',
                ],
            ],
            $metrics,
        );

        $summary = $metrics->toSummary(new MusicNormalizationService());

        self::assertSame(1, $summary['listening_events_created']);
        self::assertSame(0, $summary['artists_created']);
        self::assertSame(0, $summary['tracks_created']);
        self::assertCount(3, $updateCalls);
        self::assertStringContainsString('UPDATE music_artists', $updateCalls[0]['sql']);
        self::assertStringContainsString('UPDATE music_tracks', $updateCalls[1]['sql']);
        self::assertStringContainsString('INSERT INTO music_listening_events', $updateCalls[2]['sql']);
        self::assertSame('artist_existing', $updateCalls[0]['params'][6]);
        self::assertSame('track_existing', $updateCalls[1]['params'][8]);
        self::assertSame('Album One', $updateCalls[1]['params'][6]);
        self::assertSame('spotify:track:123', $updateCalls[1]['params'][7]);
    }

    private function createMetrics(): MusicImportMetrics
    {
        $metrics = new MusicImportMetrics();
        $metrics->registerInspection(new SpotifyArchiveInspection(
            '/tmp/demo.zip',
            'checksum',
            'spotify.zip',
            1,
            ['Spotify Account Data/StreamingHistory_music_0.json'],
            [],
            [],
            'absent',
            [],
        ));
        $metrics->start();
        $metrics->startMusicFile('Spotify Account Data/StreamingHistory_music_0.json');

        return $metrics;
    }

    private function makeRow(string $artistName, string $trackName, int $recordIndex, int $playedDurationMs = 120000): ParsedListeningEvent
    {
        return new ParsedListeningEvent(
            sourceFileName: 'Spotify Account Data/StreamingHistory_music_0.json',
            sourceOffset: $recordIndex,
            playedAt: new DateTimeImmutable('2026-06-06 19:52'),
            artistDisplayName: $artistName,
            normalizedArtistName: mb_strtolower($artistName),
            trackDisplayName: $trackName,
            normalizedTrackName: mb_strtolower($trackName),
            playedDurationMs: $playedDurationMs,
            sourceFingerprint: sprintf('fingerprint-%d', $recordIndex),
        );
    }
}

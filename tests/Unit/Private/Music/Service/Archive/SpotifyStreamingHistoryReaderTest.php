<?php

declare(strict_types=1);

namespace App\Tests\Unit\Private\Music\Service\Archive;

use App\Private\Music\Dto\SpotifyArchiveInspection;
use App\Private\Music\Service\Archive\SpotifyArchiveStreamFactory;
use App\Private\Music\Service\Archive\SpotifyStreamingHistoryReader;
use App\Private\Music\Service\Import\MusicImportMetrics;
use App\Private\Music\Service\Normalization\MusicNormalizationService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ZipArchive;

final class SpotifyStreamingHistoryReaderTest extends TestCase
{
    public function testItReadsStreamingHistoryIncrementallyFromZipStream(): void
    {
        $reader = new SpotifyStreamingHistoryReader(
            new MusicNormalizationService(),
            new SpotifyArchiveStreamFactory(),
        );
        $rows = [];
        for ($index = 0; $index < 350; ++$index) {
            $rows[] = [
                'endTime' => sprintf('2026-06-06 19:%02d', $index % 60),
                'artistName' => 'Demo Artist',
                'trackName' => 'Demo Track ' . $index,
                'msPlayed' => 120000,
            ];
        }
        $archivePath = $this->createArchive([
            'Spotify Account Data/StreamingHistory_music_0.json' => json_encode($rows, JSON_THROW_ON_ERROR),
        ]);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($archivePath));

        try {
            $inspection = new SpotifyArchiveInspection(
                $archivePath,
                'checksum',
                'demo.zip',
                1,
                ['Spotify Account Data/StreamingHistory_music_0.json'],
                [],
                [],
                'absent',
                [],
            );
            $metrics = new MusicImportMetrics();
            $metrics->registerInspection($inspection);
            $metrics->start();
            $metrics->startMusicFile('Spotify Account Data/StreamingHistory_music_0.json');

            $events = iterator_to_array(
                $reader->iterateListeningEvents($zip, 'Spotify Account Data/StreamingHistory_music_0.json', $metrics),
                false,
            );

            self::assertCount(350, $events);
            self::assertSame(0, $events[0]->sourceOffset);
            self::assertSame(349, $events[349]->sourceOffset);
            self::assertSame('demo artist', $events[0]->normalizedArtistName);
            self::assertSame('demo track 349', $events[349]->normalizedTrackName);
            self::assertNotSame($events[0]->sourceFingerprint, $events[349]->sourceFingerprint);

            $summary = $metrics->toSummary(new MusicNormalizationService());
            self::assertSame(350, $summary['lines_read']);
            self::assertSame(350, $summary['valid_lines']);
            self::assertSame(0, $summary['ignored_lines']);
            self::assertSame(0, $summary['error_lines']);
        } finally {
            $zip->close();
            @unlink($archivePath);
        }
    }

    public function testItThrowsOnInvalidJsonPayload(): void
    {
        $reader = new SpotifyStreamingHistoryReader(
            new MusicNormalizationService(),
            new SpotifyArchiveStreamFactory(),
        );
        $archivePath = $this->createArchive([
            'Spotify Account Data/StreamingHistory_music_0.json' => '{"broken": true',
        ]);

        $zip = new ZipArchive();
        self::assertTrue($zip->open($archivePath));

        try {
            $inspection = new SpotifyArchiveInspection(
                $archivePath,
                'checksum',
                'demo.zip',
                1,
                ['Spotify Account Data/StreamingHistory_music_0.json'],
                [],
                [],
                'absent',
                [],
            );
            $metrics = new MusicImportMetrics();
            $metrics->registerInspection($inspection);
            $metrics->startMusicFile('Spotify Account Data/StreamingHistory_music_0.json');

            $this->expectException(InvalidArgumentException::class);
            iterator_to_array($reader->iterateListeningEvents($zip, 'Spotify Account Data/StreamingHistory_music_0.json', $metrics), false);
        } finally {
            $zip->close();
            @unlink($archivePath);
        }
    }

    /**
     * @param array<string, string> $entries
     */
    private function createArchive(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'spotify-archive-');
        self::assertNotFalse($path);

        unlink($path);
        $zipPath = $path . '.zip';

        $zip = new ZipArchive();
        self::assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();

        return $zipPath;
    }
}

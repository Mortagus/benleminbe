<?php

declare(strict_types=1);

namespace App\Tests\Unit\Private\Music\Service\Archive;

use App\Private\Music\Service\Archive\SpotifyArchiveInspector;
use App\Private\Music\Service\Archive\SpotifyArchiveStreamFactory;
use App\Private\Music\Service\Normalization\MusicNormalizationService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

final class SpotifyArchiveInspectorTest extends TestCase
{
    public function testItDetectsMultipleMusicFilesAndIgnoresPodcastAndOtherFiles(): void
    {
        $inspector = $this->createInspector();
        $archivePath = $this->createZipFromDirectory(self::fixturesDirectory());

        try {
            $inspection = $inspector->inspectUploadedArchive(new UploadedFile($archivePath, 'demo.zip', 'application/zip', null, true));

            self::assertSame(4, $inspection->getFileCount());
            self::assertCount(2, $inspection->getMusicFileNames());
            self::assertCount(1, $inspection->getPodcastFileNames());
            self::assertSame('available', $inspection->getYourLibraryStatus());
            self::assertSame('Sunrise Sessions', $inspection->getLibraryIndex()['alpha artist|first light']['album_name']);
            self::assertSame('spotify:track:alpha1', $inspection->getLibraryIndex()['alpha artist|first light']['track_uri']);
            self::assertContains('Spotify Account Data/StreamingHistory_podcast_0.json', $inspection->getIgnoredFiles());
        } finally {
            @unlink($archivePath);
        }
    }

    public function testItNormalizesNumericAlbumNamesFromYourLibrary(): void
    {
        $inspector = $this->createInspector();
        $archivePath = $this->createArchive([
            'Spotify Account Data/StreamingHistory_music_0.json' => json_encode([
                [
                    'endTime' => '2026-06-06 19:52',
                    'artistName' => 'Demo Artist',
                    'trackName' => 'Demo Track',
                    'msPlayed' => 120000,
                ],
            ], JSON_THROW_ON_ERROR),
            'Spotify Account Data/YourLibrary.json' => json_encode([
                'tracks' => [
                    [
                        'artist' => 'Demo Artist',
                        'album' => 1989,
                        'track' => 'Demo Track',
                        'uri' => 'spotify:track:demo-track',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        try {
            $inspection = $inspector->inspectUploadedArchive(new UploadedFile($archivePath, 'demo.zip', 'application/zip', null, true));

            self::assertSame('available', $inspection->getYourLibraryStatus());
            self::assertSame('1989', $inspection->getLibraryIndex()['demo artist|demo track']['album_name']);
            self::assertSame('spotify:track:demo-track', $inspection->getLibraryIndex()['demo artist|demo track']['track_uri']);
        } finally {
            @unlink($archivePath);
        }
    }

    public function testItIgnoresNonStringYourLibraryValuesWithoutBlockingImport(): void
    {
        $inspector = $this->createInspector();
        $archivePath = $this->createArchive([
            'Spotify Account Data/StreamingHistory_music_0.json' => json_encode([
                [
                    'endTime' => '2026-06-06 19:52',
                    'artistName' => 'Demo Artist',
                    'trackName' => 'Demo Track',
                    'msPlayed' => 120000,
                ],
            ], JSON_THROW_ON_ERROR),
            'Spotify Account Data/YourLibrary.json' => json_encode([
                'tracks' => [
                    [
                        'artist' => 'Demo Artist',
                        'album' => ['unexpected'],
                        'track' => 'Demo Track',
                        'uri' => ['unexpected'],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        try {
            $inspection = $inspector->inspectUploadedArchive(new UploadedFile($archivePath, 'demo.zip', 'application/zip', null, true));

            self::assertSame('available', $inspection->getYourLibraryStatus());
            self::assertSame([], $inspection->getLibraryIndex());
        } finally {
            @unlink($archivePath);
        }
    }

    public function testItRejectsArchivesWithoutCompatibleMusicFile(): void
    {
        $inspector = $this->createInspector();
        $archivePath = $this->createArchive([
            'Spotify Account Data/StreamingHistory_podcast_0.json' => json_encode([
                [
                    'endTime' => '2026-06-06 19:52',
                    'podcastName' => 'Demo Podcast',
                    'episodeName' => 'Episode 1',
                    'msPlayed' => 120000,
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        try {
            $this->expectException(InvalidArgumentException::class);
            $inspector->inspectUploadedArchive(new UploadedFile($archivePath, 'demo.zip', 'application/zip', null, true));
        } finally {
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

    private function createZipFromDirectory(string $directory): string
    {
        $path = tempnam(sys_get_temp_dir(), 'music-fixture-');
        self::assertNotFalse($path);

        unlink($path);
        $zipPath = $path . '.zip';

        $zip = new ZipArchive();
        self::assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            $filePath = $file->getPathname();
            $localName = substr($filePath, strlen($directory) + 1);
            $zip->addFile($filePath, $localName);
        }

        $zip->close();

        return $zipPath;
    }

    private function createInspector(): SpotifyArchiveInspector
    {
        return new SpotifyArchiveInspector(
            new MusicNormalizationService(),
            new SpotifyArchiveStreamFactory(),
        );
    }

    private static function fixturesDirectory(): string
    {
        return dirname(__DIR__, 6) . '/tests/fixtures/music/spotify-archive';
    }
}

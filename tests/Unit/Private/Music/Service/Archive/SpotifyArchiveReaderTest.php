<?php

declare(strict_types=1);

namespace App\Tests\Unit\Private\Music\Service\Archive;

use App\Private\Music\Service\Archive\SpotifyArchiveReader;
use App\Private\Music\Service\Normalization\MusicNormalizationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

final class SpotifyArchiveReaderTest extends TestCase
{
    public function testItNormalizesNumericAlbumNamesFromYourLibrary(): void
    {
        $reader = new SpotifyArchiveReader(new MusicNormalizationService());
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
            $plan = $reader->readUploadedArchive(new UploadedFile($archivePath, 'demo.zip', 'application/zip', null, true));
            $plan->collectSummary();

            self::assertSame('spotify_archive', $plan->getSourceType());
            self::assertCount(1, $plan->getMusicFiles());
            self::assertSame('1989', $plan->getLibraryIndex()['demo artist|demo track']['album_name']);
            self::assertSame('spotify:track:demo-track', $plan->getLibraryIndex()['demo artist|demo track']['track_uri']);
            self::assertSame(1, $plan->getTotalEntries());
            self::assertSame(0, $plan->getIgnoredEntries());
            self::assertSame(0, $plan->getErrorEntries());
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
}

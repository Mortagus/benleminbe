<?php

declare(strict_types=1);

namespace App\Tests\Unit\Private\Music\Service\Import;

use App\Private\Music\Dto\SpotifyListeningEventRow;
use App\Private\Music\Entity\Artist;
use App\Private\Music\Entity\Track;
use App\Private\Music\Entity\MusicImport;
use App\Private\Music\Repository\MusicRepository;
use App\Private\Music\Service\Import\MusicImportBatchWriter;
use DateTimeImmutable;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class MusicImportBatchWriterTest extends TestCase
{
    public function testItFlushesEventsByBatchAndReusesCachedArtistsAndTracks(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $artistRepository = $this->createMock(EntityRepository::class);
        $trackRepository = $this->createMock(EntityRepository::class);
        $musicRepository = new MusicRepository($entityManager);
        $writer = new MusicImportBatchWriter($entityManager, $musicRepository, 2);
        $import = new MusicImport('import_test', 'spotify.zip', 'checksum');

        $entityManager->expects(self::exactly(3))
            ->method('getRepository')
            ->willReturnCallback(static function (string $class) use ($artistRepository, $trackRepository): EntityRepository {
                return match ($class) {
                    Artist::class => $artistRepository,
                    Track::class => $trackRepository,
                    default => throw new \LogicException(sprintf('Unexpected repository class "%s".', $class)),
                };
            });

        $artistRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['normalizedName' => 'alpha artist'])
            ->willReturn(null);

        $trackRepository->expects(self::exactly(2))
            ->method('findOneBy')
            ->willReturn(null);

        $entityManager->expects(self::exactly(6))
            ->method('persist');

        $entityManager->expects(self::exactly(2))
            ->method('flush');

        $entityManager->expects(self::exactly(3))
            ->method('detach');

        $writer->persistListeningEvent($import, $this->makeRow('First Light', 1));
        $writer->persistListeningEvent($import, $this->makeRow('Road Trip', 2));
        $writer->persistListeningEvent($import, $this->makeRow('First Light', 3));
        $writer->finish();

        self::assertTrue(true);
    }

    private function makeRow(string $trackName, int $recordIndex): SpotifyListeningEventRow
    {
        return new SpotifyListeningEventRow(
            playedAt: new DateTimeImmutable('2026-06-06 19:52'),
            artistNameRaw: 'Alpha Artist',
            artistNameNormalized: 'alpha artist',
            trackName: $trackName,
            trackNameNormalized: mb_strtolower($trackName),
            playedDurationMs: 120000,
            albumName: 'Sunrise Sessions',
            trackUri: 'spotify:track:alpha1',
            sourcePayloadVersion: 'spotify_streaming_history_v1',
            sourceFileName: 'Spotify Account Data/StreamingHistory_music_0.json',
            sourceRecordIndex: $recordIndex,
            fingerprint: sprintf('fingerprint-%d', $recordIndex),
            rawPayload: [
                'artistName' => 'Alpha Artist',
                'trackName' => $trackName,
                'endTime' => '2026-06-06 19:52',
                'msPlayed' => 120000,
            ],
        );
    }
}

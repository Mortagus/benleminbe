<?php

declare(strict_types=1);

namespace App\Tests\Functional\Private;

use App\Private\Music\Entity\Artist;
use App\Private\Music\Entity\ListeningEvent;
use App\Private\Music\Entity\MusicImport;
use App\Private\Music\Entity\Track;
use App\Private\Music\Service\Normalization\MusicNormalizationService;
use App\Private\Music\Repository\MusicRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

final class MusicWebTest extends MusicWebTestCase
{
    public function testPrivateAreaRedirectsGuestsAndShowsLoginPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/private/music');

        self::assertResponseRedirects('/private/login');

        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Connexion');
        self::assertSelectorExists('input[name="_csrf_token"]');
    }

    public function testImportingArchiveBuildsDashboardStatistics(): void
    {
        $client = $this->createAuthenticatedClient();
        $this->submitFixtureArchiveImport($client);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', '7 ligne');
        self::assertSelectorTextContains('body', '2 fichier');
        self::assertSelectorTextContains('body', '1 fichier');
        self::assertSelectorTextContains('body', 'StreamingHistory_music_0.json');
        self::assertSelectorTextContains('body', 'StreamingHistory_music_1.json');
        self::assertSelectorTextContains('[data-stat-key="batch_count"]', '4');
        self::assertSelectorTextContains('body', 'Lots');
        self::assertSelectorTextContains('body', 'taille batch 2');
        self::assertSelectorTextContains('body', 'YourLibrary disponible');
        self::assertSelectorTextContains('body', 'Mémoire max');

        $client->request('GET', '/private/music');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-stat-key="listening_events_total"]', '7');
        self::assertSelectorTextContains('[data-stat-key="duration_total"]', '17 min');
        self::assertSelectorTextContains('[data-stat-key="artists_total"]', '4');
        self::assertSelectorTextContains('[data-stat-key="tracks_total"]', '5');
        self::assertSelectorTextContains('[data-stat-key="albums_total"]', '4');
        self::assertSelectorTextContains('body', 'Alpha Artist');
        self::assertSelectorTextContains('body', 'Beta Band');
        self::assertSelectorTextContains('body', 'First Light');
        self::assertSelectorTextContains('body', 'Road Trip');
        self::assertSelectorTextContains('body', 'Sunrise Sessions');
    }

    public function testImportingArchiveOnFilledDatabaseReusesExistingArtistsAndTracks(): void
    {
        $client = $this->createAuthenticatedClient();
        $this->submitFixtureArchiveImport($client);

        $secondZipPath = $this->createZipArchive([
            'Spotify Account Data/StreamingHistory_music_0.json' => json_encode([
                [
                    'endTime' => '2026-06-07 09:30',
                    'artistName' => 'Alpha Artist',
                    'trackName' => 'First Light',
                    'msPlayed' => 130000,
                ],
                [
                    'endTime' => '2026-06-07 09:35',
                    'artistName' => 'Delta Duo',
                    'trackName' => 'Fresh Cut',
                    'msPlayed' => 150000,
                ],
            ], JSON_THROW_ON_ERROR),
            'Spotify Account Data/YourLibrary.json' => json_encode([
                'tracks' => [
                    [
                        'artist' => 'Delta Duo',
                        'album' => 'Fresh Cuts',
                        'track' => 'Fresh Cut',
                        'uri' => 'spotify:track:delta1',
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $client->request('GET', '/private/music/import');
        $token = $client->getCrawler()->filter('input[name="_token"]')->attr('value');

        $client->request(
            'POST',
            '/private/music/import',
            ['_token' => $token],
            ['archive' => new UploadedFile($secondZipPath, 'spotify-second.zip', 'application/zip', null, true)],
        );

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', '2 ligne');
        self::assertSelectorTextContains('body', '1 fichier');
        self::assertSelectorTextContains('[data-stat-key="batch_count"]', '1');
        self::assertSelectorTextContains('body', 'Lots');
        self::assertSelectorTextContains('body', 'YourLibrary disponible');
        self::assertSelectorTextContains('body', '1 artiste');
        self::assertSelectorTextContains('body', '1 titre');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(MusicRepository::class);
        self::assertSame(5, $entityManager->getRepository(Artist::class)->count([]));
        self::assertSame(6, $entityManager->getRepository(Track::class)->count([]));
        self::assertSame(9, $entityManager->getRepository(ListeningEvent::class)->count([]));

        $alpha = $repository->findArtistByNormalizedName('alpha artist');
        self::assertInstanceOf(Artist::class, $alpha);
        self::assertSame(3, $alpha->getListeningCount());

        $firstLight = $repository->findTrackByArtistAndNormalizedTitle($alpha, 'first light');
        self::assertInstanceOf(Track::class, $firstLight);
        self::assertSame(2, $firstLight->getListeningCount());
        self::assertSame('Sunrise Sessions', $firstLight->getAlbumName());
        self::assertSame('spotify:track:alpha1', $firstLight->getSpotifyUri());

        $delta = $repository->findArtistByNormalizedName('delta duo');
        self::assertInstanceOf(Artist::class, $delta);
        self::assertSame(1, $delta->getListeningCount());

        @unlink($secondZipPath);
    }

    public function testImportingSameArchiveTwiceCreatesDuplicateAttemptWithoutDuplicatingEvents(): void
    {
        $client = $this->createAuthenticatedClient();
        $this->submitFixtureArchiveImport($client);
        $this->submitFixtureArchiveImport($client);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'déjà été importée');

        $client->request('GET', '/private/music');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-stat-key="listening_events_total"]', '7');
        self::assertSelectorTextContains('[data-stat-key="artists_total"]', '4');
        self::assertSelectorTextContains('[data-stat-key="tracks_total"]', '5');

        $imports = self::getContainer()->get(EntityManagerInterface::class)->getRepository(MusicImport::class)->findBy([], ['importedAt' => 'ASC']);
        self::assertCount(1, $imports);
        self::assertSame('completed', $imports[0]->getStatus()->value);
    }

    public function testHardResetClearsMusicDataAndAllowsRelaunchingTheSameArchive(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/private/music/import');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Aucun import musical n a encore été enregistré');
        self::assertSelectorExists('p.private-empty');
        self::assertSelectorCount(0, 'form[action="/private/music/import/reset-hard"]');

        $this->submitFixtureArchiveImport($client);
        self::assertResponseIsSuccessful();

        $client->request('GET', '/private/music/import');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[action="/private/music/import/reset-hard"]');

        $token = $client->getCrawler()->filter('form[action="/private/music/import/reset-hard"] input[name="_token"]')->attr('value');
        $client->request(
            'POST',
            '/private/music/import/reset-hard',
            ['_token' => $token],
        );

        self::assertResponseRedirects('/private/music/import');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Réinitialisation terminée');
        self::assertSelectorTextContains('body', 'Aucune donnée musicale n est encore présente');

        $this->submitFixtureArchiveImport($client);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', '7 ligne');
        self::assertSelectorTextNotContains('body', 'déjà été importée');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertSame(1, $entityManager->getRepository(MusicImport::class)->count([]));
        self::assertSame(7, $entityManager->getRepository(ListeningEvent::class)->count([]));
    }

    public function testImportRejectsInvalidZip(): void
    {
        $client = $this->createAuthenticatedClient();
        $invalidZip = $this->createTempFile('broken.zip', 'not a zip');

        $client->request('GET', '/private/music/import');
        $token = $client->getCrawler()->filter('input[name="_token"]')->attr('value');

        $client->request(
            'POST',
            '/private/music/import',
            ['_token' => $token],
            ['archive' => new UploadedFile($invalidZip, 'broken.zip', 'application/zip', null, true)],
        );

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.private-alert', 'Le fichier ZIP importe ne peut pas etre ouvert');
    }

    public function testImportRejectsArchiveWithoutCompatibleJson(): void
    {
        $client = $this->createAuthenticatedClient();
        $zipPath = $this->createZipArchive([
            'Spotify Account Data/Other.json' => '{"foo":"bar"}',
            'Spotify Account Data/ReadMe.txt' => 'ignored',
        ]);

        $client->request('GET', '/private/music/import');
        $token = $client->getCrawler()->filter('input[name="_token"]')->attr('value');

        $client->request(
            'POST',
            '/private/music/import',
            ['_token' => $token],
            ['archive' => new UploadedFile($zipPath, 'empty.zip', 'application/zip', null, true)],
        );

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.private-alert', 'Aucun fichier StreamingHistory_music_*.json exploitable');
    }

    public function testArtistsListingSupportsSearchSortingAndPagination(): void
    {
        $client = $this->createAuthenticatedClient();
        $this->submitFixtureArchiveImport($client);
        $this->seedSyntheticPaginationData();

        $client->request('GET', '/private/music/artists?page=2');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Artistes');
        self::assertSelectorTextContains('.private-pagination', 'Précédente');
        self::assertSelectorTextContains('.private-pagination', 'Suivante');
        self::assertSelectorTextContains('.private-pagination__link--current', '2');
        self::assertSelectorTextContains('.private-muted', 'Affichage 21-29 sur 29 artistes.');

        $client->request('GET', '/private/music/artists?q=Alpha&sort=plays&direction=desc');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Alpha Artist');
        self::assertStringNotContainsString('Beta Band', $client->getResponse()->getContent());
    }

    public function testTracksListingShowsAlbumAndSpotifyUriWhenAvailable(): void
    {
        $client = $this->createAuthenticatedClient();
        $this->submitFixtureArchiveImport($client);

        $client->request('GET', '/private/music/tracks?q=First+Light');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Titres');
        self::assertSelectorTextContains('body', 'Alpha Artist');
        self::assertSelectorTextContains('body', 'Sunrise Sessions');
        self::assertSelectorTextContains('body', 'spotify:track:alpha1');
    }

    public function testAlbumsAndGenresScreensRender(): void
    {
        $client = $this->createAuthenticatedClient();
        $this->submitFixtureArchiveImport($client);

        $client->request('GET', '/private/music/albums');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', '4 albums distincts');
        self::assertSelectorTextContains('body', 'vue détaillée n est pas encore activée');

        $client->request('GET', '/private/music/genres');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Aucun style n a encore été saisi manuellement');
    }

    private function submitFixtureArchiveImport(\Symfony\Bundle\FrameworkBundle\KernelBrowser $client): Crawler
    {
        $zipPath = $this->createFixtureArchiveZip();

        $client->request('GET', '/private/music/import');
        $token = $client->getCrawler()->filter('input[name="_token"]')->attr('value');

        $client->request(
            'POST',
            '/private/music/import',
            ['_token' => $token],
            ['archive' => new UploadedFile($zipPath, 'spotify-archive.zip', 'application/zip', null, true)],
        );

        return $client->getCrawler();
    }

    private function createFixtureArchiveZip(): string
    {
        return $this->createZipFromDirectory(self::fixturesDirectory());
    }

    private function createTempFile(string $filename, string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'music-invalid-');
        if ($path === false) {
            self::fail('Unable to create a temporary file.');
        }

        file_put_contents($path, $contents);

        $target = dirname($path) . DIRECTORY_SEPARATOR . $filename;
        rename($path, $target);

        return $target;
    }

    /**
     * @param array<string, string> $entries
     */
    private function createZipArchive(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'music-zip-');
        if ($path === false) {
            self::fail('Unable to create a temporary archive.');
        }

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
        if ($path === false) {
            self::fail('Unable to create a temporary archive.');
        }

        unlink($path);
        $zipPath = $path . '.zip';

        $zip = new ZipArchive();
        self::assertTrue($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
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

    private function seedSyntheticPaginationData(): void
    {
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(MusicRepository::class);
        $normalizer = self::getContainer()->get(MusicNormalizationService::class);

        /** @var MusicImport $import */
        $import = $entityManager->getRepository(MusicImport::class)->findOneBy([], ['importedAt' => 'DESC']);
        self::assertInstanceOf(MusicImport::class, $import);

        for ($index = 1; $index <= 25; ++$index) {
            $artistName = sprintf('Pagination Artist %02d', $index);
            $trackName = sprintf('Pagination Track %02d', $index);
            $playedAt = new DateTimeImmutable(sprintf('2026-01-%02d 12:00', (($index - 1) % 28) + 1));

            $artist = $repository->findArtistByNormalizedName($normalizer->buildArtistKey($artistName));
            if (!$artist instanceof Artist) {
                $artist = new Artist(
                    sprintf('music_artist_seed_%02d', $index),
                    $normalizer->buildArtistKey($artistName),
                    $artistName,
                );
                $entityManager->persist($artist);
            }

            $artist->incrementListeningCount();
            $artist->addPlayedMs(90_000);
            if ($artist->getFirstPlayedAt() === null || $playedAt < $artist->getFirstPlayedAt()) {
                $artist->setFirstPlayedAt($playedAt);
            }
            if ($artist->getLastPlayedAt() === null || $playedAt > $artist->getLastPlayedAt()) {
                $artist->setLastPlayedAt($playedAt);
            }

            $track = $repository->findTrackByArtistAndNormalizedTitle($artist, $normalizer->normalizeKey($trackName));
            if (!$track instanceof Track) {
                $track = new Track(
                    sprintf('music_track_seed_%02d', $index),
                    $artist,
                    $normalizer->normalizeKey($trackName),
                    $trackName,
                );
                $entityManager->persist($track);
            }

            $track->incrementListeningCount();
            $track->addPlayedMs(90_000);
            if ($track->getFirstPlayedAt() === null || $playedAt < $track->getFirstPlayedAt()) {
                $track->setFirstPlayedAt($playedAt);
            }
            if ($track->getLastPlayedAt() === null || $playedAt > $track->getLastPlayedAt()) {
                $track->setLastPlayedAt($playedAt);
            }

            $event = new ListeningEvent(
                sprintf('music_event_seed_%02d', $index),
                $import,
                $artist,
                $track,
                $playedAt,
                sprintf('seed-pagination-%02d', $index),
            );
            $event->setTrackName($trackName);
            $event->setArtistNameRaw($artistName);
            $event->setArtistNameNormalized($normalizer->buildArtistKey($artistName));
            $event->setPlayedDurationMs(90_000);
            $event->setSourcePayloadVersion('seed');
            $event->setSourceFileName('seed.json');
            $event->setSourceRecordIndex($index);
            $event->setRawPayload([
                'artistName' => $artistName,
                'trackName' => $trackName,
                'endTime' => $playedAt->format('Y-m-d H:i'),
                'msPlayed' => 90_000,
            ]);

            $entityManager->persist($event);
        }

        $entityManager->flush();
    }

    private static function fixturesDirectory(): string
    {
        return self::getContainer()->getParameter('kernel.project_dir') . '/tests/fixtures/music/spotify-archive';
    }
}

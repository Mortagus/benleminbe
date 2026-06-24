<?php

declare(strict_types=1);

namespace App\Private\Music\Service\Import;

use App\Private\Music\Dto\SpotifyListeningEventRow;
use App\Private\Music\Entity\Artist;
use App\Private\Music\Entity\ListeningEvent;
use App\Private\Music\Entity\MusicImport;
use App\Private\Music\Entity\Track;
use App\Private\Music\Repository\MusicRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;

final class MusicImportBatchWriter
{
    private const int DEFAULT_BATCH_SIZE = 250;

    /**
     * @var array<string, Artist>
     */
    private array $artistsByNormalizedName = [];

    /**
     * @var array<string, Track>
     */
    private array $tracksByArtistAndTitle = [];

    /**
     * @var list<ListeningEvent>
     */
    private array $pendingEvents = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MusicRepository $musicRepository,
        private readonly int $batchSize = self::DEFAULT_BATCH_SIZE,
    ) {
    }

    public function persistListeningEvent(MusicImport $import, SpotifyListeningEventRow $row): void
    {
        $artist = $this->resolveArtist($row->artistNameRaw, $row->artistNameNormalized, $row->playedAt);
        $track = $this->resolveTrack($artist, $row->trackNameNormalized, $row->trackName);

        $event = new ListeningEvent(
            $this->generateId('music_event'),
            $import,
            $artist,
            $track,
            $row->playedAt,
            $row->fingerprint,
        );
        $event->setTrackName($row->trackName);
        $event->setArtistNameRaw($row->artistNameRaw);
        $event->setArtistNameNormalized($row->artistNameNormalized);
        $event->setAlbumName($row->albumName);
        $event->setPlayedDurationMs($row->playedDurationMs);
        $event->setTrackUri($row->trackUri);
        $event->setSourcePayloadVersion($row->sourcePayloadVersion);
        $event->setSourceFileName($row->sourceFileName);
        $event->setSourceRecordIndex($row->sourceRecordIndex);
        $event->setRawPayload($row->rawPayload);

        $artist->incrementListeningCount();
        $artist->addPlayedMs($row->playedDurationMs ?? 0);
        $this->updatePlayedRange($artist, $row->playedAt);

        $track->incrementListeningCount();
        $track->addPlayedMs($row->playedDurationMs ?? 0);
        $this->updatePlayedRange($track, $row->playedAt);
        if ($track->getAlbumName() === null && $row->albumName !== null) {
            $track->setAlbumName($row->albumName);
        }
        if ($track->getSpotifyUri() === null && $row->trackUri !== null) {
            $track->setSpotifyUri($row->trackUri);
        }

        $this->entityManager->persist($event);
        $this->pendingEvents[] = $event;

        if (count($this->pendingEvents) >= $this->batchSize) {
            $this->flushPendingEvents();
        }
    }

    public function finish(): void
    {
        $this->flushPendingEvents();
    }

    private function flushPendingEvents(): void
    {
        if ($this->pendingEvents === []) {
            return;
        }

        $this->entityManager->flush();
        foreach ($this->pendingEvents as $event) {
            $this->entityManager->detach($event);
        }
        $this->pendingEvents = [];
    }

    private function resolveArtist(string $displayName, string $normalizedName, DateTimeImmutable $playedAt): Artist
    {
        if (isset($this->artistsByNormalizedName[$normalizedName])) {
            $artist = $this->artistsByNormalizedName[$normalizedName];
        } else {
            $artist = $this->musicRepository->findArtistByNormalizedName($normalizedName);
            if (!$artist instanceof Artist) {
                $artist = new Artist($this->generateId('music_artist'), $normalizedName, $displayName);
                $this->entityManager->persist($artist);
            }

            $this->artistsByNormalizedName[$normalizedName] = $artist;
        }

        if ($artist->getDisplayName() === '' || mb_strtolower($artist->getDisplayName()) === mb_strtolower($displayName)) {
            $artist->setDisplayName($displayName);
        }

        $this->updatePlayedRange($artist, $playedAt);

        return $artist;
    }

    private function resolveTrack(Artist $artist, string $normalizedTitle, string $displayTitle): Track
    {
        $key = $artist->getNormalizedName() . '|' . $normalizedTitle;
        if (isset($this->tracksByArtistAndTitle[$key])) {
            return $this->tracksByArtistAndTitle[$key];
        }

        $track = $this->musicRepository->findTrackByArtistAndNormalizedTitle($artist, $normalizedTitle);
        if (!$track instanceof Track) {
            $track = new Track($this->generateId('music_track'), $artist, $normalizedTitle, $displayTitle);
            $this->entityManager->persist($track);
        }

        $this->tracksByArtistAndTitle[$key] = $track;

        return $track;
    }

    private function updatePlayedRange(Artist|Track $entity, DateTimeImmutable $playedAt): void
    {
        if ($entity->getFirstPlayedAt() === null || $playedAt < $entity->getFirstPlayedAt()) {
            $entity->setFirstPlayedAt($playedAt);
        }

        if ($entity->getLastPlayedAt() === null || $playedAt > $entity->getLastPlayedAt()) {
            $entity->setLastPlayedAt($playedAt);
        }
    }

    private function generateId(string $prefix): string
    {
        return sprintf('%s_%s', $prefix, bin2hex(random_bytes(8)));
    }
}

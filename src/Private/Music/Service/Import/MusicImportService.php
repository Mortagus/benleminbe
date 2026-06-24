<?php

declare(strict_types=1);

namespace App\Private\Music\Service\Import;

use App\Private\Music\Entity\Artist;
use App\Private\Music\Entity\ListeningEvent;
use App\Private\Music\Entity\MusicImport;
use App\Private\Music\Entity\Track;
use App\Private\Music\Enum\MusicImportSourceType;
use App\Private\Music\Enum\MusicImportStatus;
use App\Private\Music\Repository\MusicRepository;
use App\Private\Music\Service\Archive\SpotifyArchiveReader;
use App\Private\Music\Service\Normalization\MusicNormalizationService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MusicImportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MusicRepository $musicRepository,
        private readonly SpotifyArchiveReader $archiveReader,
        private readonly MusicNormalizationService $normalizationService,
    ) {
    }

    /**
     * @return array{import: array<string, mixed>, summary: array<string, mixed>}
     */
    public function importSpotifyArchive(UploadedFile $uploadedFile): array
    {
        $archive = $this->archiveReader->readUploadedArchive($uploadedFile);

        $existingImport = $this->musicRepository->findImportByChecksum($archive['archive_checksum']);
        if ($existingImport instanceof MusicImport) {
            return [
                'import' => $this->decorateImport($existingImport),
                'summary' => $this->decorateArchiveSummary($archive, true),
            ];
        }

        $this->entityManager->beginTransaction();

        try {
            $import = new MusicImport(
                $this->generateId('music_import'),
                $archive['original_filename'],
                $archive['archive_checksum'],
            );
            $import->setSourceType(MusicImportSourceType::SpotifyArchive);
            $import->setStatus(MusicImportStatus::Completed);
            $import->setImportedAt(new DateTimeImmutable());

            $processedEntries = 0;
            $musicEvents = $archive['music_events'];
            $libraryIndex = $archive['library_index'];

            foreach ($musicEvents as $eventData) {
                $libraryMetadata = $libraryIndex[$eventData['artist_name_normalized'] . '|' . $eventData['track_name_normalized']] ?? [
                    'album_name' => null,
                    'track_uri' => null,
                ];
                $eventData['album_name'] = $libraryMetadata['album_name'];
                $eventData['track_uri'] = $libraryMetadata['track_uri'];

                $artist = $this->getOrCreateArtist($eventData['artist_name_raw'], $eventData['artist_name_normalized'], $eventData['played_at']);
                $track = $this->getOrCreateTrack($artist, $eventData);

                $event = new ListeningEvent(
                    $this->generateId('music_event'),
                    $import,
                    $artist,
                    $track,
                    $eventData['played_at'],
                    $eventData['fingerprint'],
                );
                $event->setTrackName($eventData['track_name']);
                $event->setArtistNameRaw($eventData['artist_name_raw']);
                $event->setArtistNameNormalized($eventData['artist_name_normalized']);
                $event->setAlbumName($eventData['album_name']);
                $event->setPlayedDurationMs($eventData['played_duration_ms']);
                $event->setTrackUri($eventData['track_uri']);
                $event->setSourcePayloadVersion($eventData['source_payload_version']);
                $event->setSourceFileName($eventData['source_file_name']);
                $event->setSourceRecordIndex($eventData['source_record_index']);
                $event->setRawPayload($eventData['raw_payload']);

                $artist->incrementListeningCount();
                $artist->addPlayedMs($eventData['played_duration_ms'] ?? 0);
                $this->updateArtistPlayedRange($artist, $eventData['played_at']);

                $track->incrementListeningCount();
                $track->addPlayedMs($eventData['played_duration_ms'] ?? 0);
                $this->updateTrackPlayedRange($track, $eventData['played_at']);
                if ($track->getAlbumName() === null && $eventData['album_name'] !== null) {
                    $track->setAlbumName($eventData['album_name']);
                }
                if ($track->getSpotifyUri() === null && $eventData['track_uri'] !== null) {
                    $track->setSpotifyUri($eventData['track_uri']);
                }

                $this->entityManager->persist($event);
                ++$processedEntries;
            }

            $import->setSummary($this->decorateArchiveSummary($archive));
            $this->entityManager->persist($import);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (InvalidArgumentException $exception) {
            $this->entityManager->rollback();
            throw $exception;
        } catch (\Throwable $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }

        return [
            'import' => $this->decorateImport($import),
            'summary' => $this->decorateArchiveSummary($archive),
        ];
    }

    /**
     * @param array<string, mixed> $eventData
     */
    private function getOrCreateTrack(Artist $artist, array $eventData): Track
    {
        $track = $this->musicRepository->findTrackByArtistAndNormalizedTitle($artist, $eventData['track_name_normalized']);
        if (!$track instanceof Track) {
            $track = new Track(
                $this->generateId('music_track'),
                $artist,
                $eventData['track_name_normalized'],
                $eventData['track_name'],
            );
            $this->entityManager->persist($track);
        }

        return $track;
    }

    private function getOrCreateArtist(string $displayName, string $normalizedName, DateTimeImmutable $playedAt): Artist
    {
        $artist = $this->musicRepository->findArtistByNormalizedName($normalizedName);
        if (!$artist instanceof Artist) {
            $artist = new Artist($this->generateId('music_artist'), $normalizedName, $displayName);
            $this->entityManager->persist($artist);
        }

        if ($artist->getDisplayName() === '' || mb_strtolower($artist->getDisplayName()) === mb_strtolower($displayName)) {
            $artist->setDisplayName($displayName);
        }

        $this->updateArtistPlayedRange($artist, $playedAt);

        return $artist;
    }

    private function updateArtistPlayedRange(Artist $artist, DateTimeImmutable $playedAt): void
    {
        if ($artist->getFirstPlayedAt() === null || $playedAt < $artist->getFirstPlayedAt()) {
            $artist->setFirstPlayedAt($playedAt);
        }

        if ($artist->getLastPlayedAt() === null || $playedAt > $artist->getLastPlayedAt()) {
            $artist->setLastPlayedAt($playedAt);
        }
    }

    private function updateTrackPlayedRange(Track $track, DateTimeImmutable $playedAt): void
    {
        if ($track->getFirstPlayedAt() === null || $playedAt < $track->getFirstPlayedAt()) {
            $track->setFirstPlayedAt($playedAt);
        }

        if ($track->getLastPlayedAt() === null || $playedAt > $track->getLastPlayedAt()) {
            $track->setLastPlayedAt($playedAt);
        }
    }

    /**
     * @param array<string, mixed> $archive
     *
     * @return array<string, mixed>
     */
    private function decorateArchiveSummary(array $archive, bool $duplicate = false): array
    {
        return [
            'duplicate' => $duplicate,
            'archive_checksum' => $archive['archive_checksum'],
            'original_filename' => $archive['original_filename'],
            'source_type' => $archive['source_type'],
            'file_count' => $archive['file_count'],
            'music_files' => $archive['music_files'],
            'ignored_files' => $archive['ignored_files'],
            'processed_entries' => count($archive['music_events']),
            'ignored_entries' => $archive['ignored_entries'],
            'error_entries' => $archive['error_entries'],
            'period_start' => $archive['period_start'],
            'period_end' => $archive['period_end'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decorateImport(MusicImport $import): array
    {
        return [
            'id' => $import->getId(),
            'original_filename' => $import->getOriginalFilename(),
            'source_type' => $import->getSourceType()->value,
            'source_type_label' => $import->getSourceType()->label(),
            'archive_checksum' => $import->getArchiveChecksum(),
            'status' => $import->getStatus()->value,
            'status_label' => $import->getStatus()->label(),
            'imported_at' => $import->getImportedAt(),
            'summary' => $import->getSummary(),
            'error_message' => $import->getErrorMessage(),
        ];
    }

    private function generateId(string $prefix): string
    {
        return sprintf('%s_%s', $prefix, bin2hex(random_bytes(8)));
    }
}

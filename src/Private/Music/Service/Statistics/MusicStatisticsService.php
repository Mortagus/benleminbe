<?php

declare(strict_types=1);

namespace App\Private\Music\Service\Statistics;

use App\Private\Music\Entity\Artist;
use App\Private\Music\Entity\Genre;
use App\Private\Music\Entity\ListeningEvent;
use App\Private\Music\Entity\MusicImport;
use App\Private\Music\Entity\Track;
use App\Private\Music\Service\Normalization\MusicNormalizationService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class MusicStatisticsService
{
    private const int DEFAULT_PAGE_SIZE = 20;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MusicNormalizationService $normalizationService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(): array
    {
        $eventRepository = $this->entityManager->getRepository(ListeningEvent::class);
        $artistRepository = $this->entityManager->getRepository(Artist::class);
        $trackRepository = $this->entityManager->getRepository(Track::class);
        $importRepository = $this->entityManager->getRepository(MusicImport::class);

        $durationTotal = (int) $this->entityManager->createQueryBuilder()
            ->select('COALESCE(SUM(event.playedDurationMs), 0)')
            ->from(ListeningEvent::class, 'event')
            ->getQuery()
            ->getSingleScalarResult();

        $period = $this->entityManager->createQueryBuilder()
            ->select('MIN(event.playedAt) AS periodStart, MAX(event.playedAt) AS periodEnd')
            ->from(ListeningEvent::class, 'event')
            ->getQuery()
            ->getOneOrNullResult() ?: [];

        $distinctAlbumCount = (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(DISTINCT track.albumName)')
            ->from(Track::class, 'track')
            ->where('track.albumName IS NOT NULL')
            ->andWhere('track.albumName <> :empty')
            ->setParameter('empty', '')
            ->getQuery()
            ->getSingleScalarResult();

        $artists = $this->decorateArtists($artistRepository->findBy([], ['listeningCount' => 'DESC', 'displayName' => 'ASC']));
        $tracks = $this->decorateTracks($trackRepository->findBy([], ['listeningCount' => 'DESC', 'displayTitle' => 'ASC']));
        $imports = $this->decorateImports($importRepository->findBy([], ['importedAt' => 'DESC']), 5);

        return [
            'stats' => [
                'listening_events_total' => (int) $eventRepository->count([]),
                'duration_total_ms' => $durationTotal,
                'duration_total_label' => $this->normalizationService->formatDurationLabel($durationTotal),
                'period_start' => $period['periodStart'] ?? null,
                'period_end' => $period['periodEnd'] ?? null,
                'artists_total' => (int) $artistRepository->count([]),
                'tracks_total' => (int) $trackRepository->count([]),
                'albums_total' => $distinctAlbumCount,
                'albums_available' => $distinctAlbumCount > 0,
                'latest_import_at' => $imports[0]['imported_at'] ?? null,
            ],
            'top_artists' => array_slice($artists, 0, 5),
            'top_tracks' => array_slice($tracks, 0, 5),
            'recent_imports' => $imports,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function getArtistsPage(array $filters, int $page, int $pageSize = self::DEFAULT_PAGE_SIZE): array
    {
        $artists = $this->decorateArtists($this->entityManager->getRepository(Artist::class)->findAll());
        $search = $this->normalizationService->normalizeSearch($filters['search'] ?? '');
        $sort = $this->normalizeSort($filters['sort'] ?? 'default');
        $direction = $this->normalizeDirection($filters['direction'] ?? 'desc');
        $genreSlug = $this->normalizationService->normalizeKey($filters['genre'] ?? '');

        if ($search !== '') {
            $artists = array_values(array_filter($artists, static function (array $artist) use ($search): bool {
                $haystack = implode(' ', [
                    $artist['display_name'],
                    $artist['normalized_name'],
                    implode(' ', $artist['genres']),
                ]);

                return str_contains(mb_strtolower($haystack), $search);
            }));
        }

        if ($genreSlug !== '') {
            $artists = array_values(array_filter($artists, static function (array $artist) use ($genreSlug): bool {
                return in_array($genreSlug, $artist['genre_slugs'], true);
            }));
        }

        $artists = $this->sortArtists($artists, $sort, $direction);

        return $this->paginateArtists($artists, $page, $pageSize, $filters, $sort, $direction, $genreSlug);
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function getTracksPage(array $filters, int $page, int $pageSize = self::DEFAULT_PAGE_SIZE): array
    {
        $tracks = $this->decorateTracks($this->entityManager->getRepository(Track::class)->findAll());
        $search = $this->normalizationService->normalizeSearch($filters['search'] ?? '');
        $sort = $this->normalizeSort($filters['sort'] ?? 'default');
        $direction = $this->normalizeDirection($filters['direction'] ?? 'desc');

        if ($search !== '') {
            $tracks = array_values(array_filter($tracks, static function (array $track) use ($search): bool {
                $haystack = implode(' ', [
                    $track['display_title'],
                    $track['artist_display_name'],
                    $track['album_name'] ?? '',
                    implode(' ', $track['genres']),
                ]);

                return str_contains(mb_strtolower($haystack), $search);
            }));
        }

        $tracks = $this->sortTracks($tracks, $sort, $direction);

        return $this->paginateTracks($tracks, $page, $pageSize, $filters, $sort, $direction);
    }

    /**
     * @return array<string, mixed>
     */
    public function getGenresPage(): array
    {
        $genres = $this->decorateGenres($this->entityManager->getRepository(Genre::class)->findBy([], ['name' => 'ASC']));

        return [
            'genres' => $genres,
            'total_genres' => count($genres),
        ];
    }

    /**
     * @param list<Artist> $artists
     *
     * @return list<array<string, mixed>>
     */
    private function decorateArtists(array $artists): array
    {
        return array_map(function (Artist $artist): array {
            $genres = [];
            $genreSlugs = [];

            foreach ($artist->getGenres() as $genre) {
                $genres[] = $genre->getName();
                $genreSlugs[] = $genre->getSlug();
            }

            return [
                'id' => $artist->getId(),
                'normalized_name' => $artist->getNormalizedName(),
                'display_name' => $artist->getDisplayName(),
                'listening_count' => $artist->getListeningCount(),
                'total_played_ms' => $artist->getTotalPlayedMs(),
                'total_played_label' => $this->normalizationService->formatDurationLabel($artist->getTotalPlayedMs()),
                'first_played_at' => $artist->getFirstPlayedAt(),
                'last_played_at' => $artist->getLastPlayedAt(),
                'genre_count' => count($genres),
                'genres' => $genres,
                'genre_slugs' => $genreSlugs,
            ];
        }, $artists);
    }

    /**
     * @param list<Track> $tracks
     *
     * @return list<array<string, mixed>>
     */
    private function decorateTracks(array $tracks): array
    {
        return array_map(function (Track $track): array {
            $genres = [];
            $genreSlugs = [];

            $artist = $track->getArtist();
            if ($artist instanceof Artist) {
                foreach ($artist->getGenres() as $genre) {
                    $genres[] = $genre->getName();
                    $genreSlugs[] = $genre->getSlug();
                }
            }

            return [
                'id' => $track->getId(),
                'artist_display_name' => $artist instanceof Artist ? $artist->getDisplayName() : '',
                'artist_normalized_name' => $artist instanceof Artist ? $artist->getNormalizedName() : '',
                'display_title' => $track->getDisplayTitle(),
                'normalized_title' => $track->getNormalizedTitle(),
                'album_name' => $track->getAlbumName(),
                'spotify_uri' => $track->getSpotifyUri(),
                'listening_count' => $track->getListeningCount(),
                'total_played_ms' => $track->getTotalPlayedMs(),
                'total_played_label' => $this->normalizationService->formatDurationLabel($track->getTotalPlayedMs()),
                'first_played_at' => $track->getFirstPlayedAt(),
                'last_played_at' => $track->getLastPlayedAt(),
                'genre_count' => count($genres),
                'genres' => $genres,
                'genre_slugs' => $genreSlugs,
            ];
        }, $tracks);
    }

    /**
     * @param list<Genre> $genres
     *
     * @return list<array<string, mixed>>
     */
    private function decorateGenres(array $genres): array
    {
        return array_map(static function (Genre $genre): array {
            return [
                'id' => $genre->getId(),
                'name' => $genre->getName(),
                'slug' => $genre->getSlug(),
            ];
        }, $genres);
    }

    /**
     * @param list<MusicImport> $imports
     *
     * @return list<array<string, mixed>>
     */
    private function decorateImports(array $imports, int $limit = 10): array
    {
        return array_slice(array_map(static function (MusicImport $import): array {
            return [
                'id' => $import->getId(),
                'original_filename' => $import->getOriginalFilename(),
                'source_type' => $import->getSourceType()->value,
                'source_type_label' => $import->getSourceType()->label(),
                'status' => $import->getStatus()->value,
                'status_label' => $import->getStatus()->label(),
                'imported_at' => $import->getImportedAt(),
                'summary' => $import->getSummary(),
                'error_message' => $import->getErrorMessage(),
            ];
        }, $imports), 0, $limit);
    }

    /**
     * @param list<array<string, mixed>> $artists
     *
     * @return list<array<string, mixed>>
     */
    private function sortArtists(array $artists, string $sort, string $direction): array
    {
        usort($artists, function (array $left, array $right) use ($sort, $direction): int {
            $comparison = match ($sort) {
                'name' => strnatcasecmp($left['display_name'], $right['display_name']),
                'duration' => $left['total_played_ms'] <=> $right['total_played_ms'],
                'first_played' => ($left['first_played_at']?->getTimestamp() ?? PHP_INT_MAX) <=> ($right['first_played_at']?->getTimestamp() ?? PHP_INT_MAX),
                'last_played' => ($left['last_played_at']?->getTimestamp() ?? PHP_INT_MIN) <=> ($right['last_played_at']?->getTimestamp() ?? PHP_INT_MIN),
                'default', 'plays' => $left['listening_count'] <=> $right['listening_count'],
                default => $left['listening_count'] <=> $right['listening_count'],
            };

            if ($direction === 'desc') {
                $comparison *= -1;
            }

            if ($comparison === 0) {
                $comparison = strnatcasecmp($left['display_name'], $right['display_name']);
            }

            return $comparison;
        });

        return $artists;
    }

    /**
     * @param list<array<string, mixed>> $tracks
     *
     * @return list<array<string, mixed>>
     */
    private function sortTracks(array $tracks, string $sort, string $direction): array
    {
        usort($tracks, function (array $left, array $right) use ($sort, $direction): int {
            $comparison = match ($sort) {
                'title' => strnatcasecmp($left['display_title'], $right['display_title']),
                'artist' => strnatcasecmp($left['artist_display_name'], $right['artist_display_name']),
                'album' => strnatcasecmp($left['album_name'] ?? '', $right['album_name'] ?? ''),
                'duration' => $left['total_played_ms'] <=> $right['total_played_ms'],
                'first_played' => ($left['first_played_at']?->getTimestamp() ?? PHP_INT_MAX) <=> ($right['first_played_at']?->getTimestamp() ?? PHP_INT_MAX),
                'last_played' => ($left['last_played_at']?->getTimestamp() ?? PHP_INT_MIN) <=> ($right['last_played_at']?->getTimestamp() ?? PHP_INT_MIN),
                'default', 'plays' => $left['listening_count'] <=> $right['listening_count'],
                default => $left['listening_count'] <=> $right['listening_count'],
            };

            if ($direction === 'desc') {
                $comparison *= -1;
            }

            if ($comparison === 0) {
                $comparison = strnatcasecmp($left['display_title'], $right['display_title']);
            }

            return $comparison;
        });

        return $tracks;
    }

    /**
     * @param list<array<string, mixed>> $artists
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function paginateArtists(array $artists, int $page, int $pageSize, array $filters, string $sort, string $direction, string $genreSlug): array
    {
        $total = count($artists);
        $pageCount = max(1, (int) ceil($total / $pageSize));
        $page = max(1, min($page, $pageCount));
        $offset = ($page - 1) * $pageSize;
        $items = array_slice($artists, $offset, $pageSize);

        return [
            'artists' => $items,
            'current_query' => (string) ($filters['search'] ?? ''),
            'current_sort' => $sort,
            'current_direction' => $direction,
            'current_genre' => $genreSlug,
            'total_artists' => $total,
            'page' => $page,
            'page_count' => $pageCount,
            'visible_from' => $total === 0 ? 0 : $offset + 1,
            'visible_to' => min($total, $offset + count($items)),
            'sort_options' => $this->getArtistSortOptions(),
            'direction_options' => $this->getDirectionOptions(),
            'genre_options' => $this->getGenreOptions(),
            'pagination_items' => $this->buildPaginationItems($page, $pageCount),
        ];
    }

    /**
     * @param list<array<string, mixed>> $tracks
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    private function paginateTracks(array $tracks, int $page, int $pageSize, array $filters, string $sort, string $direction): array
    {
        $total = count($tracks);
        $pageCount = max(1, (int) ceil($total / $pageSize));
        $page = max(1, min($page, $pageCount));
        $offset = ($page - 1) * $pageSize;
        $items = array_slice($tracks, $offset, $pageSize);

        return [
            'tracks' => $items,
            'current_query' => (string) ($filters['search'] ?? ''),
            'current_sort' => $sort,
            'current_direction' => $direction,
            'total_tracks' => $total,
            'page' => $page,
            'page_count' => $pageCount,
            'visible_from' => $total === 0 ? 0 : $offset + 1,
            'visible_to' => min($total, $offset + count($items)),
            'sort_options' => $this->getTrackSortOptions(),
            'direction_options' => $this->getDirectionOptions(),
            'pagination_items' => $this->buildPaginationItems($page, $pageCount),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getArtistSortOptions(): array
    {
        return [
            'default' => 'Pertinence',
            'name' => 'Nom',
            'plays' => 'Ecoutes',
            'duration' => 'Duree',
            'first_played' => 'Premiere ecoute',
            'last_played' => 'Derniere ecoute',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getTrackSortOptions(): array
    {
        return [
            'default' => 'Pertinence',
            'title' => 'Titre',
            'artist' => 'Artiste',
            'album' => 'Album',
            'plays' => 'Ecoutes',
            'duration' => 'Duree',
            'first_played' => 'Premiere ecoute',
            'last_played' => 'Derniere ecoute',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getDirectionOptions(): array
    {
        return [
            'desc' => 'Decroissant',
            'asc' => 'Croissant',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getGenreOptions(): array
    {
        $options = [];

        foreach ($this->entityManager->getRepository(Genre::class)->findBy([], ['name' => 'ASC']) as $genre) {
            if ($genre instanceof Genre) {
                $options[$genre->getSlug()] = $genre->getName();
            }
        }

        return $options;
    }

    /**
     * @return array<int, array{type: string, label: string, page?: int, current?: bool}>
     */
    private function buildPaginationItems(int $currentPage, int $pageCount): array
    {
        if ($pageCount <= 7) {
            $pages = [];
            for ($page = 1; $page <= $pageCount; ++$page) {
                $pages[] = [
                    'type' => 'page',
                    'label' => (string) $page,
                    'page' => $page,
                    'current' => $page === $currentPage,
                ];
            }

            return $pages;
        }

        $pages = [];
        $pages[] = ['type' => 'page', 'label' => '1', 'page' => 1, 'current' => $currentPage === 1];

        $windowStart = max(2, $currentPage - 1);
        $windowEnd = min($pageCount - 1, $currentPage + 1);

        if ($windowStart > 2) {
            $pages[] = ['type' => 'ellipsis', 'label' => '…'];
        }

        for ($page = $windowStart; $page <= $windowEnd; ++$page) {
            $pages[] = [
                'type' => 'page',
                'label' => (string) $page,
                'page' => $page,
                'current' => $page === $currentPage,
            ];
        }

        if ($windowEnd < $pageCount - 1) {
            $pages[] = ['type' => 'ellipsis', 'label' => '…'];
        }

        $pages[] = [
            'type' => 'page',
            'label' => (string) $pageCount,
            'page' => $pageCount,
            'current' => $currentPage === $pageCount,
        ];

        return $pages;
    }

    private function normalizeSort(string $sort): string
    {
        $sort = strtolower(trim($sort));

        return match ($sort) {
            'name', 'plays', 'duration', 'first_played', 'last_played', 'title', 'artist', 'album' => $sort,
            default => 'default',
        };
    }

    private function normalizeDirection(string $direction): string
    {
        $direction = strtolower(trim($direction));

        return $direction === 'asc' ? 'asc' : 'desc';
    }
}

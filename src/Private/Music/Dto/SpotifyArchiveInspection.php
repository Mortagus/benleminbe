<?php

declare(strict_types=1);

namespace App\Private\Music\Dto;

final class SpotifyArchiveInspection
{
    /**
     * @param list<string> $musicFileNames
     * @param list<string> $podcastFileNames
     * @param list<string> $ignoredFiles
     * @param array<string, array{album_name: string|null, track_uri: string|null}> $libraryIndex
     */
    public function __construct(
        private readonly string $archivePath,
        private readonly string $archiveChecksum,
        private readonly string $originalFilename,
        private readonly int $fileCount,
        private readonly array $musicFileNames,
        private readonly array $podcastFileNames,
        private readonly array $ignoredFiles,
        private readonly string $yourLibraryStatus,
        private readonly array $libraryIndex,
    ) {
    }

    public function getArchivePath(): string
    {
        return $this->archivePath;
    }

    public function getArchiveChecksum(): string
    {
        return $this->archiveChecksum;
    }

    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    public function getFileCount(): int
    {
        return $this->fileCount;
    }

    /**
     * @return list<string>
     */
    public function getMusicFileNames(): array
    {
        return $this->musicFileNames;
    }

    /**
     * @return list<string>
     */
    public function getPodcastFileNames(): array
    {
        return $this->podcastFileNames;
    }

    /**
     * @return list<string>
     */
    public function getIgnoredFiles(): array
    {
        return $this->ignoredFiles;
    }

    public function getYourLibraryStatus(): string
    {
        return $this->yourLibraryStatus;
    }

    public function hasYourLibraryIndex(): bool
    {
        return $this->yourLibraryStatus === 'available' && $this->libraryIndex !== [];
    }

    /**
     * @return array<string, array{album_name: string|null, track_uri: string|null}>
     */
    public function getLibraryIndex(): array
    {
        return $this->libraryIndex;
    }

    public function hasMusicFiles(): bool
    {
        return $this->musicFileNames !== [];
    }
}

<?php

declare(strict_types=1);

namespace App\Private\Music\Service\Archive;

use App\Private\Music\Dto\ParsedListeningEvent;
use App\Private\Music\Service\Normalization\MusicNormalizationService;
use App\Private\Music\Service\Import\MusicImportMetrics;
use DateTimeImmutable;
use InvalidArgumentException;
use ZipArchive;

final class SpotifyStreamingHistoryReader
{
    public function __construct(
        private readonly MusicNormalizationService $normalizationService,
        private readonly SpotifyArchiveStreamFactory $streamFactory,
    ) {
    }

    /**
     * @return iterable<ParsedListeningEvent>
     */
    public function iterateListeningEvents(ZipArchive $zip, string $entryName, MusicImportMetrics $metrics): iterable
    {
        $stream = $this->streamFactory->openEntryStream($zip, $entryName);
        $offset = 0;

        try {
            foreach ($this->iterateListeningEventRows($stream, $entryName) as $row) {
                $currentOffset = $offset++;
                $metrics->recordLineRead($entryName);

                if (!is_array($row)) {
                    $metrics->recordIgnoredLine($entryName);
                    continue;
                }

                $artistName = $this->normalizationService->normalizeText($row['artistName'] ?? null);
                $trackName = $this->normalizationService->normalizeText($row['trackName'] ?? null);
                $endTime = $this->normalizationService->normalizeText($row['endTime'] ?? null);

                if ($artistName === '' || $trackName === '' || $endTime === '') {
                    $metrics->recordIgnoredLine($entryName);
                    continue;
                }

                try {
                    $playedAt = $this->normalizationService->parseArchiveDateTime($endTime);
                } catch (InvalidArgumentException) {
                    $metrics->recordErrorLine($entryName);
                    continue;
                }

                $playedDurationMs = isset($row['msPlayed']) && is_numeric($row['msPlayed']) ? max(0, (int) $row['msPlayed']) : null;
                $artistDisplayName = $artistName;
                $trackDisplayName = $trackName;
                $normalizedArtistName = $this->normalizationService->buildArtistKey($artistDisplayName);
                $normalizedTrackName = $this->normalizationService->normalizeKey($trackDisplayName);
                $sourceFingerprint = hash('sha256', implode('|', [
                    $entryName,
                    (string) $currentOffset,
                    $playedAt->format('Y-m-d H:i'),
                    $normalizedArtistName,
                    $normalizedTrackName,
                    (string) ($playedDurationMs ?? ''),
                ]));

                $metrics->recordValidLine($entryName, $playedAt, $playedDurationMs);

                yield new ParsedListeningEvent(
                    sourceFileName: $entryName,
                    sourceOffset: $currentOffset,
                    playedAt: $playedAt,
                    artistDisplayName: $artistDisplayName,
                    normalizedArtistName: $normalizedArtistName,
                    trackDisplayName: $trackDisplayName,
                    normalizedTrackName: $normalizedTrackName,
                    playedDurationMs: $playedDurationMs,
                    sourceFingerprint: $sourceFingerprint,
                );
            }
        } finally {
            fclose($stream);
        }
    }

    /**
     * @return iterable<array<string, mixed>>
     */
    private function iterateListeningEventRows(mixed $stream, string $entryName): iterable
    {
        $parser = new class {
            private bool $arrayStarted = false;
            private bool $arrayEnded = false;
            private bool $inString = false;
            private bool $escape = false;
            private int $depth = 0;
            private string $objectBuffer = '';

            public function feed(string $chunk): iterable
            {
                $length = strlen($chunk);

                for ($index = 0; $index < $length; ++$index) {
                    $char = $chunk[$index];

                    if (!$this->arrayStarted) {
                        if (ctype_space($char)) {
                            continue;
                        }

                        if ($char !== '[') {
                            throw new InvalidArgumentException('Le fichier Spotify ne commence pas par une liste JSON.');
                        }

                        $this->arrayStarted = true;
                        continue;
                    }

                    if ($this->arrayEnded) {
                        if (!ctype_space($char)) {
                            throw new InvalidArgumentException('Le fichier Spotify contient des données apres la fin de la liste JSON.');
                        }

                        continue;
                    }

                    if ($this->objectBuffer === '') {
                        if (ctype_space($char) || $char === ',') {
                            continue;
                        }

                        if ($char === ']') {
                            $this->arrayEnded = true;
                            continue;
                        }

                        if ($char !== '{') {
                            throw new InvalidArgumentException('Le fichier Spotify contient un element JSON inattendu.');
                        }

                        $this->objectBuffer = '{';
                        $this->depth = 1;
                        $this->inString = false;
                        $this->escape = false;
                        continue;
                    }

                    $this->objectBuffer .= $char;

                    if ($this->inString) {
                        if ($this->escape) {
                            $this->escape = false;
                            continue;
                        }

                        if ($char === '\\') {
                            $this->escape = true;
                            continue;
                        }

                        if ($char === '"') {
                            $this->inString = false;
                        }

                        continue;
                    }

                    if ($char === '"') {
                        $this->inString = true;
                        continue;
                    }

                    if ($char === '{' || $char === '[') {
                        ++$this->depth;
                        continue;
                    }

                    if ($char === '}' || $char === ']') {
                        --$this->depth;
                        if ($this->depth < 0) {
                            throw new InvalidArgumentException('Le fichier Spotify contient une structure JSON invalide.');
                        }

                        if ($this->depth === 0) {
                            try {
                                $decoded = json_decode($this->objectBuffer, true, 512, JSON_THROW_ON_ERROR);
                            } catch (\JsonException $exception) {
                                throw new InvalidArgumentException('Le fichier Spotify contient un JSON invalide.', previous: $exception);
                            }

                            yield $decoded;
                            $this->objectBuffer = '';
                        }
                    }
                }
            }

            public function finish(): void
            {
                if ($this->inString || $this->escape || $this->depth !== 0 || $this->objectBuffer !== '') {
                    throw new InvalidArgumentException('Le fichier Spotify contient un JSON incomplet.');
                }

                if (!$this->arrayStarted || !$this->arrayEnded) {
                    throw new InvalidArgumentException('Le fichier Spotify contient un JSON incomplet.');
                }
            }
        };

        while (!feof($stream)) {
            $chunk = fread($stream, 8192);
            if ($chunk === false) {
                throw new InvalidArgumentException(sprintf('Le fichier "%s" est illisible dans le ZIP.', $entryName));
            }

            if ($chunk === '') {
                continue;
            }

            yield from $parser->feed($chunk);
        }

        $parser->finish();
    }
}

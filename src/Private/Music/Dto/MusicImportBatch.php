<?php

declare(strict_types=1);

namespace App\Private\Music\Dto;

final class MusicImportBatch
{
    /**
     * @param list<ParsedListeningEvent> $events
     */
    public function __construct(
        private readonly string $sourceFileName,
        private readonly int $sequence,
        private readonly array $events,
    ) {
    }

    public function getSourceFileName(): string
    {
        return $this->sourceFileName;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    /**
     * @return list<ParsedListeningEvent>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function count(): int
    {
        return count($this->events);
    }

    public function isEmpty(): bool
    {
        return $this->events === [];
    }
}

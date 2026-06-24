<?php

declare(strict_types=1);

namespace App\Private\Music\Service\Normalization;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

final class MusicNormalizationService
{
    public function normalizeText(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    public function normalizeKey(?string $value): string
    {
        $value = $this->normalizeText($value);

        return mb_strtolower($value);
    }

    public function normalizeSearch(?string $value): string
    {
        return $this->normalizeKey($value);
    }

    public function slugify(string $value): string
    {
        $value = $this->normalizeText($value);
        if ($value === '') {
            return '';
        }

        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($transliterated !== false) {
            $value = $transliterated;
        }

        $value = mb_strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? $value;
        $value = trim($value, '-');

        return $value;
    }

    public function formatDuration(?int $milliseconds): string
    {
        if ($milliseconds === null || $milliseconds < 0) {
            return '—';
        }

        if ($milliseconds === 0) {
            return '0 s';
        }

        $seconds = intdiv($milliseconds, 1000);
        $minutes = intdiv($seconds, 60);
        $hours = intdiv($minutes, 60);

        $remainingMinutes = $minutes % 60;
        $remainingSeconds = $seconds % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = sprintf('%d h', $hours);
        }

        if ($remainingMinutes > 0 || $hours > 0) {
            $parts[] = sprintf('%d min', $remainingMinutes);
        }

        if ($hours === 0 && $remainingMinutes < 5 && $remainingSeconds > 0) {
            $parts[] = sprintf('%d s', $remainingSeconds);
        }

        return implode(' ', $parts);
    }

    public function formatDurationLabel(?int $milliseconds): string
    {
        $label = $this->formatDuration($milliseconds);

        return $label === '' ? '—' : $label;
    }

    public function parseArchiveDateTime(string $value): DateTimeImmutable
    {
        $timezone = new DateTimeZone(date_default_timezone_get());
        $dateTime = DateTimeImmutable::createFromFormat('Y-m-d H:i', trim($value), $timezone);
        if ($dateTime === false) {
            throw new InvalidArgumentException(sprintf('Invalid Spotify archive datetime "%s".', $value));
        }

        return $dateTime;
    }

    public function parseDateTimeOrNull(?string $value): ?DateTimeImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return $this->parseArchiveDateTime($value);
    }

    public function formatDateTime(?DateTimeInterface $dateTime, string $format = 'd/m/Y H:i'): string
    {
        if (!$dateTime instanceof DateTimeInterface) {
            return '—';
        }

        return $dateTime->format($format);
    }

    public function buildArtistKey(string $artistName): string
    {
        return $this->normalizeKey($artistName);
    }

    public function buildTrackKey(string $artistName, string $trackName): string
    {
        return $this->normalizeKey($artistName) . '|' . $this->normalizeKey($trackName);
    }

    public function buildGenreKey(string $name): string
    {
        return $this->slugify($name);
    }
}

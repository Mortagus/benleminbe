<?php

declare(strict_types=1);

namespace App\Private\Music\Enum;

enum MusicImportSourceType: string
{
    case SpotifyArchive = 'spotify_archive';

    public function label(): string
    {
        return match ($this) {
            self::SpotifyArchive => 'Archive Spotify',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];

        foreach (self::cases() as $case) {
            $labels[$case->value] = $case->label();
        }

        return $labels;
    }

    public static function default(): self
    {
        return self::SpotifyArchive;
    }
}

<?php

declare(strict_types=1);

namespace App\Private\Music\Enum;

enum MusicImportStatus: string
{
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Duplicate = 'duplicate';

    public function label(): string
    {
        return match ($this) {
            self::Processing => 'En cours',
            self::Completed => 'Terminé',
            self::Failed => 'Échoué',
            self::Duplicate => 'Déjà importé',
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
        return self::Processing;
    }
}

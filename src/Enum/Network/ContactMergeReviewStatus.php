<?php

declare(strict_types=1);

namespace App\Enum\Network;

enum ContactMergeReviewStatus: string
{
    case Pending = 'pending';
    case Resolved = 'resolved';
    case Ignored = 'ignored';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Resolved => 'Résolu',
            self::Ignored => 'Ignoré',
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
        return self::Pending;
    }
}

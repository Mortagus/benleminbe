<?php

declare(strict_types=1);

namespace App\Enum\Network;

enum ContactPriority: string
{
    case High = 'haute';
    case Medium = 'moyenne';
    case Low = 'basse';

    public function label(): string
    {
        return match ($this) {
            self::High => 'Haute',
            self::Medium => 'Moyenne',
            self::Low => 'Basse',
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
        return self::Medium;
    }
}

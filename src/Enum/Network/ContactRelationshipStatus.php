<?php

declare(strict_types=1);

namespace App\Enum\Network;

enum ContactRelationshipStatus: string
{
    case Priority = 'prioritaire';
    case FollowUp = 'a_relancer';
    case InProgress = 'en_cours';
    case Waiting = 'en_attente';
    case Cold = 'froid';

    public function label(): string
    {
        return match ($this) {
            self::Priority => 'Prioritaire',
            self::FollowUp => 'À relancer',
            self::InProgress => 'En cours',
            self::Waiting => 'En attente',
            self::Cold => 'Froid',
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
        return self::FollowUp;
    }
}

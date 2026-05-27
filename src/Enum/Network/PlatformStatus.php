<?php

declare(strict_types=1);

namespace App\Enum\Network;

enum PlatformStatus: string
{
    case Completed = 'complet';
    case UpToDate = 'a_jour';
    case AvailabilityVisible = 'disponibilite_visible';
    case ToVerify = 'a_verifier';
    case ToEnrich = 'a_enrichir';
    case Inactive = 'inactif';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Complété',
            self::UpToDate => 'À jour',
            self::AvailabilityVisible => 'Disponibilité en place et à jour',
            self::ToVerify => 'À vérifier',
            self::ToEnrich => 'À enrichir',
            self::Inactive => 'Inactif',
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
        return self::ToEnrich;
    }
}

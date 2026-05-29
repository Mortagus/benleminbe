<?php

declare(strict_types=1);

namespace App\Enum\Network;

enum ContactImportSource: string
{
    case PhoneVCard = 'phone_vcard';
    case LinkedInConnectionsCsv = 'linkedin_connections_csv';

    public function label(): string
    {
        return match ($this) {
            self::PhoneVCard => 'Contacts du téléphone (vCard)',
            self::LinkedInConnectionsCsv => 'Connexions LinkedIn (CSV)',
        };
    }

    public function expectedExtension(): string
    {
        return match ($this) {
            self::PhoneVCard => 'vcf',
            self::LinkedInConnectionsCsv => 'csv',
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
        return self::PhoneVCard;
    }
}

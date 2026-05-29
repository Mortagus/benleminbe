<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

use App\Enum\Network\ContactImportSource;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ContactImportParser
{
    /**
     * @return list<array<string, mixed>>
     */
    public function parseUploadedFile(UploadedFile $uploadedFile, ContactImportSource $source): array
    {
        $content = file_get_contents($uploadedFile->getPathname());
        if ($content === false) {
            throw new InvalidArgumentException('Impossible de lire le fichier importé.');
        }

        return $this->parseContent($content, $source);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function parseContent(string $content, ContactImportSource $source): array
    {
        return match ($source) {
            ContactImportSource::PhoneVCard => $this->parseVCardImport($content),
            ContactImportSource::LinkedInConnectionsCsv => $this->parseLinkedInConnectionsImport($content),
        };
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseCsvImport(string $content, callable $rowMapper): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];
        if ($lines === []) {
            return [];
        }

        $headerLine = (string) array_shift($lines);
        $delimiter = substr_count($headerLine, ';') > substr_count($headerLine, ',') ? ';' : ',';
        $headers = str_getcsv($this->stripBom($headerLine), $delimiter, '"', "\\");
        if ($headers === []) {
            return [];
        }

        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $values = str_getcsv($line, $delimiter, '"', "\\");
            if ($values === []) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$this->normalizeCsvHeader((string) $header)] = $values[$index] ?? '';
            }

            $rows[] = $rowMapper($row);
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseLinkedInConnectionsImport(string $content): array
    {
        return $this->parseCsvImport($content, fn (array $row): array => $this->mapLinkedInImportedRow($row));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function parseVCardImport(string $content): array
    {
        $lines = $this->unfoldVCardLines($content);
        $rows = [];
        $currentCard = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if (strcasecmp($trimmed, 'BEGIN:VCARD') === 0) {
                $currentCard = [];
                continue;
            }

            if (strcasecmp($trimmed, 'END:VCARD') === 0) {
                if (is_array($currentCard)) {
                    $row = $this->mapVCardImportedRow($currentCard);
                    if ($row !== null) {
                        $rows[] = $row;
                    }
                }

                $currentCard = null;
                continue;
            }

            if (!is_array($currentCard) || !str_contains($line, ':')) {
                continue;
            }

            [$meta, $value] = explode(':', $line, 2);
            $property = strtoupper(preg_replace('/^item\d+\./i', '', strtok($meta, ';') ?: $meta) ?: '');
            if ($property === '') {
                continue;
            }

            $currentCard[$property][] = trim($value);
        }

        return $rows;
    }

    /**
     * @param array<string, list<string>> $card
     *
     * @return array<string, mixed>|null
     */
    private function mapVCardImportedRow(array $card): ?array
    {
        $firstName = $this->firstVCardValue($card, 'N', 1);
        $lastName = $this->firstVCardValue($card, 'N', 0);
        $fullName = $this->firstVCardValue($card, 'FN');
        $organization = $this->firstVCardValue($card, 'ORG');
        $role = $this->firstVCardValue($card, 'TITLE');
        $email = $this->firstVCardValue($card, 'EMAIL');
        $phone = $this->firstVCardValue($card, 'TEL');
        $profileUrl = $this->firstVCardValue($card, 'URL');
        $notes = $this->firstVCardValue($card, 'NOTE');
        $tags = $this->firstVCardValue($card, 'CATEGORIES');

        if ($fullName === null) {
            $fullName = trim(trim((string) $firstName) . ' ' . trim((string) $lastName));
        }

        $displayName = $this->firstNonEmpty([
            $fullName,
            $organization,
            $email,
            $phone,
        ]);

        if ($displayName === '') {
            return null;
        }

        return [
            'display_name' => $displayName,
            'first_name' => $firstName ?? '',
            'last_name' => $lastName ?? '',
            'organization' => $organization ?? '',
            'role' => $role ?? '',
            'main_channel' => $email !== null && $email !== '' ? 'email' : ($phone !== null && $phone !== '' ? 'téléphone' : ''),
            'email' => $email ?? '',
            'phone' => $phone ?? '',
            'profile_url' => $profileUrl ?? '',
            'source' => '',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
            'last_contact_at' => '',
            'next_action_at' => '',
            'next_action' => '',
            'notes' => $notes ?? '',
            'tags' => $tags ?? '',
        ];
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function mapLinkedInImportedRow(array $row): array
    {
        $firstName = $this->firstRowValue($row, ['first_name', 'first', 'prenom', 'prénom']);
        $lastName = $this->firstRowValue($row, ['last_name', 'last', 'nom_de_famille', 'nom']);
        $displayName = $this->firstRowValue($row, ['display_name', 'name', 'full_name', 'nom_complet']);
        $organization = $this->firstRowValue($row, ['organization', 'company', 'company_name', 'entreprise', 'societe', 'société']);
        $role = $this->firstRowValue($row, ['role', 'position', 'job_title', 'headline', 'poste', 'fonction']);
        $email = $this->firstRowValue($row, ['email', 'email_address', 'adresse_email', 'adresse_e_mail']);
        $phone = $this->firstRowValue($row, ['phone', 'phone_number', 'telephone', 'téléphone']);
        $profileUrl = $this->firstRowValue($row, ['profile_url', 'linkedin_url', 'url', 'profile']);
        $connectedOn = $this->firstRowValue($row, ['connected_on', 'connection_date', 'date_de_connexion', 'date_connexion']);

        if ($displayName === '') {
            $displayName = trim($firstName . ' ' . $lastName);
        }

        if ($displayName === '') {
            $displayName = $this->firstNonEmpty([
                $organization,
                $email,
                $phone,
            ]);
        }

        return [
            'display_name' => $displayName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'organization' => $organization,
            'role' => $role,
            'main_channel' => 'LinkedIn',
            'email' => $email,
            'phone' => $phone,
            'profile_url' => $profileUrl,
            'source' => '',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
            'last_contact_at' => $connectedOn,
            'next_action_at' => '',
            'next_action' => '',
            'notes' => '',
            'tags' => '',
        ];
    }

    private function normalizeCsvHeader(string $header): string
    {
        $header = $this->stripBom($header);
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $header);
        if ($transliterated !== false) {
            $header = $transliterated;
        }

        $header = strtolower($header);
        $header = preg_replace('/[^a-z0-9]+/', '_', $header) ?? $header;

        return trim($header, '_');
    }

    private function stripBom(string $value): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
    }

    /**
     * @param array<string, list<string>> $card
     */
    private function firstVCardValue(array $card, string $property, ?int $position = null): ?string
    {
        $property = strtoupper($property);
        $values = $card[$property] ?? [];
        if (!is_array($values) || $values === []) {
            return null;
        }

        if ($position !== null) {
            $entry = $values[0] ?? null;
            if (!is_string($entry) || $entry === '') {
                return null;
            }

            $parts = array_map('trim', explode(';', $entry));

            return isset($parts[$position]) ? $this->decodeVCardText($parts[$position]) : null;
        }

        foreach ($values as $value) {
            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed !== '') {
                    return $this->decodeVCardText($trimmed);
                }
            }
        }

        return null;
    }

    /**
     * @param list<string> $values
     */
    private function firstNonEmpty(array $values): string
    {
        foreach ($values as $value) {
            $value = $this->normalizeString($value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function normalizeString(mixed $value): string
    {
        return trim((string) $value);
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string> $keys
     */
    private function firstRowValue(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $row)) {
                continue;
            }

            $value = $this->normalizeString($row[$key]);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @return list<string>
     */
    private function unfoldVCardLines(string $content): array
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", $content);
        $unfolded = [];

        foreach ($lines as $line) {
            if ($line !== '' && ($line[0] === ' ' || $line[0] === "\t")) {
                $index = array_key_last($unfolded);
                if ($index !== null) {
                    $unfolded[$index] .= ltrim($line);
                }

                continue;
            }

            $unfolded[] = $line;
        }

        return $unfolded;
    }

    private function decodeVCardText(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (str_contains($value, '=')) {
            $value = quoted_printable_decode($value);
        }

        return trim(strtr($value, [
            '\\\\' => '\\',
            '\\n' => "\n",
            '\\N' => "\n",
            '\\;' => ';',
            '\\,' => ',',
        ]));
    }
}

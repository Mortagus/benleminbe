<?php

declare(strict_types=1);

namespace App\Public\Service\Dnd;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PlayerXmlImportParser
{
    public function parseUploadedFile(UploadedFile $uploadedFile): array
    {
        if (!$uploadedFile->isValid()) {
            throw new InvalidArgumentException('Le fichier XML importé est invalide.');
        }

        $content = file_get_contents($uploadedFile->getPathname());

        if ($content === false) {
            throw new InvalidArgumentException('Le fichier XML importé est illisible.');
        }

        return $this->parse($content, $uploadedFile->getClientOriginalName());
    }

    public function parse(string $xml, ?string $fileName = null): array
    {
        $document = new DOMDocument();
        $document->preserveWhiteSpace = false;
        $document->formatOutput = false;

        libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml);
        $errors = libxml_get_errors();
        libxml_clear_errors();

        if ($loaded !== true) {
            throw new InvalidArgumentException($this->buildXmlErrorMessage($errors));
        }

        $xpath = new DOMXPath($document);
        $character = $this->getCharacterNode($xpath);

        if (!($character instanceof DOMElement)) {
            throw new InvalidArgumentException('Le XML ne contient pas de bloc <character>.');
        }

        $level = $this->readInteger($xpath, '/builder/character/level');
        $dexterity = $this->readInteger($xpath, '/builder/character/dex');
        $dexterityModifier = $this->abilityModifier($dexterity);
        $baseHitPoints = $this->sumLevelHitPoints($xpath, $level);
        $armorClass = $this->readArmorClass($xpath);

        return [
            'player' => [
                'id' => $this->normalizeId($this->readText($xpath, '/builder/character/name') ?? $this->fallbackFileName($fileName)),
                'type' => 'player',
                'name' => $this->readText($xpath, '/builder/character/name') ?? 'Joueur importé',
                'armorClass' => $armorClass,
                'baseHitPoints' => $baseHitPoints,
                'currentHitPoints' => $baseHitPoints,
                'initiative' => null,
                'roll' => null,
                'initiativeModifier' => $dexterityModifier,
                'identity' => [
                    'name' => $this->readText($xpath, '/builder/character/name'),
                    'race' => $this->readText($xpath, '/builder/character/race'),
                    'className' => $this->readText($xpath, '/builder/character/class'),
                    'classPath' => $this->readText($xpath, '/builder/character/classPath'),
                    'background' => $this->readText($xpath, '/builder/character/background'),
                    'level' => $level,
                    'alignment' => $this->readInteger($xpath, '/builder/character/alignment'),
                    'age' => $this->readInteger($xpath, '/builder/character/age'),
                    'sex' => $this->readInteger($xpath, '/builder/character/sexe'),
                ],
                'abilityScores' => [
                    'strength' => $this->readInteger($xpath, '/builder/character/str'),
                    'dexterity' => $dexterity,
                    'constitution' => $this->readInteger($xpath, '/builder/character/con'),
                    'intelligence' => $this->readInteger($xpath, '/builder/character/int'),
                    'wisdom' => $this->readInteger($xpath, '/builder/character/wis'),
                    'charisma' => $this->readInteger($xpath, '/builder/character/cha'),
                ],
                'combat' => [
                    'armorClass' => $armorClass,
                    'baseHitPoints' => $baseHitPoints,
                    'currentHitPoints' => $baseHitPoints,
                    'initiative' => null,
                    'roll' => null,
                    'initiativeModifier' => $dexterityModifier,
                ],
                'presentation' => [
                    'height' => $this->readText($xpath, '/builder/character/height'),
                    'weight' => $this->readText($xpath, '/builder/character/weight'),
                    'eyes' => $this->readText($xpath, '/builder/character/eyes'),
                    'skin' => $this->readText($xpath, '/builder/character/skin'),
                    'hair' => $this->readText($xpath, '/builder/character/hair'),
                    'appearance' => $this->readText($xpath, '/builder/character/appearance'),
                ],
                'profile' => [
                    'name' => $this->readText($xpath, '/builder/character/name'),
                    'race' => $this->readText($xpath, '/builder/character/race'),
                    'className' => $this->readText($xpath, '/builder/character/class'),
                    'classPath' => $this->readText($xpath, '/builder/character/classPath'),
                    'background' => $this->readText($xpath, '/builder/character/background'),
                    'level' => $level,
                    'alignment' => $this->readInteger($xpath, '/builder/character/alignment'),
                    'age' => $this->readInteger($xpath, '/builder/character/age'),
                    'sex' => $this->readInteger($xpath, '/builder/character/sexe'),
                    'height' => $this->readText($xpath, '/builder/character/height'),
                    'weight' => $this->readText($xpath, '/builder/character/weight'),
                ],
                'proficiencies' => [
                    'skills' => [],
                    'tools' => $this->readDelimitedValues($xpath, '/builder/character/toolsProf[@id="1"]'),
                    'languages' => $this->readDelimitedValues($xpath, '/builder/character/languages[@id="0"]'),
                ],
                'spellbook' => [
                    'known' => $this->readKnownSpells($xpath),
                ],
                'equipment' => [
                    'weapons' => [],
                    'armor' => [],
                    'items' => [],
                    'currency' => [
                        'gp' => $this->readInteger($xpath, '/builder/character/gp') ?? 0,
                        'pp' => $this->readInteger($xpath, '/builder/character/pp') ?? 0,
                        'ep' => $this->readInteger($xpath, '/builder/character/ep') ?? 0,
                        'sp' => $this->readInteger($xpath, '/builder/character/sp') ?? 0,
                        'cp' => $this->readInteger($xpath, '/builder/character/cp') ?? 0,
                    ],
                ],
                'story' => [
                    'traits' => $this->readText($xpath, '/builder/character/traits'),
                    'ideals' => $this->readText($xpath, '/builder/character/ideals'),
                    'bonds' => $this->readText($xpath, '/builder/character/bonds'),
                    'flaws' => $this->readText($xpath, '/builder/character/flaws'),
                    'backstory' => $this->readText($xpath, '/builder/character/backstory'),
                    'allies' => $this->readText($xpath, '/builder/character/allies'),
                    'features' => $this->readText($xpath, '/builder/character/features'),
                    'treasure' => $this->readText($xpath, '/builder/character/treasure'),
                ],
                'source' => [
                    'format' => 'xml',
                    'origin' => 'builder',
                    'fileName' => $fileName,
                    'importedAt' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                ],
            ],
            'warnings' => $this->collectWarnings($xpath),
            'raw' => [
                'skillsProf' => $this->readIndexedValues($xpath, '/builder/character/skillsProf'),
                'toolsProf' => $this->readIndexedValues($xpath, '/builder/character/toolsProf'),
                'languages' => $this->readIndexedValues($xpath, '/builder/character/languages'),
                'knownSpells' => $this->readKnownSpells($xpath),
                'armor' => $this->readDelimitedValues($xpath, '/builder/character/armor'),
                'shield' => $this->readDelimitedValues($xpath, '/builder/character/shield'),
                'tools' => $this->readDelimitedValues($xpath, '/builder/character/tools'),
                'items' => $this->readDelimitedValues($xpath, '/builder/character/item'),
                'itemQuantities' => $this->readDelimitedValues($xpath, '/builder/character/itemQ'),
                'weapons' => $this->readDelimitedValues($xpath, '/builder/character/weapon'),
                'weaponQuantities' => $this->readDelimitedValues($xpath, '/builder/character/weaponQ'),
                'pack' => $this->readText($xpath, '/builder/character/pack'),
                'backSpe' => $this->readText($xpath, '/builder/character/backSpe'),
                'classCustom' => $this->readIndexedValues($xpath, '/builder/character/classCustom'),
                'styleCombat1' => $this->readText($xpath, '/builder/character/styleCombat1'),
                'styleCombat2' => $this->readText($xpath, '/builder/character/styleCombat2'),
                'favoredEnemy0' => $this->readText($xpath, '/builder/character/favoredEnemy0'),
                'favoredEnemy6' => $this->readText($xpath, '/builder/character/favoredEnemy6'),
                'favoredEnemy14' => $this->readText($xpath, '/builder/character/favoredEnemy14'),
            ],
        ];
    }

    private function getCharacterNode(DOMXPath $xpath): ?DOMElement
    {
        $nodes = $xpath->query('/builder/character');

        if ($nodes === false || $nodes->length === 0) {
            return null;
        }

        $node = $nodes->item(0);

        return $node instanceof DOMElement ? $node : null;
    }

    private function readText(DOMXPath $xpath, string $query): ?string
    {
        $value = trim((string) $xpath->evaluate(sprintf('string(%s)', $query)));

        return $value === '' ? null : $value;
    }

    private function readInteger(DOMXPath $xpath, string $query): ?int
    {
        $value = $this->readText($xpath, $query);

        if ($value === null || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function readDelimitedValues(DOMXPath $xpath, string $query): array
    {
        $value = $this->readText($xpath, $query);

        if ($value === null) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $part): string => trim($part),
            explode(',', $value),
        ), static fn (string $part): bool => $part !== ''));
    }

    private function readIndexedValues(DOMXPath $xpath, string $query): array
    {
        $nodes = $xpath->query($query);

        if ($nodes === false || $nodes->length === 0) {
            return [];
        }

        $values = [];

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $identifier = $node->getAttribute('id');
            $values[$identifier !== '' ? $identifier : (string) count($values)] = $this->readDelimitedNodeValues($node);
        }

        return $values;
    }

    private function readDelimitedNodeValues(DOMElement $node): array
    {
        $value = trim($node->textContent);

        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $part): string => trim($part),
            explode(',', $value),
        ), static fn (string $part): bool => $part !== ''));
    }

    private function readKnownSpells(DOMXPath $xpath): array
    {
        $nodes = $xpath->query('/builder/character/knownSpell');

        if ($nodes === false || $nodes->length === 0) {
            return [];
        }

        $spells = [];

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $name = trim($node->textContent);

            if ($name === '') {
                continue;
            }

            $spells[] = [
                'name' => $name,
                'level' => $this->normalizeNullableInteger($node->getAttribute('lvl')),
            ];
        }

        return $spells;
    }

    private function collectWarnings(DOMXPath $xpath): array
    {
        $warnings = [];

        if ($this->readText($xpath, '/builder/character/skillsProf[@id="0"]') !== null) {
            $warnings[] = 'Les compétences extraites depuis le XML restent conservées en ids bruts pour le moment.';
        }

        if ($this->readText($xpath, '/builder/character/item') !== null) {
            $warnings[] = 'Les objets et armes du XML sont conservés dans la section raw en attente de correspondances lisibles.';
        }

        return $warnings;
    }

    private function sumLevelHitPoints(DOMXPath $xpath, ?int $level): int
    {
        if ($level === null || $level < 1) {
            return 0;
        }

        $nodes = $xpath->query('/builder/character/lvl');

        if ($nodes === false || $nodes->length === 0) {
            return 0;
        }

        $total = 0;

        foreach ($nodes as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            $nodeLevel = $this->normalizeNullableInteger($node->getAttribute('lvl'));

            if ($nodeLevel === null || $nodeLevel > $level) {
                continue;
            }

            $hpBrutNodes = $node->getElementsByTagName('hp_brut');
            $hpBrut = $hpBrutNodes->length > 0 ? trim((string) $hpBrutNodes->item(0)?->textContent) : '';

            if ($hpBrut === '' || !is_numeric($hpBrut)) {
                continue;
            }

            $total += (int) $hpBrut;
        }

        return $total;
    }

    private function readArmorClass(DOMXPath $xpath): ?int
    {
        $explicitArmorClass = $this->readInteger($xpath, '/builder/character/armorClass')
            ?? $this->readInteger($xpath, '/builder/character/ca')
            ?? $this->readInteger($xpath, '/builder/character/classCustom[@id="0"]');

        if ($explicitArmorClass !== null) {
            return $explicitArmorClass;
        }

        return $this->inferArmorClassFromEquipment($xpath);
    }

    private function inferArmorClassFromEquipment(DOMXPath $xpath): ?int
    {
        $armorCode = $this->readInteger($xpath, '/builder/character/armor');
        $shieldCode = $this->readInteger($xpath, '/builder/character/shield') ?? 0;

        if ($armorCode === null) {
            return null;
        }

        $armorClass = match ($armorCode) {
            0 => 10,
            1, 2 => 11,
            3, 4 => 12,
            5 => 15,
            6, 7 => 16,
            8 => 17,
            9 => 14,
            10 => 18,
            11 => 19,
            12 => 20,
            default => null,
        };

        if ($armorClass === null) {
            return null;
        }

        return $armorClass + max(0, $shieldCode);
    }

    private function normalizeNullableInteger(string $value): ?int
    {
        $value = trim($value);

        if ($value === '' || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function abilityModifier(?int $score): int
    {
        if ($score === null) {
            return 0;
        }

        return (int) floor(($score - 10) / 2);
    }

    private function buildXmlErrorMessage(array $errors): string
    {
        if ($errors === []) {
            return 'Le XML importé est invalide.';
        }

        $messages = array_map(
            static fn (\LibXMLError $error): string => trim($error->message),
            $errors,
        );

        return 'Le XML importé est invalide : ' . implode(' ', array_filter($messages));
    }

    private function fallbackFileName(?string $fileName): string
    {
        if ($fileName === null || $fileName === '') {
            return 'joueur-importe';
        }

        return pathinfo($fileName, PATHINFO_FILENAME) ?: 'joueur-importe';
    }

    private function normalizeId(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? $value;

        return trim($value, '-') ?: 'joueur-importe';
    }
}

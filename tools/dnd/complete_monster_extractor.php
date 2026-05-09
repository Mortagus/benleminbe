<?php

$html = file_get_contents(__DIR__ . '/monsters-source.html');

$dom = new DOMDocument();
libxml_use_internal_errors(TRUE);
$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
libxml_clear_errors();

$xpath = new DOMXPath($dom);

$blocks = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' bloc ')]");

$monsters = [];

foreach ($blocks as $index => $block) {
    $nameNode = $xpath->query(".//h1", $block)->item(0);
    $typeNode = $xpath->query(".//div[contains(concat(' ', normalize-space(@class), ' '), ' type ')]", $block)->item(0);
    $redNode = $xpath->query(".//div[contains(concat(' ', normalize-space(@class), ' '), ' red ')]", $block)->item(0);

    if (!$nameNode || !$typeNode || !$redNode) {
        continue;
    }

    $name = trim($nameNode->textContent);
    $typeLine = trim($typeNode->textContent);
    $redText = normalizeText($redNode->textContent);

    $typeData = parseTypeLine($typeLine);

    $armorClass = extractIntAfterLabel($redText, 'Classe d\'armure');
    $hitPoints = extractIntAfterLabel($redText, 'Points de vie');
    $speed = extractTextBetweenLabels($redText, 'Vitesse', ['FOR']);

    $abilities = parseAbilities($xpath, $block);
    $challengeRating = extractChallengeRating($redText);

    $monsters[] = [
        'id' => $index + 1,
        'slug' => slugify($name),
        'name' => $name,
        'challenge_rating' => $challengeRating,
        'type' => $typeData['type'],
        'size' => $typeData['size'],
        'armor_class' => $armorClass,
        'hit_points' => $hitPoints,
        'speed' => $speed,
        'alignment' => $typeData['alignment'],
        'is_legendary' => FALSE,
        'abilities' => $abilities,
        'initiative_modifier' => $abilities['dex']['modifier'] ?? 0,
    ];
}

file_put_contents(
    __DIR__ . '/monsters.generated.json',
    json_encode($monsters, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
);

function normalizeText(string $text): string {
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
}

function parseTypeLine(string $line): array {
    // Exemple :
    // "Humanoïde (aarakocra) de taille M, neutre bon"

    $line = normalizeText($line);

    if (preg_match('/^(.*?) de taille ([A-Z]+), (.+)$/u', $line, $matches)) {
        return [
            'type' => trim($matches[1]),
            'size' => trim($matches[2]),
            'alignment' => trim($matches[3]),
        ];
    }

    return [
        'type' => $line,
        'size' => NULL,
        'alignment' => NULL,
    ];
}

function extractIntAfterLabel(string $text, string $label): ?int {
    $pattern = '/' . preg_quote($label, '/') . '\s+(\d+)/u';

    if (preg_match($pattern, $text, $matches)) {
        return (int) $matches[1];
    }

    return NULL;
}

function extractTextBetweenLabels(string $text, string $startLabel, array $endLabels): ?string {
    $start = mb_strpos($text, $startLabel);

    if ($start === FALSE) {
        return NULL;
    }

    $start += mb_strlen($startLabel);

    $endPositions = [];

    foreach ($endLabels as $endLabel) {
        $pos = mb_strpos($text, $endLabel, $start);

        if ($pos !== FALSE) {
            $endPositions[] = $pos;
        }
    }

    $end = $endPositions ? min($endPositions) : mb_strlen($text);

    return trim(mb_substr($text, $start, $end - $start));
}

function parseAbilities(DOMXPath $xpath, DOMNode $block): array
{
    $map = [
        'FOR' => 'str',
        'DEX' => 'dex',
        'CON' => 'con',
        'INT' => 'int',
        'SAG' => 'wis',
        'CHA' => 'cha',
    ];

    $abilities = [];

    $caracNodes = $xpath->query(
        ".//div[contains(concat(' ', normalize-space(@class), ' '), ' carac ')]",
        $block
    );

    foreach ($caracNodes as $caracNode) {
        $labelNode = $xpath->query(".//strong", $caracNode)->item(0);

        if (!$labelNode) {
            continue;
        }

        $label = trim($labelNode->textContent);
        $text = normalizeText($caracNode->textContent);

        if (!isset($map[$label])) {
            continue;
        }

        if (preg_match('/(\d+)\s*\(([+-]\d+)\)/u', $text, $matches)) {
            $abilities[$map[$label]] = [
                'score' => (int) $matches[1],
                'modifier' => (int) $matches[2],
            ];
        }
    }

    return $abilities;
}

function extractChallengeRating(string $text): ?string {
    if (preg_match('/Puissance\s+([0-9\/]+)/u', $text, $matches)) {
        return $matches[1];
    }

    return NULL;
}

function slugify(string $text): string {
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');

    return $text;
}

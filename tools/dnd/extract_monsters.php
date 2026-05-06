<?php

declare(strict_types=1);

if ($argc < 3) {
    fwrite(STDERR, "Usage: php extract_monsters.php <input.html> <output.js>\n");
    exit(1);
}

$inputPath = $argv[1];
$outputPath = $argv[2];

if (!is_file($inputPath)) {
    fwrite(STDERR, "Fichier introuvable : {$inputPath}\n");
    exit(1);
}

$html = file_get_contents($inputPath);

if ($html === false) {
    fwrite(STDERR, "Impossible de lire le fichier : {$inputPath}\n");
    exit(1);
}

libxml_use_internal_errors(true);

$dom = new DOMDocument();
$dom->loadHTML('<?xml encoding="UTF-8">' . $html);

$xpath = new DOMXPath($dom);

$rows = $xpath->query('//table[@id="liste"]/tbody/tr');

if ($rows === false) {
    fwrite(STDERR, "Impossible de trouver les lignes du tableau.\n");
    exit(1);
}

$monsters = [];
$id = 1;

foreach ($rows as $row) {
    $cells = $xpath->query('./td', $row);

    if ($cells === false || $cells->length < 13) {
        continue;
    }

    $slugInput = $xpath->query('.//input', $cells->item(0))->item(0);
    $nameLink = $xpath->query('.//a', $cells->item(1))->item(0);

    $slug = $slugInput instanceof DOMElement
        ? trim($slugInput->getAttribute('value'), " \t\n\r\0\x0B'\"")
        : '';

    $name = $nameLink !== null
        ? cleanText($nameLink->textContent)
        : cleanText($cells->item(1)->textContent);

    $challengeRating = cleanText($cells->item(4)->textContent);
    $type = cleanText($cells->item(5)->textContent);
    $size = cleanText($cells->item(6)->textContent);
    $armorClass = toIntOrNull($cells->item(7)->textContent);
    $hitPoints = toIntOrNull($cells->item(8)->textContent);
    $alignment = cleanText($cells->item(10)->textContent);
    $legendaryText = cleanText($cells->item(11)->textContent);

    $monsters[] = [
        'id' => $id++,
        'slug' => $slug,
        'name' => $name,
        'challenge_rating' => $challengeRating,
        'type' => $type,
        'size' => $size,
        'armor_class' => $armorClass,
        'hit_points' => $hitPoints,
        'alignment' => $alignment,
        'is_legendary' => $legendaryText !== '',
    ];
}

$json = json_encode(
    $monsters,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

if ($json === false) {
    fwrite(STDERR, "Erreur JSON : " . json_last_error_msg() . "\n");
    exit(1);
}

$js = <<<JS
// Generated from {$inputPath}
// Do not edit manually.

const monsterClasses = {$json};

JS;

if (file_put_contents($outputPath, $js) === false) {
    fwrite(STDERR, "Impossible d'écrire le fichier : {$outputPath}\n");
    exit(1);
}

echo count($monsters) . " monstres exportés dans {$outputPath}\n";

function cleanText(?string $value): string
{
    $value ??= '';
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('/\s+/u', ' ', $value);

    return trim($value ?? '');
}

function toIntOrNull(?string $value): ?int
{
    $value = cleanText($value);

    if ($value === '') {
        return null;
    }

    return ctype_digit($value) ? (int) $value : null;
}

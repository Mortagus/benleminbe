<?php

declare(strict_types=1);

$bestiaryPath = $argv[1] ?? dirname(__DIR__, 2) . '/assets/scripts/lab/dnd/bestiary.js';
$expectedMonsterCounts = [418, 428];
$requiredFields = [
    'id',
    'slug',
    'name',
    'challenge_rating',
    'type',
    'size',
    'armor_class',
    'hit_points',
    'speed',
    'alignment',
    'is_legendary',
    'abilities',
    'initiative_modifier',
];
$requiredAbilities = ['str', 'dex', 'con', 'int', 'wis', 'cha'];
$errors = [];

if (!is_file($bestiaryPath)) {
    fail(["Fichier bestiaire introuvable : {$bestiaryPath}"]);
}

$content = file_get_contents($bestiaryPath);

if ($content === false) {
    fail(["Impossible de lire le fichier bestiaire : {$bestiaryPath}"]);
}

if (!preg_match('/^export const bestiary = (.*);\s*$/ms', $content, $matches)) {
    fail(['Le fichier doit exporter un tableau via "export const bestiary = [...]".']);
}

$bestiary = json_decode($matches[1], true);

if (!is_array($bestiary)) {
    fail(['Le contenu exporté par bestiary doit être un tableau JSON valide.']);
}

if (!in_array(count($bestiary), $expectedMonsterCounts, true)) {
    $errors[] = sprintf(
        'Le catalogue contient %d monstres ; valeurs attendues : %s.',
        count($bestiary),
        implode(' ou ', $expectedMonsterCounts)
    );
}

$slugs = [];

foreach ($bestiary as $index => $monster) {
    $label = sprintf('Monstre #%d', $index + 1);

    if (!is_array($monster)) {
        $errors[] = "{$label} n'est pas un objet.";
        continue;
    }

    foreach ($requiredFields as $field) {
        if (!array_key_exists($field, $monster)) {
            $errors[] = "{$label} ne contient pas le champ {$field}.";
        }
    }

    $name = $monster['name'] ?? $label;
    $label = "{$label} ({$name})";

    validateNonEmptyString($monster, 'slug', $label, $errors);
    validateNonEmptyString($monster, 'name', $label, $errors);
    validateNonEmptyString($monster, 'challenge_rating', $label, $errors);
    validateNonEmptyString($monster, 'type', $label, $errors);
    validateNonEmptyString($monster, 'size', $label, $errors);
    validateNonEmptyString($monster, 'speed', $label, $errors);
    validateNonEmptyString($monster, 'alignment', $label, $errors);
    validateInteger($monster, 'id', $label, $errors);
    validateInteger($monster, 'armor_class', $label, $errors);
    validateInteger($monster, 'hit_points', $label, $errors);
    validateInteger($monster, 'initiative_modifier', $label, $errors);

    if (!is_bool($monster['is_legendary'] ?? null)) {
        $errors[] = "{$label} doit exposer is_legendary sous forme de booléen.";
    }

    if (!is_array($monster['abilities'] ?? null)) {
        $errors[] = "{$label} doit exposer abilities sous forme d'objet.";
        continue;
    }

    foreach ($requiredAbilities as $ability) {
        if (!isset($monster['abilities'][$ability]) || !is_array($monster['abilities'][$ability])) {
            $errors[] = "{$label} ne contient pas la caractéristique {$ability}.";
            continue;
        }

        validateInteger($monster['abilities'][$ability], 'score', "{$label} {$ability}", $errors);
        validateInteger($monster['abilities'][$ability], 'modifier', "{$label} {$ability}", $errors);
    }

    if (
        isset($monster['abilities']['dex']['modifier'])
        && is_int($monster['abilities']['dex']['modifier'])
        && isset($monster['initiative_modifier'])
        && $monster['initiative_modifier'] !== $monster['abilities']['dex']['modifier']
    ) {
        $errors[] = "{$label} a un initiative_modifier différent du modificateur de DEX.";
    }

    if (isset($monster['slug'])) {
        if (isset($slugs[$monster['slug']])) {
            $errors[] = "{$label} utilise un slug déjà présent : {$monster['slug']}.";
        }

        $slugs[$monster['slug']] = true;
    }
}

if ($errors !== []) {
    fail($errors);
}

echo sprintf("Bestiaire valide : %d monstres.\n", count($bestiary));

function validateNonEmptyString(array $data, string $field, string $label, array &$errors): void
{
    if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
        $errors[] = "{$label} doit exposer {$field} sous forme de chaîne non vide.";
    }
}

function validateInteger(array $data, string $field, string $label, array &$errors): void
{
    if (!isset($data[$field]) || !is_int($data[$field])) {
        $errors[] = "{$label} doit exposer {$field} sous forme d'entier.";
    }
}

function fail(array $errors): never
{
    fwrite(STDERR, "Bestiaire invalide :\n");

    foreach ($errors as $error) {
        fwrite(STDERR, "- {$error}\n");
    }

    exit(1);
}

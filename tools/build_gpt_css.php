<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$outputPath = $argv[1] ?? 'var/gpt/css-context.css';
$inputPaths = array_slice($argv, 2) ?: ['assets/styles/app.css'];

$outputFile = buildProjectPath($projectRoot, $outputPath);

$includedFiles = [];
$compiledCss = '';

foreach ($inputPaths as $inputPath) {
    $inputFile = resolveProjectPath($projectRoot, $inputPath);

    if ($inputFile === null || !is_file($inputFile)) {
        fwrite(STDERR, "CSS entry file not found: {$inputPath}\n");
        exit(1);
    }

    $compiledCss .= "/* Entry: " . relativePath($projectRoot, $inputFile) . " */\n\n";
    $compiledCss .= compileCssFile($inputFile, $projectRoot, $includedFiles);
}

$compiledCss = "/* Generated CSS context for ChatGPT.\n"
    . " * Entries: " . implode(', ', $inputPaths) . "\n"
    . " * Source files: " . count($includedFiles) . "\n"
    . " * Do not edit manually.\n"
    . " */\n\n"
    . $compiledCss;

$outputDirectory = dirname($outputFile);

if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0775, true)) {
    fwrite(STDERR, "Unable to create output directory: {$outputDirectory}\n");
    exit(1);
}

if (file_put_contents($outputFile, rtrim($compiledCss) . "\n") === false) {
    fwrite(STDERR, "Unable to write output file: {$outputFile}\n");
    exit(1);
}

echo "CSS context written to " . relativePath($projectRoot, $outputFile) . "\n";
echo count($includedFiles) . " source files included.\n";

/**
 * @param array<string, bool> $includedFiles
 */
function compileCssFile(string $filePath, string $projectRoot, array &$includedFiles): string
{
    $realPath = realpath($filePath);

    if ($realPath === false) {
        fwrite(STDERR, "Imported CSS file not found: {$filePath}\n");
        exit(1);
    }

    if (isset($includedFiles[$realPath])) {
        return '';
    }

    $includedFiles[$realPath] = true;
    $css = file_get_contents($realPath);

    if ($css === false) {
        fwrite(STDERR, "Unable to read CSS file: {$realPath}\n");
        exit(1);
    }

    $css = preg_replace_callback(
        '/@import\s+(?:url\(\s*)?([\'"]?)([^\'"\)\s;]+)\1\s*\)?[^;]*;/i',
        static function (array $matches) use ($realPath, $projectRoot, &$includedFiles): string {
            $importPath = $matches[2];

            if (preg_match('/^(?:[a-z][a-z0-9+.-]*:|\/)/i', $importPath) === 1) {
                return $matches[0];
            }

            $importedFile = realpath(dirname($realPath) . DIRECTORY_SEPARATOR . $importPath);

            if ($importedFile === false || !is_file($importedFile)) {
                fwrite(
                    STDERR,
                    "Unable to resolve CSS import {$importPath} from " . relativePath($projectRoot, $realPath) . "\n"
                );
                exit(1);
            }

            return compileCssFile($importedFile, $projectRoot, $includedFiles);
        },
        $css
    );

    if ($css === null) {
        fwrite(STDERR, "Unable to parse imports in CSS file: {$realPath}\n");
        exit(1);
    }

    return "/* ===== " . relativePath($projectRoot, $realPath) . " ===== */\n"
        . rtrim($css)
        . "\n\n";
}

function resolveProjectPath(string $projectRoot, string $path): ?string
{
    if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
        return realpath($path) ?: null;
    }

    return realpath($projectRoot . DIRECTORY_SEPARATOR . $path) ?: null;
}

function buildProjectPath(string $projectRoot, string $path): string
{
    if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
        return $path;
    }

    return $projectRoot . DIRECTORY_SEPARATOR . $path;
}

function relativePath(string $projectRoot, string $path): string
{
    $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    if (str_starts_with($path, $projectRoot)) {
        return substr($path, strlen($projectRoot));
    }

    return $path;
}

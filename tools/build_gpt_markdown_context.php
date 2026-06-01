<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$outputPath = $argv[1] ?? 'var/gpt/consolidated-markdown-context.md';
$inputPaths = array_slice($argv, 2) ?: [
    'README.md',
    'docs/documentation-index.md',
    'docs/documentation-architecture.md',
    'docs/documentation-routing.md',
    'docs/project-architecture.md',
    'docs/content-workflow.md',
    'docs/deployment-and-verification.md',
    'docs/assistant-context.md',
    'docs/private',
    'docs/lab',
    'docs/en-cours',
];

$outputFile = buildProjectPath($projectRoot, $outputPath);
$sourceFiles = expandInputPaths($projectRoot, $inputPaths);

$compiledMarkdown = "# Consolidated Markdown context for ChatGPT\n\n"
    . "Generated from " . count($sourceFiles) . " source files.\n"
    . "Do not edit manually.\n\n";

foreach ($sourceFiles as $sourceFile) {
    $content = file_get_contents($sourceFile);

    if ($content === false) {
        fwrite(STDERR, "Unable to read Markdown file: {$sourceFile}\n");
        exit(1);
    }

    $compiledMarkdown .= "## `" . relativePath($projectRoot, $sourceFile) . "`\n\n";
    $compiledMarkdown .= rtrim(str_replace("\r\n", "\n", $content)) . "\n\n";
}

$outputDirectory = dirname($outputFile);

if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0775, true)) {
    fwrite(STDERR, "Unable to create output directory: {$outputDirectory}\n");
    exit(1);
}

if (file_put_contents($outputFile, rtrim($compiledMarkdown) . "\n") === false) {
    fwrite(STDERR, "Unable to write output file: {$outputFile}\n");
    exit(1);
}

echo "Markdown context written to " . relativePath($projectRoot, $outputFile) . "\n";
echo count($sourceFiles) . " source files included.\n";

/**
 * @param list<string> $inputPaths
 *
 * @return list<string>
 */
function expandInputPaths(string $projectRoot, array $inputPaths): array
{
    $inputFiles = [];

    foreach ($inputPaths as $inputPath) {
        $resolvedInput = resolveProjectPath($projectRoot, $inputPath);

        if ($resolvedInput === null) {
            fwrite(STDERR, "Markdown input not found: {$inputPath}\n");
            exit(1);
        }

        if (is_file($resolvedInput)) {
            if (!str_ends_with($resolvedInput, '.md')) {
                fwrite(STDERR, "Markdown input is not a Markdown file: {$inputPath}\n");
                exit(1);
            }

            $inputFiles[$resolvedInput] = $resolvedInput;
            continue;
        }

        if (!is_dir($resolvedInput)) {
            fwrite(STDERR, "Markdown input is not a file or directory: {$inputPath}\n");
            exit(1);
        }

        $directoryIterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($resolvedInput, FilesystemIterator::SKIP_DOTS)
        );

        $directoryFiles = [];

        foreach ($directoryIterator as $fileInfo) {
            if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }

            $filePath = $fileInfo->getRealPath();

            if ($filePath === false || !str_ends_with($filePath, '.md')) {
                continue;
            }

            $directoryFiles[] = $filePath;
        }

        sort($directoryFiles, SORT_STRING);

        foreach ($directoryFiles as $filePath) {
            $inputFiles[$filePath] = $filePath;
        }
    }

    if ($inputFiles === []) {
        fwrite(STDERR, "No Markdown files found in inputs: " . implode(', ', $inputPaths) . "\n");
        exit(1);
    }

    return array_values($inputFiles);
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

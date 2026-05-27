<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);
$options = getopt('', [
    'api-key::',
    'base-url::',
    'help',
    'locale::',
    'output-dir::',
    'retry-count::',
    'retry-delay-ms::',
    'strategy::',
    'timeout-seconds::',
]);

if (isset($options['help'])) {
    fwrite(STDOUT, usage());
    exit(0);
}

$baseUrl = normalizeBaseUrl((string) ($options['base-url'] ?? 'https://benlemin.be'));
$locale = trim((string) ($options['locale'] ?? 'fr-FR')) ?: 'fr-FR';
$strategyOption = strtolower(trim((string) ($options['strategy'] ?? 'both')));
$outputDir = (string) ($options['output-dir'] ?? sprintf('var/audits/pagespeed/%s', date('Y-m-d')));
$apiKey = trim((string) ($options['api-key'] ?? (string) getenv('PAGESPEED_API_KEY')));
$retryCount = max(1, (int) ($options['retry-count'] ?? 2));
$retryDelayMs = max(0, (int) ($options['retry-delay-ms'] ?? 1500));
$timeoutSeconds = max(10, (int) ($options['timeout-seconds'] ?? 120));

$strategies = match ($strategyOption) {
    'mobile' => ['mobile'],
    'desktop' => ['desktop'],
    'both', '' => ['mobile', 'desktop'],
    default => fail("Invalid strategy: {$strategyOption}\n"),
};

$pages = trackedPages();
$outputDirectory = buildProjectPath($projectRoot, $outputDir);

ensureDirectory($outputDirectory);

$summary = [
    'projectRoot' => $projectRoot,
    'baseUrl' => $baseUrl,
    'locale' => $locale,
    'generatedAt' => gmdate('c'),
    'hasErrors' => false,
    'strategies' => [],
];

foreach ($strategies as $strategy) {
    $strategyDirectory = $outputDirectory . DIRECTORY_SEPARATOR . $strategy;
    ensureDirectory($strategyDirectory);
    ensureDirectory($strategyDirectory . DIRECTORY_SEPARATOR . 'errors');

    $summary['strategies'][$strategy] = [
        'reports' => [],
        'errors' => [],
    ];

    foreach ($pages as $page) {
        $pageUrl = buildPageUrl($baseUrl, $page['path']);
        $apiUrl = buildApiUrl($pageUrl, $strategy, $locale, $apiKey);
        $fetchResult = fetchUrl($apiUrl, $retryCount, $retryDelayMs, $timeoutSeconds);
        $filePath = $strategyDirectory . DIRECTORY_SEPARATOR . $page['slug'] . '.json';

        if ($fetchResult['statusCode'] < 200 || $fetchResult['statusCode'] >= 300) {
            $errorArtifactPath = $strategyDirectory . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . $page['slug'] . '.txt';
            $errorArtifactContent = $fetchResult['responseBody'] !== ''
                ? $fetchResult['responseBody']
                : errorMessageFromFetchResult($fetchResult);

            if (file_put_contents($errorArtifactPath, rtrim($errorArtifactContent) . "\n") === false) {
                fail("Unable to write error artifact: {$errorArtifactPath}\n");
            }

            $summary['strategies'][$strategy]['errors'][] = buildErrorRow(
                $page,
                $pageUrl,
                $strategy,
                $fetchResult,
                $errorArtifactPath
            );
            $summary['hasErrors'] = true;
            continue;
        }

        try {
            $decoded = json_decode($fetchResult['responseBody'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $errorArtifactPath = $strategyDirectory . DIRECTORY_SEPARATOR . 'errors' . DIRECTORY_SEPARATOR . $page['slug'] . '.txt';
            $errorArtifactContent = $fetchResult['responseBody'] !== ''
                ? $fetchResult['responseBody']
                : 'Unable to decode PageSpeed JSON.';

            if (file_put_contents($errorArtifactPath, rtrim($errorArtifactContent) . "\n") === false) {
                fail("Unable to write error artifact: {$errorArtifactPath}\n");
            }

            $summary['strategies'][$strategy]['errors'][] = buildErrorRow(
                $page,
                $pageUrl,
                $strategy,
                [
                    'attempts' => $fetchResult['attempts'],
                    'errorMessage' => 'Unable to decode PageSpeed JSON: ' . $exception->getMessage(),
                    'headers' => $fetchResult['headers'],
                    'responseBody' => $fetchResult['responseBody'],
                    'statusCode' => $fetchResult['statusCode'],
                    'transportError' => $fetchResult['transportError'],
                ],
                $errorArtifactPath
            );
            $summary['hasErrors'] = true;
            continue;
        }

        if (file_put_contents($filePath, $fetchResult['responseBody']) === false) {
            fail("Unable to write JSON report: {$filePath}\n");
        }

        $summary['strategies'][$strategy]['reports'][] = buildSummaryRow($page, $pageUrl, $strategy, $decoded, $filePath);
    }
}

$summaryMarkdown = buildSummaryMarkdown($summary);
$summaryJson = json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

if ($summaryJson === false) {
    fail("Unable to encode summary JSON.\n");
}

if (file_put_contents($outputDirectory . DIRECTORY_SEPARATOR . 'index.md', $summaryMarkdown) === false) {
    fail("Unable to write summary Markdown.\n");
}

if (file_put_contents($outputDirectory . DIRECTORY_SEPARATOR . 'index.json', $summaryJson . "\n") === false) {
    fail("Unable to write summary JSON.\n");
}

fwrite(STDOUT, "PageSpeed reports written to " . relativePath($projectRoot, $outputDirectory) . "\n");
fwrite(STDOUT, "Strategies: " . implode(', ', $strategies) . "\n");

if (($summary['hasErrors'] ?? false) === true) {
    fwrite(STDERR, "One or more PageSpeed requests failed. The partial baseline has still been written.\n");
    exit(1);
}

/**
 * @return list<array{slug:string,label:string,path:string}>
 */
function trackedPages(): array
{
    return [
        ['slug' => 'home-fr', 'label' => 'Accueil FR', 'path' => '/fr'],
        ['slug' => 'projects-fr', 'label' => 'Projets FR', 'path' => '/fr/projects'],
        ['slug' => 'project-delcampe-fr', 'label' => 'Fiche projet Delcampe FR', 'path' => '/fr/projects/delcampe'],
        ['slug' => 'experiences-fr', 'label' => 'Experiences FR', 'path' => '/fr/experiences'],
        ['slug' => 'skills-fr', 'label' => 'Competences FR', 'path' => '/fr/skills'],
        ['slug' => 'contact-fr', 'label' => 'Contact FR', 'path' => '/fr/contact'],
    ];
}

function usage(): string
{
    return <<<'TXT'
Usage:
  php tools/pagespeed/collect_pagespeed.php [--base-url=URL] [--strategy=mobile|desktop|both] [--locale=fr-FR] [--output-dir=PATH] [--api-key=KEY] [--retry-count=N] [--retry-delay-ms=N] [--timeout-seconds=N]

Defaults:
  --base-url=https://benlemin.be
  --strategy=both
  --locale=fr-FR
  --output-dir=var/audits/pagespeed/YYYY-MM-DD
  --api-key=\$PAGESPEED_API_KEY
  --retry-count=2
  --retry-delay-ms=1500
  --timeout-seconds=120

TXT;
}

function normalizeBaseUrl(string $baseUrl): string
{
    $baseUrl = trim($baseUrl);

    if ($baseUrl === '') {
        fail("Base URL cannot be empty.\n");
    }

    return rtrim($baseUrl, '/');
}

function buildPageUrl(string $baseUrl, string $path): string
{
    return $baseUrl . '/' . ltrim($path, '/');
}

function buildApiUrl(string $pageUrl, string $strategy, string $locale, string $apiKey): string
{
    $query = [
        'category' => 'performance',
        'locale' => $locale,
        'strategy' => $strategy,
        'url' => $pageUrl,
    ];

    if ($apiKey !== '') {
        $query['key'] = $apiKey;
    }

    return 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}

function shouldRetryStatus(int $statusCode, ?int $retryAfterSeconds): bool
{
    if ($statusCode === 0) {
        return true;
    }

    if ($statusCode >= 500 && $statusCode <= 599) {
        return true;
    }

    if ($statusCode === 429 && $retryAfterSeconds !== null && $retryAfterSeconds <= 60) {
        return true;
    }

    return false;
}

/**
 * @param list<string> $headers
 */
function extractRetryAfterSeconds(array $headers): ?int
{
    $value = extractHeaderValue($headers, 'Retry-After');

    if ($value === null || $value === '') {
        return null;
    }

    if (ctype_digit($value)) {
        return (int) $value;
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return null;
    }

    $delta = $timestamp - time();

    return $delta > 0 ? $delta : 0;
}

/**
 * @param list<string> $headers
 */
function extractHeaderValue(array $headers, string $headerName): ?string
{
    foreach ($headers as $headerLine) {
        if (stripos($headerLine, $headerName . ':') !== 0) {
            continue;
        }

        $value = trim(substr($headerLine, strlen($headerName) + 1));

        return $value !== '' ? $value : null;
    }

    return null;
}

/**
 * @param array<string, mixed> $result
 */
function errorMessageFromFetchResult(array $result): string
{
    if (isset($result['errorMessage']) && is_string($result['errorMessage']) && $result['errorMessage'] !== '') {
        return $result['errorMessage'];
    }

    if (isset($result['transportError']) && is_string($result['transportError']) && $result['transportError'] !== '') {
        return $result['transportError'];
    }

    $responseBody = isset($result['responseBody']) && is_string($result['responseBody']) ? $result['responseBody'] : '';

    if ($responseBody !== '') {
        $decoded = json_decode($responseBody, true);

        if (is_array($decoded) && isset($decoded['error'])) {
            $error = $decoded['error'];

            if (is_array($error) && isset($error['message']) && is_string($error['message']) && $error['message'] !== '') {
                return $error['message'];
            }

            if (is_string($error) && $error !== '') {
                return $error;
            }
        }

        $snippet = trim((string) preg_replace('/\s+/', ' ', $responseBody));

        if ($snippet !== '') {
            return truncateText($snippet, 240);
        }
    }

    $statusCode = isset($result['statusCode']) && is_numeric($result['statusCode'])
        ? (int) $result['statusCode']
        : 0;

    if ($statusCode > 0) {
        return 'HTTP ' . $statusCode;
    }

    return 'Unknown PageSpeed error.';
}

function truncateText(string $text, int $length): string
{
    if ($length < 1 || strlen($text) <= $length) {
        return $text;
    }

    return substr($text, 0, max(0, $length - 1)) . '…';
}

/**
 * @return array{
 *     attempts:int,
 *     headers:list<string>,
 *     responseBody:string,
 *     statusCode:int,
 *     transportError:?string
 * }
 */
function fetchUrl(string $url, int $maxAttempts, int $retryDelayMs, int $timeoutSeconds): array
{
    $attempt = 0;
    $lastResult = [
        'attempts' => 0,
        'headers' => [],
        'responseBody' => '',
        'statusCode' => 0,
        'transportError' => null,
    ];

    while ($attempt < $maxAttempts) {
        $attempt++;
        $result = fetchUrlOnce($url, $timeoutSeconds);
        $result['attempts'] = $attempt;
        $lastResult = $result;

        if ($result['transportError'] !== null) {
            if ($attempt < $maxAttempts) {
                fwrite(STDERR, "PageSpeed request failed for {$url}: {$result['transportError']} Retrying...\n");
                usleep(($retryDelayMs * $attempt) * 1000);
                continue;
            }

            return $result;
        }

        if ($result['statusCode'] >= 200 && $result['statusCode'] < 300) {
            return $result;
        }

        $retryAfterSeconds = extractRetryAfterSeconds($result['headers']);
        $shouldRetry = shouldRetryStatus($result['statusCode'], $retryAfterSeconds);

        if (!$shouldRetry || $attempt >= $maxAttempts) {
            return $result;
        }

        $delayMs = $retryAfterSeconds !== null
            ? max(1000, $retryAfterSeconds * 1000)
            : $retryDelayMs * $attempt;

        fwrite(
            STDERR,
            sprintf(
                "PageSpeed request returned HTTP %d for %s. Retrying in %d ms (%d/%d).\n",
                $result['statusCode'],
                $url,
                $delayMs,
                $attempt,
                $maxAttempts
            )
        );
        usleep($delayMs * 1000);
    }

    return $lastResult;
}

/**
 * @return array{
 *     headers:list<string>,
 *     responseBody:string,
 *     statusCode:int,
 *     transportError:?string
 * }
 */
function fetchUrlOnce(string $url, int $timeoutSeconds): array
{
    $headers = [];

    if (function_exists('curl_init')) {
        $handle = curl_init($url);

        if ($handle === false) {
            return [
                'headers' => [],
                'responseBody' => '',
                'statusCode' => 0,
                'transportError' => 'Unable to initialize cURL.',
            ];
        }

        curl_setopt_array($handle, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => min(30, $timeoutSeconds),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: benleminbe-pagespeed/1.0',
            ],
            CURLOPT_HEADERFUNCTION => static function ($handle, string $headerLine) use (&$headers): int {
                $trimmedHeader = trim($headerLine);

                if ($trimmedHeader !== '') {
                    $headers[] = $trimmedHeader;
                }

                return strlen($headerLine);
            },
        ]);

        $responseBody = curl_exec($handle);

        if ($responseBody === false) {
            $error = curl_error($handle);
            $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            curl_close($handle);

            return [
                'headers' => $headers,
                'responseBody' => '',
                'statusCode' => $statusCode,
                'transportError' => $error !== '' ? $error : 'Unknown cURL error.',
            ];
        }

        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        return [
            'headers' => $headers,
            'responseBody' => (string) $responseBody,
            'statusCode' => $statusCode,
            'transportError' => null,
        ];
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeoutSeconds,
            'ignore_errors' => true,
            'header' => implode("\r\n", [
                'Accept: application/json',
                'User-Agent: benleminbe-pagespeed/1.0',
            ]),
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $responseBody = @file_get_contents($url, false, $context);

    if ($responseBody === false) {
        return [
            'headers' => [],
            'responseBody' => '',
            'statusCode' => 0,
            'transportError' => 'Unable to fetch PageSpeed data using file_get_contents().',
        ];
    }

    $statusCode = 0;

    foreach (($http_response_header ?? []) as $headerLine) {
        $headers[] = trim((string) $headerLine);

        if (preg_match('/^HTTP\/\S+\s+(\d{3})\b/', $headerLine, $matches) === 1) {
            $statusCode = (int) $matches[1];
        }
    }

    return [
        'headers' => $headers,
        'responseBody' => (string) $responseBody,
        'statusCode' => $statusCode,
        'transportError' => null,
    ];
}

/**
 * @param array<string, mixed> $decoded
 *
 * @return array<string, mixed>
 */
function buildSummaryRow(array $page, string $pageUrl, string $strategy, array $decoded, string $filePath): array
{
    $lighthouseResult = $decoded['lighthouseResult'] ?? [];

    return [
        'status' => 'ok',
        'label' => $page['label'],
        'path' => $page['path'],
        'url' => $pageUrl,
        'strategy' => $strategy,
        'analysisUTCTimestamp' => $decoded['analysisUTCTimestamp'] ?? null,
        'filePath' => $filePath,
        'fieldPageCategory' => $decoded['loadingExperience']['overall_category'] ?? null,
        'fieldPageFallback' => $decoded['loadingExperience']['origin_fallback'] ?? null,
        'fieldOriginCategory' => $decoded['originLoadingExperience']['overall_category'] ?? null,
        'performanceScore' => scorePercent($lighthouseResult['categories']['performance']['score'] ?? null),
        'firstContentfulPaint' => auditDisplayValue($lighthouseResult, 'first-contentful-paint'),
        'largestContentfulPaint' => auditDisplayValue($lighthouseResult, 'largest-contentful-paint'),
        'cumulativeLayoutShift' => auditDisplayValue($lighthouseResult, 'cumulative-layout-shift'),
        'totalBlockingTime' => auditDisplayValue($lighthouseResult, 'total-blocking-time'),
    ];
}

/**
 * @param array<string, mixed> $result
 *
 * @return array<string, mixed>
 */
function buildErrorRow(array $page, string $pageUrl, string $strategy, array $result, string $artifactPath): array
{
    return [
        'status' => 'error',
        'label' => $page['label'],
        'path' => $page['path'],
        'url' => $pageUrl,
        'strategy' => $strategy,
        'attempts' => $result['attempts'] ?? 0,
        'httpStatus' => $result['statusCode'] ?? 0,
        'errorMessage' => errorMessageFromFetchResult($result),
        'artifactPath' => $artifactPath,
    ];
}

/**
 * @param array<string, mixed> $summary
 */
function buildSummaryMarkdown(array $summary): string
{
    $lines = [];
    $lines[] = '# PageSpeed Audit Summary';
    $lines[] = '';
    $lines[] = 'Generated at: ' . ($summary['generatedAt'] ?? 'N/A');
    $lines[] = 'Base URL: ' . ($summary['baseUrl'] ?? 'N/A');
    $lines[] = 'Locale: ' . ($summary['locale'] ?? 'N/A');
    $lines[] = '';
    $lines[] = 'Artifacts are stored under `var/audits/pagespeed/`.';
    $lines[] = '';

    foreach (($summary['strategies'] ?? []) as $strategy => $rows) {
        $lines[] = '## ' . strtoupper((string) $strategy);
        $lines[] = '';
        $reports = $rows['reports'] ?? [];
        $errors = $rows['errors'] ?? [];

        if ($reports !== []) {
            $lines[] = '### Successful Reports';
            $lines[] = '';
            $lines[] = '| Page | Field page | Field origin | Fallback | Lab perf | FCP | LCP | CLS | TBT | Artifact |';
            $lines[] = '| --- | --- | --- | --- | ---: | --- | --- | --- | --- | --- |';

            foreach ($reports as $row) {
                $lines[] = '| ' . implode(' | ', [
                    markdownCell((string) ($row['label'] ?? 'N/A')),
                    markdownCell(formatFieldCategory($row['fieldPageCategory'] ?? null)),
                    markdownCell(formatFieldCategory($row['fieldOriginCategory'] ?? null)),
                    markdownCell(boolToText($row['fieldPageFallback'] ?? null)),
                    markdownCell((string) ($row['performanceScore'] ?? 'N/A')),
                    markdownCell((string) ($row['firstContentfulPaint'] ?? 'N/A')),
                    markdownCell((string) ($row['largestContentfulPaint'] ?? 'N/A')),
                    markdownCell((string) ($row['cumulativeLayoutShift'] ?? 'N/A')),
                    markdownCell((string) ($row['totalBlockingTime'] ?? 'N/A')),
                    markdownCell(relativePath((string) ($summary['projectRoot'] ?? ''), (string) ($row['filePath'] ?? ''))),
                ]) . ' |';
            }

            $lines[] = '';
        } else {
            $lines[] = '### Successful Reports';
            $lines[] = '';
            $lines[] = '_No successful report for this strategy._';
            $lines[] = '';
        }

        if ($errors !== []) {
            $lines[] = '### Errors';
            $lines[] = '';
            $lines[] = '| Page | HTTP | Attempts | Message | Artifact |';
            $lines[] = '| --- | ---: | ---: | --- | --- |';

            foreach ($errors as $row) {
                $lines[] = '| ' . implode(' | ', [
                    markdownCell((string) ($row['label'] ?? 'N/A')),
                    markdownCell((string) ($row['httpStatus'] ?? 0)),
                    markdownCell((string) ($row['attempts'] ?? 0)),
                    markdownCell((string) ($row['errorMessage'] ?? 'N/A')),
                    markdownCell(relativePath((string) ($summary['projectRoot'] ?? ''), (string) ($row['artifactPath'] ?? ''))),
                ]) . ' |';
            }

            $lines[] = '';
        }
    }

    $lines[] = '## Reading Notes';
    $lines[] = '';
    $lines[] = '- `Field page` uses `loadingExperience`. When the page does not have enough data, PSI may fall back to `originLoadingExperience`.';
    $lines[] = '- `Field origin` remains useful when page-level data is missing or sparse.';
    $lines[] = '- `Lab perf` comes from the Lighthouse run embedded in PSI and is the value closest to the local debugging workflow.';
    $lines[] = '- Keep the last known summary as a baseline and look for trend changes, not single-run noise.';
    $lines[] = '- `No Data` on field data means the production origin or URL does not yet have enough CrUX data in the current window.';

    return implode("\n", $lines) . "\n";
}

function auditDisplayValue(array $lighthouseResult, string $auditId): ?string
{
    $value = $lighthouseResult['audits'][$auditId]['displayValue'] ?? null;

    return is_string($value) && $value !== '' ? $value : null;
}

/**
 * @param float|int|string|null $score
 */
function scorePercent(float|int|string|null $score): ?int
{
    if ($score === null || $score === '') {
        return null;
    }

    if (!is_numeric($score)) {
        return null;
    }

    return (int) round(((float) $score) * 100);
}

function formatFieldCategory(mixed $category): string
{
    if (!is_string($category) || $category === '') {
        return 'N/A';
    }

    return strtoupper($category);
}

function boolToText(mixed $value): string
{
    if ($value === true) {
        return 'yes';
    }

    if ($value === false) {
        return 'no';
    }

    return 'N/A';
}

function markdownCell(string $value): string
{
    return str_replace(['|', "\n"], ['\\|', ' '], $value);
}

function ensureDirectory(string $directory): void
{
    if (is_dir($directory)) {
        return;
    }

    if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
        fail("Unable to create directory: {$directory}\n");
    }
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

    if ($projectRoot !== DIRECTORY_SEPARATOR && str_starts_with($path, $projectRoot)) {
        return substr($path, strlen($projectRoot));
    }

    return $path;
}

function fail(string $message): never
{
    fwrite(STDERR, $message);
    exit(1);
}

<?php

declare(strict_types=1);

namespace App\Private\Security\Service;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;

final readonly class PasskeyCeremonyLogger
{
    public function __construct(
        private string $logFile,
        private bool $enabled,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function log(Request $request, string $event, array $context = []): void
    {
        if (! $this->enabled) {
            return;
        }

        $record = [
            'time' => (new DateTimeImmutable())->format(DATE_ATOM),
            'event' => $event,
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'scheme' => $request->getScheme(),
            'host' => $request->getHost(),
            'is_secure' => $request->isSecure(),
            'origin_header' => $request->headers->get('Origin'),
            'referer_host' => $this->extractHost($request->headers->get('Referer')),
            'content_type' => $request->headers->get('Content-Type'),
            'user_agent' => $request->headers->get('User-Agent'),
            'has_session' => $request->hasSession(),
            'session_started' => $request->hasSession() && $request->getSession()->isStarted(),
            'context' => $this->normalizeContext($context),
        ];

        $directory = dirname($this->logFile);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents(
            $this->logFile,
            json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND | LOCK_EX,
        );
    }

    private function extractHost(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? $host : null;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    private function normalizeContext(array $context): array
    {
        $normalized = [];

        foreach ($context as $key => $value) {
            $normalized[$key] = match (true) {
                $value instanceof \Throwable => [
                    'class' => $value::class,
                    'message' => $value->getMessage(),
                    'code' => $value->getCode(),
                    'file' => basename($value->getFile()),
                    'line' => $value->getLine(),
                ],
                is_scalar($value) || $value === null => $value,
                is_array($value) => $this->normalizeContext($value),
                default => $value::class,
            };
        }

        return $normalized;
    }
}

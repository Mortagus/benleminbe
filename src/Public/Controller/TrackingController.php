<?php

namespace App\Public\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class TrackingController {
    #[Route('/track/cv-download', name: 'track_cv_download', methods: ['POST'])]
    public function cvDownload(Request $request): JsonResponse {
        $logLine = sprintf(
            "[%s] cv_download locale=%s referer=%s user_agent=%s\n",
            new \DateTimeImmutable()->format(\DateTimeInterface::ATOM),
            $request->headers->get('referer') ? basename(parse_url($request->headers->get('referer'), PHP_URL_PATH) ?: '') : 'unknown',
            $request->headers->get('referer', 'unknown'),
            $request->headers->get('user-agent', 'unknown'),
        );

        $logDir = dirname(__DIR__, 2) . '/var/log';

        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        file_put_contents(
            $logDir . '/cv-downloads.log',
            $logLine,
            FILE_APPEND | LOCK_EX
        );

        return new JsonResponse(NULL, 204);
    }
}

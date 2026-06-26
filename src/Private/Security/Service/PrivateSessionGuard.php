<?php

declare(strict_types=1);

namespace App\Private\Security\Service;

use Psr\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final readonly class PrivateSessionGuard
{
    private const AUTHENTICATED_AT_KEY = 'private_authenticated_at';
    private const LAST_ACTIVITY_AT_KEY = 'private_last_activity_at';
    private const IDLE_LIMIT_SECONDS = 1800;
    private const ABSOLUTE_LIMIT_SECONDS = 43200;

    public function __construct(
        private ClockInterface $clock,
    ) {
    }

    public function markAuthenticated(SessionInterface $session): void
    {
        $now = $this->clock->now()->getTimestamp();
        $session->set(self::AUTHENTICATED_AT_KEY, $now);
        $session->set(self::LAST_ACTIVITY_AT_KEY, $now);
    }

    public function touch(SessionInterface $session): void
    {
        $session->set(self::LAST_ACTIVITY_AT_KEY, $this->clock->now()->getTimestamp());
    }

    public function isExpired(SessionInterface $session): bool
    {
        $authenticatedAt = $session->get(self::AUTHENTICATED_AT_KEY);
        $lastActivityAt = $session->get(self::LAST_ACTIVITY_AT_KEY);

        if (! is_int($authenticatedAt) || ! is_int($lastActivityAt)) {
            return false;
        }

        $now = $this->clock->now()->getTimestamp();

        return ($now - $authenticatedAt) >= self::ABSOLUTE_LIMIT_SECONDS
            || ($now - $lastActivityAt) >= self::IDLE_LIMIT_SECONDS;
    }

    public function clear(SessionInterface $session): void
    {
        $session->remove(self::AUTHENTICATED_AT_KEY);
        $session->remove(self::LAST_ACTIVITY_AT_KEY);
    }
}

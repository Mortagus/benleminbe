<?php

declare(strict_types=1);

namespace App\Tests\Unit\Private\Security;

use App\Private\Security\Service\PrivateSessionGuard;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;

final class PrivateSessionGuardTest extends TestCase
{
    public function testGuardMarksAndExpiresByInactivityAndAbsoluteDuration(): void
    {
        $clock = new MockClock('2026-06-25 12:00:00');
        $guard = new PrivateSessionGuard($clock);
        $session = new Session(new MockArraySessionStorage());

        $guard->markAuthenticated($session);
        self::assertFalse($guard->isExpired($session));

        $clock->modify('+29 minutes');
        self::assertFalse($guard->isExpired($session));

        $clock->modify('+2 minutes');
        self::assertTrue($guard->isExpired($session));
    }

    public function testGuardTouchesActivityWithoutResettingTheAbsoluteLifetime(): void
    {
        $clock = new MockClock('2026-06-25 12:00:00');
        $guard = new PrivateSessionGuard($clock);
        $session = new Session(new MockArraySessionStorage());

        $guard->markAuthenticated($session);
        $clock->modify('+11 hours 50 minutes');
        $guard->touch($session);
        self::assertFalse($guard->isExpired($session));

        $clock->modify('+13 minutes');
        self::assertTrue($guard->isExpired($session));
    }
}

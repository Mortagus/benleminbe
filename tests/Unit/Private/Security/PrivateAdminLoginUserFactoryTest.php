<?php

declare(strict_types=1);

namespace App\Tests\Unit\Private\Security;

use App\Private\Security\Service\PrivateAdminLoginUserFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\InMemoryUserProvider;

final class PrivateAdminLoginUserFactoryTest extends TestCase
{
    public function testItCreatesAUserCompatibleWithTheMemoryProvider(): void
    {
        $factory = new PrivateAdminLoginUserFactory('private-dev-password-hash');
        $user = $factory->create();

        self::assertSame('private_admin', $user->getUserIdentifier());
        self::assertSame('private-dev-password-hash', $user->getPassword());
        self::assertSame(['ROLE_PRIVATE_ADMIN'], $user->getRoles());

        $provider = new InMemoryUserProvider([
            'private_admin' => [
                'password' => 'private-dev-password-hash',
                'roles' => ['ROLE_PRIVATE_ADMIN'],
            ],
        ]);

        $refreshedUser = $provider->refreshUser($user);
        self::assertTrue($user->isEqualTo($refreshedUser));
        self::assertFalse((new InMemoryUser('private_admin', null, ['ROLE_PRIVATE_ADMIN']))->isEqualTo($refreshedUser));
    }
}

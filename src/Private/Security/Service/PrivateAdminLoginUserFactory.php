<?php

declare(strict_types=1);

namespace App\Private\Security\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\User\InMemoryUser;

final readonly class PrivateAdminLoginUserFactory
{
    private const USER_IDENTIFIER = 'private_admin';
    private const ROLE_PRIVATE_ADMIN = 'ROLE_PRIVATE_ADMIN';

    public function __construct(
        #[Autowire('%env(PRIVATE_ADMIN_PASSWORD_HASH)%')]
        private string $passwordHash,
    ) {
    }

    public function create(): InMemoryUser
    {
        return new InMemoryUser(
            self::USER_IDENTIFIER,
            $this->passwordHash,
            [self::ROLE_PRIVATE_ADMIN],
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Private\Security\Repository;

use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepositoryInterface;
use Webauthn\PublicKeyCredentialUserEntity;

final class PrivateAdminWebauthnUserRepository implements PublicKeyCredentialUserEntityRepositoryInterface
{
    private const USERNAME = 'private_admin';
    private const USER_HANDLE = 'benlemin-private-admin';
    private const DISPLAY_NAME = 'Administrateur prive';

    public function findOneByUsername(string $username): ?PublicKeyCredentialUserEntity
    {
        if ($username !== self::USERNAME) {
            return null;
        }

        return PublicKeyCredentialUserEntity::create(self::USERNAME, self::USER_HANDLE, self::DISPLAY_NAME);
    }

    public function findOneByUserHandle(string $userHandle): ?PublicKeyCredentialUserEntity
    {
        if ($userHandle !== self::USER_HANDLE) {
            return null;
        }

        return PublicKeyCredentialUserEntity::create(self::USERNAME, self::USER_HANDLE, self::DISPLAY_NAME);
    }
}

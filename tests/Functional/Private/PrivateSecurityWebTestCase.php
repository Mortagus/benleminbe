<?php

declare(strict_types=1);

namespace App\Tests\Functional\Private;

use Doctrine\DBAL\Connection;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class PrivateSecurityWebTestCase extends WebTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();

        $connection = self::getContainer()->get('doctrine')->getConnection();
        $this->assertTestDatabaseIsReachable($connection);
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('TRUNCATE TABLE private_passkey_credentials');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        self::getContainer()->get('cache.rate_limiter')->clear();
        self::ensureKernelShutdown();
    }

    private function assertTestDatabaseIsReachable(Connection $connection): void
    {
        try {
            $connection->executeQuery('SELECT 1');
        } catch (\Throwable $throwable) {
            throw new RuntimeException(
                "La base de donnees de test est inaccessible pour PrivateSecurityWebTest.\n"
                . "Commandes de recuperation:\n"
                . "  docker compose stop db\n"
                . "  docker compose up -d db\n"
                . "  php bin/phpunit tests/Functional/Private/PrivateSecurityWebTest.php\n"
                . 'Cause initiale: ' . $throwable->getMessage(),
                0,
                $throwable,
            );
        }
    }

    protected function createAuthenticatedClient(): KernelBrowser
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/private/login');
        $form = $crawler->filter('form.private-form')->selectButton('Se connecter')->form([
            '_username' => 'private_admin',
            '_password' => 'private-dev-password',
        ]);

        $client->submit($form);
        self::assertResponseRedirects('/private');
        $client->followRedirect();

        return $client;
    }
}

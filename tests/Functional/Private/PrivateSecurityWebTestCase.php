<?php

declare(strict_types=1);

namespace App\Tests\Functional\Private;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class PrivateSecurityWebTestCase extends WebTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();

        $connection = self::getContainer()->get('doctrine')->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('TRUNCATE TABLE private_passkey_credentials');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');

        self::getContainer()->get('cache.rate_limiter')->clear();
        self::ensureKernelShutdown();
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

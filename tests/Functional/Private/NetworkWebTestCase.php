<?php

declare(strict_types=1);

namespace App\Tests\Functional\Private;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class NetworkWebTestCase extends WebTestCase
{
    private const NETWORK_TABLES = [
        'network_interactions',
        'network_import_logs',
        'network_contacts',
        'network_platforms',
    ];

    protected function setUp(): void
    {
        self::bootKernel();

        $connection = self::getContainer()->get('doctrine')->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');

        foreach (self::NETWORK_TABLES as $table) {
            $connection->executeStatement(sprintf('TRUNCATE TABLE %s', $table));
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        self::ensureKernelShutdown();
    }

    protected function createAuthenticatedClient(): KernelBrowser
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/private/login');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'private_admin',
            '_password' => 'private-dev-password',
        ]);

        $client->submit($form);
        self::assertResponseRedirects('/private');
        $client->followRedirect();

        return $client;
    }
}

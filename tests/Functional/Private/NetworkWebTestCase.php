<?php

declare(strict_types=1);

namespace App\Tests\Functional\Private;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class NetworkWebTestCase extends WebTestCase
{
    private const NETWORK_TABLES = [
        'network_interactions',
        'network_import_logs',
        'network_contact_merge_reviews',
        'network_contacts',
        'network_platforms',
    ];

    protected function setUp(): void
    {
        self::bootKernel();

        $connection = self::getContainer()->get('doctrine')->getConnection();
        $this->ensureMergeReviewTableExists($connection);
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');

        foreach (self::NETWORK_TABLES as $table) {
            $connection->executeStatement(sprintf('TRUNCATE TABLE %s', $table));
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        self::ensureKernelShutdown();
    }

    private function ensureMergeReviewTableExists(Connection $connection): void
    {
        $connection->executeStatement('CREATE TABLE IF NOT EXISTS network_contact_merge_reviews (id VARCHAR(120) NOT NULL, fingerprint VARCHAR(255) NOT NULL, left_contact_id VARCHAR(120) DEFAULT NULL, right_contact_id VARCHAR(120) DEFAULT NULL, status VARCHAR(32) NOT NULL, score INT NOT NULL, review_score INT NOT NULL, reasons JSON NOT NULL, field_choices JSON NOT NULL, left_snapshot JSON NOT NULL, right_snapshot JSON NOT NULL, resolved_contact_id VARCHAR(120) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', reviewed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', resolved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ignored_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_network_contact_merge_reviews_fingerprint (fingerprint), INDEX IDX_7E6FEF38A4A326A2 (left_contact_id), INDEX IDX_7E6FEF3825DDBDC4 (right_contact_id), INDEX IDX_7E6FEF389A0B8A19 (resolved_contact_id), CONSTRAINT FK_7E6FEF38A4A326A2 FOREIGN KEY (left_contact_id) REFERENCES network_contacts (id) ON DELETE SET NULL, CONSTRAINT FK_7E6FEF3825DDBDC4 FOREIGN KEY (right_contact_id) REFERENCES network_contacts (id) ON DELETE SET NULL, CONSTRAINT FK_7E6FEF389A0B8A19 FOREIGN KEY (resolved_contact_id) REFERENCES network_contacts (id) ON DELETE SET NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
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

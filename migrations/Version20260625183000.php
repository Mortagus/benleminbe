<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260625183000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the private passkey credentials table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE private_passkey_credentials (id INT AUTO_INCREMENT NOT NULL, public_key_credential_id VARCHAR(512) NOT NULL, display_name VARCHAR(180) NOT NULL, user_handle VARCHAR(64) NOT NULL, type VARCHAR(32) NOT NULL, transports JSON NOT NULL, attestation_type VARCHAR(64) NOT NULL, trust_path JSON NOT NULL, aaguid VARCHAR(36) NOT NULL, credential_public_key LONGTEXT NOT NULL, counter INT NOT NULL, other_ui JSON DEFAULT NULL, backup_eligible TINYINT(1) DEFAULT NULL, backup_status TINYINT(1) DEFAULT NULL, uv_initialized TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_used_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_private_passkey_credential_id (public_key_credential_id), INDEX idx_private_passkey_user_handle (user_handle), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE private_passkey_credentials');
    }
}

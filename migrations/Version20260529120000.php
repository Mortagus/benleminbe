<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add manual merge review storage for private network contacts.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE network_contact_merge_reviews (id VARCHAR(120) NOT NULL, fingerprint VARCHAR(255) NOT NULL, left_contact_id VARCHAR(120) DEFAULT NULL, right_contact_id VARCHAR(120) DEFAULT NULL, status VARCHAR(32) NOT NULL, score INT NOT NULL, review_score INT NOT NULL, reasons JSON NOT NULL, field_choices JSON NOT NULL, left_snapshot JSON NOT NULL, right_snapshot JSON NOT NULL, resolved_contact_id VARCHAR(120) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', reviewed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', resolved_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ignored_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_network_contact_merge_reviews_fingerprint (fingerprint), INDEX IDX_7E6FEF38A4A326A2 (left_contact_id), INDEX IDX_7E6FEF3825DDBDC4 (right_contact_id), INDEX IDX_7E6FEF389A0B8A19 (resolved_contact_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE network_contact_merge_reviews ADD CONSTRAINT FK_7E6FEF38A4A326A2 FOREIGN KEY (left_contact_id) REFERENCES network_contacts (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE network_contact_merge_reviews ADD CONSTRAINT FK_7E6FEF3825DDBDC4 FOREIGN KEY (right_contact_id) REFERENCES network_contacts (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE network_contact_merge_reviews ADD CONSTRAINT FK_7E6FEF389A0B8A19 FOREIGN KEY (resolved_contact_id) REFERENCES network_contacts (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE network_contact_merge_reviews DROP FOREIGN KEY FK_7E6FEF389A0B8A19');
        $this->addSql('ALTER TABLE network_contact_merge_reviews DROP FOREIGN KEY FK_7E6FEF3825DDBDC4');
        $this->addSql('ALTER TABLE network_contact_merge_reviews DROP FOREIGN KEY FK_7E6FEF38A4A326A2');
        $this->addSql('DROP TABLE network_contact_merge_reviews');
    }
}

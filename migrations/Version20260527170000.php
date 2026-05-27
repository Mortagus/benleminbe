<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the network MVP tables and seed the default platforms.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE network_platforms (slug VARCHAR(120) NOT NULL, name VARCHAR(180) NOT NULL, category VARCHAR(80) NOT NULL, profile_url VARCHAR(2048) DEFAULT NULL, status VARCHAR(32) NOT NULL, note TEXT DEFAULT NULL, last_reviewed_at DATE DEFAULT NULL, active BOOLEAN NOT NULL, PRIMARY KEY(slug))');
        $this->addSql('CREATE TABLE network_contacts (id VARCHAR(120) NOT NULL, display_name VARCHAR(180) NOT NULL, first_name VARCHAR(180) DEFAULT NULL, last_name VARCHAR(180) DEFAULT NULL, organization VARCHAR(180) DEFAULT NULL, role VARCHAR(180) DEFAULT NULL, main_channel VARCHAR(120) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, phone VARCHAR(60) DEFAULT NULL, profile_url VARCHAR(2048) DEFAULT NULL, source VARCHAR(180) DEFAULT NULL, priority VARCHAR(32) NOT NULL, relationship_status VARCHAR(32) NOT NULL, last_contact_at DATE DEFAULT NULL, next_action_at DATE DEFAULT NULL, next_action TEXT DEFAULT NULL, notes TEXT DEFAULT NULL, tags JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE network_interactions (id VARCHAR(120) NOT NULL, contact_id VARCHAR(120) NOT NULL, date DATE NOT NULL, channel VARCHAR(120) DEFAULT NULL, summary TEXT DEFAULT NULL, result TEXT DEFAULT NULL, next_action TEXT DEFAULT NULL, next_action_at DATE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_NETWORK_INTERACTIONS_CONTACT_ID ON network_interactions (contact_id)');
        $this->addSql('ALTER TABLE network_interactions ADD CONSTRAINT FK_NETWORK_INTERACTIONS_CONTACT_ID FOREIGN KEY (contact_id) REFERENCES network_contacts (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE TABLE network_import_logs (id VARCHAR(120) NOT NULL, source_label VARCHAR(180) NOT NULL, total INT NOT NULL, created INT NOT NULL, updated INT NOT NULL, imported_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, errors JSON NOT NULL, PRIMARY KEY(id))');

        $this->addSql("INSERT INTO network_platforms (slug, name, category, profile_url, status, note, last_reviewed_at, active) VALUES ('linkedin', 'LinkedIn', 'reseau', 'https://www.linkedin.com/in/benlem/', 'a_jour', 'Profil professionnel principal.', NULL, TRUE)");
        $this->addSql("INSERT INTO network_platforms (slug, name, category, profile_url, status, note, last_reviewed_at, active) VALUES ('malt', 'Malt', 'freelance', 'https://fr.malt.be/profile/benjaminlemin', 'a_jour', 'Canal principal pour les missions freelance structurées.', NULL, TRUE)");
        $this->addSql("INSERT INTO network_platforms (slug, name, category, profile_url, status, note, last_reviewed_at, active) VALUES ('indeed', 'Indeed', 'jobboard', '', 'a_enrichir', 'À renseigner si un profil est ouvert ou à créer.', NULL, TRUE)");
        $this->addSql("INSERT INTO network_platforms (slug, name, category, profile_url, status, note, last_reviewed_at, active) VALUES ('lehibou', 'LeHibou', 'freelance', '', 'a_enrichir', 'À renseigner si un profil existe ou doit être créé.', NULL, TRUE)");
        $this->addSql("INSERT INTO network_platforms (slug, name, category, profile_url, status, note, last_reviewed_at, active) VALUES ('wiggli', 'Wiggli', 'freelance', '', 'a_enrichir', 'À renseigner si un profil existe ou doit être créé.', NULL, TRUE)");
        $this->addSql("INSERT INTO network_platforms (slug, name, category, profile_url, status, note, last_reviewed_at, active) VALUES ('superprof', 'Superprof', 'coaching', '', 'a_enrichir', 'Plateforme de coaching technique à suivre séparément.', NULL, TRUE)");
        $this->addSql("INSERT INTO network_platforms (slug, name, category, profile_url, status, note, last_reviewed_at, active) VALUES ('apprentus', 'Apprentus', 'coaching', '', 'a_enrichir', 'Plateforme de coaching technique à suivre séparément.', NULL, TRUE)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE network_interactions');
        $this->addSql('DROP TABLE network_import_logs');
        $this->addSql('DROP TABLE network_contacts');
        $this->addSql('DROP TABLE network_platforms');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260928130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove obsolete freelance platforms from the private network defaults.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("DELETE FROM network_platforms WHERE slug IN ('malt', 'lehibou', 'wiggli')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("INSERT IGNORE INTO network_platforms (slug, name, category, profile_url, status, note, last_reviewed_at, active) VALUES ('malt', 'Malt', 'freelance', 'https://fr.malt.be/profile/benjaminlemin', 'a_jour', 'Canal principal pour les missions freelance structurées.', NULL, TRUE), ('lehibou', 'LeHibou', 'freelance', '', 'a_enrichir', 'À renseigner si un profil existe ou doit être créé.', NULL, TRUE), ('wiggli', 'Wiggli', 'freelance', '', 'a_enrichir', 'À renseigner si un profil existe ou doit être créé.', NULL, TRUE)");
    }
}

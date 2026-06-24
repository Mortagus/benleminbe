<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change music listening event idempotence to be scoped by import.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE music_listening_events DROP INDEX uniq_music_listening_events_fingerprint');
        $this->addSql('ALTER TABLE music_listening_events ADD UNIQUE INDEX uniq_music_listening_events_import_fingerprint (import_id, fingerprint)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE music_listening_events DROP INDEX uniq_music_listening_events_import_fingerprint');
        $this->addSql('ALTER TABLE music_listening_events ADD UNIQUE INDEX uniq_music_listening_events_fingerprint (fingerprint)');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create private music listening history tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE music_imports (id VARCHAR(120) NOT NULL, original_filename VARCHAR(255) NOT NULL, source_type VARCHAR(80) NOT NULL, archive_checksum VARCHAR(64) NOT NULL, status VARCHAR(32) NOT NULL, imported_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', summary JSON NOT NULL, error_message LONGTEXT DEFAULT NULL, UNIQUE INDEX uniq_music_imports_archive_checksum (archive_checksum), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE music_artists (id VARCHAR(120) NOT NULL, normalized_name VARCHAR(255) NOT NULL, display_name VARCHAR(255) NOT NULL, first_played_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_played_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', listening_count INT NOT NULL, total_played_ms INT NOT NULL, UNIQUE INDEX uniq_music_artists_normalized_name (normalized_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE music_tracks (id VARCHAR(120) NOT NULL, artist_id VARCHAR(120) NOT NULL, normalized_title VARCHAR(255) NOT NULL, display_title VARCHAR(255) NOT NULL, album_name VARCHAR(255) DEFAULT NULL, spotify_uri VARCHAR(255) DEFAULT NULL, first_played_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_played_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', listening_count INT NOT NULL, total_played_ms INT NOT NULL, INDEX IDX_6119C9E67E03E2A8 (artist_id), UNIQUE INDEX uniq_music_tracks_artist_title (artist_id, normalized_title), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE music_listening_events (id VARCHAR(120) NOT NULL, import_id VARCHAR(120) NOT NULL, artist_id VARCHAR(120) NOT NULL, track_id VARCHAR(120) NOT NULL, played_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', track_name VARCHAR(255) NOT NULL, artist_name_raw VARCHAR(255) NOT NULL, artist_name_normalized VARCHAR(255) NOT NULL, album_name VARCHAR(255) DEFAULT NULL, played_duration_ms INT DEFAULT NULL, track_uri VARCHAR(255) DEFAULT NULL, source_payload_version VARCHAR(32) DEFAULT NULL, source_file_name VARCHAR(255) DEFAULT NULL, source_record_index INT NOT NULL, fingerprint VARCHAR(128) NOT NULL, raw_payload JSON NOT NULL, UNIQUE INDEX uniq_music_listening_events_fingerprint (fingerprint), INDEX IDX_C06D7C7AEA3F8B93 (import_id), INDEX IDX_C06D7C7A2604E6E8 (artist_id), INDEX IDX_C06D7C7A8D44AE9F (track_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE music_genres (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, slug VARCHAR(180) NOT NULL, UNIQUE INDEX uniq_music_genres_slug (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE music_artist_genres (id INT AUTO_INCREMENT NOT NULL, artist_id VARCHAR(120) NOT NULL, genre_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX uniq_music_artist_genres_artist_genre (artist_id, genre_id), INDEX IDX_A60B53A32604E6E8 (artist_id), INDEX IDX_A60B53A3D1A3E217 (genre_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE music_tracks ADD CONSTRAINT FK_6119C9E67E03E2A8 FOREIGN KEY (artist_id) REFERENCES music_artists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE music_listening_events ADD CONSTRAINT FK_C06D7C7AEA3F8B93 FOREIGN KEY (import_id) REFERENCES music_imports (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE music_listening_events ADD CONSTRAINT FK_C06D7C7A2604E6E8 FOREIGN KEY (artist_id) REFERENCES music_artists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE music_listening_events ADD CONSTRAINT FK_C06D7C7A8D44AE9F FOREIGN KEY (track_id) REFERENCES music_tracks (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE music_artist_genres ADD CONSTRAINT FK_A60B53A32604E6E8 FOREIGN KEY (artist_id) REFERENCES music_artists (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE music_artist_genres ADD CONSTRAINT FK_A60B53A3D1A3E217 FOREIGN KEY (genre_id) REFERENCES music_genres (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE music_artist_genres DROP FOREIGN KEY FK_A60B53A32604E6E8');
        $this->addSql('ALTER TABLE music_artist_genres DROP FOREIGN KEY FK_A60B53A3D1A3E217');
        $this->addSql('ALTER TABLE music_listening_events DROP FOREIGN KEY FK_C06D7C7AEA3F8B93');
        $this->addSql('ALTER TABLE music_listening_events DROP FOREIGN KEY FK_C06D7C7A2604E6E8');
        $this->addSql('ALTER TABLE music_listening_events DROP FOREIGN KEY FK_C06D7C7A8D44AE9F');
        $this->addSql('ALTER TABLE music_tracks DROP FOREIGN KEY FK_6119C9E67E03E2A8');
        $this->addSql('DROP TABLE music_artist_genres');
        $this->addSql('DROP TABLE music_genres');
        $this->addSql('DROP TABLE music_listening_events');
        $this->addSql('DROP TABLE music_tracks');
        $this->addSql('DROP TABLE music_artists');
        $this->addSql('DROP TABLE music_imports');
    }
}

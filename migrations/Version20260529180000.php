<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store network contact email and phone values as JSON lists.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE network_contacts CHANGE email email JSON NOT NULL, CHANGE phone phone JSON NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE network_contacts CHANGE email email VARCHAR(180) DEFAULT NULL, CHANGE phone phone VARCHAR(60) DEFAULT NULL');
    }
}

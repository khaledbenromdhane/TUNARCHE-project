<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add vendue column to oeuvre';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oeuvre ADD vendue TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oeuvre DROP vendue');
    }
}

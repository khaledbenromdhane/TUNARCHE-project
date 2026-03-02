<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image and description to oeuvre table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oeuvre ADD image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE oeuvre ADD description LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oeuvre DROP image');
        $this->addSql('ALTER TABLE oeuvre DROP description');
    }
}

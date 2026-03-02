<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222161346 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image_analysis column to publication table';
    }

    public function up(Schema $schema): void
    {
        // Add only the image_analysis column to publication table
        $this->addSql('ALTER TABLE publication ADD image_analysis LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove the image_analysis column on rollback
        $this->addSql('ALTER TABLE publication DROP COLUMN image_analysis');
    }
}

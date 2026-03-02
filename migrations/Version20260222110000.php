<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add date_vente to oeuvre for sales statistics';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oeuvre ADD date_vente DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oeuvre DROP date_vente');
    }
}

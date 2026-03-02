<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add idgalerie foreign key to oeuvre table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oeuvre ADD idgalerie INT DEFAULT NULL');
        $this->addSql('ALTER TABLE oeuvre ADD CONSTRAINT FK_oeuvre_galerie FOREIGN KEY (idgalerie) REFERENCES galerie (idgalerie)');
        $this->addSql('CREATE INDEX IDX_oeuvre_galerie ON oeuvre (idgalerie)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oeuvre DROP FOREIGN KEY FK_oeuvre_galerie');
        $this->addSql('DROP INDEX IDX_oeuvre_galerie ON oeuvre');
        $this->addSql('ALTER TABLE oeuvre DROP idgalerie');
    }
}

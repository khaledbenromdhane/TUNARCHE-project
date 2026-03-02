<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260222100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace disponible/vendue by single statut column (disponible|vendue)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oeuvre ADD statut VARCHAR(20) DEFAULT \'disponible\' NOT NULL');
        $this->addSql("UPDATE oeuvre SET statut = 'vendue' WHERE vendue = 1");
        $this->addSql("UPDATE oeuvre SET statut = 'disponible' WHERE vendue = 0");
        $this->addSql('ALTER TABLE oeuvre DROP disponible, DROP vendue');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oeuvre ADD disponible TINYINT(1) DEFAULT 1 NOT NULL, ADD vendue TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql("UPDATE oeuvre SET vendue = 1, disponible = 0 WHERE statut = 'vendue'");
        $this->addSql("UPDATE oeuvre SET vendue = 0, disponible = 1 WHERE statut = 'disponible'");
        $this->addSql('ALTER TABLE oeuvre DROP statut');
    }
}

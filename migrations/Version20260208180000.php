<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace noms_artistes with galerie_artiste ManyToMany table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE galerie DROP noms_artistes');
        $this->addSql('CREATE TABLE galerie_artiste (galerie_id_galerie INT NOT NULL, user_iduser INT NOT NULL, INDEX IDX_GA_GALERIE (galerie_id_galerie), INDEX IDX_GA_USER (user_iduser), PRIMARY KEY(galerie_id_galerie, user_iduser)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE galerie_artiste ADD CONSTRAINT FK_GA_GALERIE FOREIGN KEY (galerie_id_galerie) REFERENCES galerie (id_galerie) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE galerie_artiste ADD CONSTRAINT FK_GA_USER FOREIGN KEY (user_iduser) REFERENCES `user` (iduser) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE galerie_artiste DROP FOREIGN KEY FK_GA_GALERIE');
        $this->addSql('ALTER TABLE galerie_artiste DROP FOREIGN KEY FK_GA_USER');
        $this->addSql('DROP TABLE galerie_artiste');
        $this->addSql('ALTER TABLE galerie ADD noms_artistes LONGTEXT DEFAULT NULL');
    }
}

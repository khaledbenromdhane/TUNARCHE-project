<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user, oeuvre, galerie tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `user` (iduser INT AUTO_INCREMENT NOT NULL, nomuser VARCHAR(100) NOT NULL, prenomuser VARCHAR(100) NOT NULL, role VARCHAR(50) NOT NULL, PRIMARY KEY(iduser)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE oeuvre (id INT AUTO_INCREMENT NOT NULL, id_artiste INT NOT NULL, titre VARCHAR(200) NOT NULL, prix NUMERIC(10, 2) NOT NULL, etat VARCHAR(20) NOT NULL, annee_realisation SMALLINT NOT NULL, INDEX IDX_35FE2EFE5EAFB355 (id_artiste), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE galerie (idgalerie INT AUTO_INCREMENT NOT NULL, nom_galerie VARCHAR(150) NOT NULL, nb_oeuvres_total INT NOT NULL, nb_employes INT NOT NULL, PRIMARY KEY(idgalerie)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE galerie_artiste (galerie_idgalerie INT NOT NULL, user_iduser INT NOT NULL, INDEX IDX_GA_GALERIE (galerie_idgalerie), INDEX IDX_GA_USER (user_iduser), PRIMARY KEY(galerie_idgalerie, user_iduser)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE oeuvre ADD CONSTRAINT FK_35FE2EFE5EAFB355 FOREIGN KEY (id_artiste) REFERENCES `user` (iduser)');
        $this->addSql('ALTER TABLE galerie_artiste ADD CONSTRAINT FK_GA_GALERIE FOREIGN KEY (galerie_idgalerie) REFERENCES galerie (idgalerie) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE galerie_artiste ADD CONSTRAINT FK_GA_USER FOREIGN KEY (user_iduser) REFERENCES `user` (iduser) ON DELETE CASCADE');
        $this->addSql('INSERT INTO `user` (iduser, nomuser, prenomuser, role) VALUES (1, \'Martin\', \'Sophie\', \'artist\'), (2, \'Benali\', \'Ahmed\', \'artist\'), (3, \'Dubois\', \'Clara\', \'artist\')');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE oeuvre DROP FOREIGN KEY FK_35FE2EFE5EAFB355');
        $this->addSql('ALTER TABLE galerie_artiste DROP FOREIGN KEY FK_GA_GALERIE');
        $this->addSql('ALTER TABLE galerie_artiste DROP FOREIGN KEY FK_GA_USER');
        $this->addSql('DROP TABLE oeuvre');
        $this->addSql('DROP TABLE galerie_artiste');
        $this->addSql('DROP TABLE galerie');
        $this->addSql('DROP TABLE `user`');
    }
}

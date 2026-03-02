<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260208170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactor Galerie entity: id_galerie, categorie, nom, nb_oeuvres_dispo, noms_artistes, nb_employes';
    }

    public function up(Schema $schema): void
    {
        // 1. Supprimer la FK oeuvre -> galerie
        $this->addSql('ALTER TABLE oeuvre DROP FOREIGN KEY FK_oeuvre_galerie');
        $this->addSql('DROP INDEX IDX_oeuvre_galerie ON oeuvre');

        // 2. Supprimer la table galerie_artiste (relation ManyToMany supprimée)
        $this->addSql('ALTER TABLE galerie_artiste DROP FOREIGN KEY FK_GA_GALERIE');
        $this->addSql('ALTER TABLE galerie_artiste DROP FOREIGN KEY FK_GA_USER');
        $this->addSql('DROP TABLE galerie_artiste');

        // 3. Modifier la table galerie
        $this->addSql('ALTER TABLE galerie ADD categorie VARCHAR(100) NOT NULL DEFAULT \'Général\' AFTER idgalerie');
        $this->addSql('ALTER TABLE galerie ADD noms_artistes LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE galerie CHANGE idgalerie id_galerie INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE galerie CHANGE nom_galerie nom VARCHAR(150) NOT NULL');
        $this->addSql('ALTER TABLE galerie CHANGE nb_oeuvres_total nb_oeuvres_dispo INT NOT NULL');

        // 4. Renommer la colonne dans oeuvre et recréer la FK
        $this->addSql('ALTER TABLE oeuvre CHANGE idgalerie id_galerie INT DEFAULT NULL');
        $this->addSql('ALTER TABLE oeuvre ADD CONSTRAINT FK_oeuvre_galerie FOREIGN KEY (id_galerie) REFERENCES galerie (id_galerie)');
        $this->addSql('CREATE INDEX IDX_oeuvre_galerie ON oeuvre (id_galerie)');
    }

    public function down(Schema $schema): void
    {
        // 1. Supprimer FK oeuvre -> galerie
        $this->addSql('ALTER TABLE oeuvre DROP FOREIGN KEY FK_oeuvre_galerie');
        $this->addSql('DROP INDEX IDX_oeuvre_galerie ON oeuvre');
        $this->addSql('ALTER TABLE oeuvre CHANGE id_galerie idgalerie INT DEFAULT NULL');

        // 2. Revenir à l'ancienne structure galerie
        $this->addSql('ALTER TABLE galerie DROP categorie');
        $this->addSql('ALTER TABLE galerie DROP noms_artistes');
        $this->addSql('ALTER TABLE galerie CHANGE id_galerie idgalerie INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE galerie CHANGE nom nom_galerie VARCHAR(150) NOT NULL');
        $this->addSql('ALTER TABLE galerie CHANGE nb_oeuvres_dispo nb_oeuvres_total INT NOT NULL');

        // 3. Recréer galerie_artiste
        $this->addSql('CREATE TABLE galerie_artiste (galerie_idgalerie INT NOT NULL, user_iduser INT NOT NULL, INDEX IDX_GA_GALERIE (galerie_idgalerie), INDEX IDX_GA_USER (user_iduser), PRIMARY KEY(galerie_idgalerie, user_iduser)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE galerie_artiste ADD CONSTRAINT FK_GA_GALERIE FOREIGN KEY (galerie_idgalerie) REFERENCES galerie (idgalerie) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE galerie_artiste ADD CONSTRAINT FK_GA_USER FOREIGN KEY (user_iduser) REFERENCES `user` (iduser) ON DELETE CASCADE');

        // 4. Recréer FK oeuvre
        $this->addSql('ALTER TABLE oeuvre ADD CONSTRAINT FK_oeuvre_galerie FOREIGN KEY (idgalerie) REFERENCES galerie (idgalerie)');
        $this->addSql('CREATE INDEX IDX_oeuvre_galerie ON oeuvre (idgalerie)');
    }
}

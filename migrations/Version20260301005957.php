<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260301005957 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE galerie (id_galerie INT AUTO_INCREMENT NOT NULL, categorie VARCHAR(100) NOT NULL, nom VARCHAR(150) NOT NULL, nb_oeuvres_dispo INT NOT NULL, nb_employes INT NOT NULL, PRIMARY KEY (id_galerie)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE galerie_artiste (galerie_id_galerie INT NOT NULL, user_id_user INT NOT NULL, INDEX IDX_2BA354C4DD7521 (galerie_id_galerie), INDEX IDX_2BA354C45EBED441 (user_id_user), PRIMARY KEY (galerie_id_galerie, user_id_user)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE oeuvre (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(200) NOT NULL, prix NUMERIC(10, 2) NOT NULL, etat VARCHAR(20) NOT NULL, annee_realisation SMALLINT NOT NULL, image VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, statut VARCHAR(20) DEFAULT \'disponible\' NOT NULL, date_vente DATETIME DEFAULT NULL, id_artiste INT DEFAULT NULL, id_galerie INT DEFAULT NULL, INDEX IDX_35FE2EFE429A9C3F (id_artiste), INDEX IDX_35FE2EFE40E0BCE0 (id_galerie), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE galerie_artiste ADD CONSTRAINT FK_2BA354C4DD7521 FOREIGN KEY (galerie_id_galerie) REFERENCES galerie (id_galerie)');
        $this->addSql('ALTER TABLE galerie_artiste ADD CONSTRAINT FK_2BA354C45EBED441 FOREIGN KEY (user_id_user) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE oeuvre ADD CONSTRAINT FK_35FE2EFE429A9C3F FOREIGN KEY (id_artiste) REFERENCES user (id_user)');
        $this->addSql('ALTER TABLE oeuvre ADD CONSTRAINT FK_35FE2EFE40E0BCE0 FOREIGN KEY (id_galerie) REFERENCES galerie (id_galerie)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE galerie_artiste DROP FOREIGN KEY FK_2BA354C4DD7521');
        $this->addSql('ALTER TABLE galerie_artiste DROP FOREIGN KEY FK_2BA354C45EBED441');
        $this->addSql('ALTER TABLE oeuvre DROP FOREIGN KEY FK_35FE2EFE429A9C3F');
        $this->addSql('ALTER TABLE oeuvre DROP FOREIGN KEY FK_35FE2EFE40E0BCE0');
        $this->addSql('DROP TABLE galerie');
        $this->addSql('DROP TABLE galerie_artiste');
        $this->addSql('DROP TABLE oeuvre');
    }
}

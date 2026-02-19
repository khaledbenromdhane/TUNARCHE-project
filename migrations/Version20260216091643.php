<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260216091643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE evaluation (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, note INT NOT NULL, commentaire LONGTEXT NOT NULL, formation_id INT NOT NULL, INDEX IDX_1323A5755200282E (formation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE formation (id INT AUTO_INCREMENT NOT NULL, nom_form VARCHAR(255) NOT NULL, date_form DATE NOT NULL, type VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, question_text VARCHAR(255) NOT NULL, choice_a VARCHAR(255) NOT NULL, choice_b VARCHAR(255) NOT NULL, choice_c VARCHAR(255) NOT NULL, correct_answer VARCHAR(1) NOT NULL, quiz_id INT NOT NULL, INDEX IDX_B6F7494E853CD175 (quiz_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE quiz (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, formation_id INT NOT NULL, INDEX IDX_A412FA925200282E (formation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE resultat (id INT AUTO_INCREMENT NOT NULL, score INT NOT NULL, is_passed TINYINT NOT NULL, created_at DATETIME NOT NULL, quiz_id INT NOT NULL, INDEX IDX_E7DB5DE2853CD175 (quiz_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A5755200282E FOREIGN KEY (formation_id) REFERENCES formation (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494E853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE quiz ADD CONSTRAINT FK_A412FA925200282E FOREIGN KEY (formation_id) REFERENCES formation (id)');
        $this->addSql('ALTER TABLE resultat ADD CONSTRAINT FK_E7DB5DE2853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A5755200282E');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494E853CD175');
        $this->addSql('ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA925200282E');
        $this->addSql('ALTER TABLE resultat DROP FOREIGN KEY FK_E7DB5DE2853CD175');
        $this->addSql('DROP TABLE evaluation');
        $this->addSql('DROP TABLE formation');
        $this->addSql('DROP TABLE question');
        $this->addSql('DROP TABLE quiz');
        $this->addSql('DROP TABLE resultat');
    }
}

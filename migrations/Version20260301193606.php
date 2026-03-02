<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260301193606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_id FK to formation (admin/artist) and evaluation (user)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE formation ADD CONSTRAINT FK_404021BFA76ED395 FOREIGN KEY (user_id) REFERENCES user (id_user)');
        $this->addSql('CREATE INDEX IDX_404021BFA76ED395 ON formation (user_id)');

        $this->addSql('ALTER TABLE evaluation ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575A76ED395 FOREIGN KEY (user_id) REFERENCES user (id_user)');
        $this->addSql('CREATE INDEX IDX_1323A575A76ED395 ON evaluation (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation DROP FOREIGN KEY FK_404021BFA76ED395');
        $this->addSql('DROP INDEX IDX_404021BFA76ED395 ON formation');
        $this->addSql('ALTER TABLE formation DROP user_id');

        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575A76ED395');
        $this->addSql('DROP INDEX IDX_1323A575A76ED395 ON evaluation');
        $this->addSql('ALTER TABLE evaluation DROP user_id');
    }
}

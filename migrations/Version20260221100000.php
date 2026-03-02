<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260221100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Insert sample galeries (categories) and oeuvres';
    }

    public function up(Schema $schema): void
    {
        // Galeries (catégories)
        $this->addSql("INSERT INTO galerie (categorie, nom, nb_oeuvres_dispo, nb_employes) VALUES
            ('Peinture', 'Galerie Art Moderne', 12, 3),
            ('Sculpture', 'Galerie Sculptures Contemporaines', 8, 2),
            ('Photographie', 'Galerie Photo Tunisie', 15, 4),
            ('Art numérique', 'Galerie Digital Art', 6, 2),
            ('Mixed Media', 'Galerie Expressions', 10, 3)
        ");

        // Lier artistes aux galeries (user 1, 2, 3 existent déjà)
        $this->addSql("INSERT INTO galerie_artiste (galerie_id_galerie, user_iduser) VALUES
            (1, 1), (1, 2),
            (2, 2), (2, 3),
            (3, 1), (3, 3),
            (4, 1), (4, 2),
            (5, 1), (5, 2), (5, 3)
        ");

        // Œuvres (id_artiste 1-3, id_galerie 1-5, prix en DT)
        $this->addSql("INSERT INTO oeuvre (id_artiste, id_galerie, titre, prix, etat, annee_realisation, description, disponible) VALUES
            (1, 1, 'Soleil couchant sur la médina', 450.00, 'neuve', 2023, 'Huile sur toile inspirée de la médina de Tunis.', 1),
            (1, 1, 'Portrait en bleu', 320.50, 'neuve', 2022, 'Portrait expressionniste aux tons bleus.', 1),
            (2, 2, 'Formes en mouvement', 680.00, 'neuve', 2024, 'Sculpture en bronze et acier.', 1),
            (2, 2, 'Silhouette', 520.00, 'neuve', 2023, 'Sculpture figurative en terre cuite.', 1),
            (3, 1, 'Femme au miroir', 380.00, 'neuve', 2022, 'Peinture acrylique sur bois.', 1),
            (3, 3, 'Rues de Sidi Bou Said', 150.00, 'neuve', 2024, 'Tirage photographique noir et blanc.', 1),
            (1, 3, 'Marché de Tunis', 120.00, 'neuve', 2023, 'Photographie couleur format 40x60.', 1),
            (2, 4, 'Algorithmes', 890.00, 'neuve', 2024, 'Œuvre générative art numérique.', 1),
            (3, 5, 'Collage urbain', 275.00, 'neuve', 2023, 'Mixed media sur carton.', 1),
            (1, 5, 'Abstraction tunisienne', 410.00, 'neuve', 2022, 'Peinture abstraite aux couleurs de la Tunisie.', 1)
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM galerie_artiste WHERE galerie_id_galerie BETWEEN 1 AND 5');
        $this->addSql('DELETE FROM oeuvre WHERE id_artiste IN (1, 2, 3)');
        $this->addSql('DELETE FROM galerie WHERE id_galerie BETWEEN 1 AND 5');
    }
}

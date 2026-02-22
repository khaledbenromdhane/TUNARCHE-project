<?php

require dirname(__DIR__).'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$entityManager = $kernel->getContainer()->get('doctrine')->getManager();
$connection = $entityManager->getConnection();

// Clear existing data
echo "Clearing existing data...\n";
$connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
$connection->executeStatement('DELETE FROM participation');
$connection->executeStatement('DELETE FROM evenement');
$connection->executeStatement('ALTER TABLE evenement AUTO_INCREMENT = 1');
$connection->executeStatement('ALTER TABLE participation AUTO_INCREMENT = 1');
$connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

echo "Inserting realistic events...\n";

$events = [
    [
        'nom' => 'Concert Jazz : Night in Tunisia',
        'type_evenement' => 'Concerts',
        'nbr_participant' => 250,
        'date' => '2026-03-15',
        'heure' => '20:30:00',
        'lieu' => 'Théâtre Municipal de Tunis',
        'description' => 'Soirée jazz exceptionnelle avec des artistes internationaux. Découvrez les standards du jazz dans une ambiance intimiste et chaleureuse. Un voyage musical à travers les plus grands classiques.',
        'paiement' => 1,
        'prix' => 35.00
    ],
    [
        'nom' => 'Exposition : Art Contemporain Tunisien',
        'type_evenement' => 'Expositions d\'art',
        'nbr_participant' => 150,
        'date' => '2026-03-20',
        'heure' => '10:00:00',
        'lieu' => 'Galerie El Marsa',
        'description' => 'Découvrez les œuvres des artistes tunisiens contemporains. Une exploration fascinante de l\'art moderne local, mettant en lumière la diversité créative de notre région.',
        'paiement' => 0,
        'prix' => null
    ],
    [
        'nom' => 'Festival des Arts de la Rue',
        'type_evenement' => 'Festivals',
        'nbr_participant' => 500,
        'date' => '2026-04-05',
        'heure' => '14:00:00',
        'lieu' => 'Avenue Habib Bourguiba',
        'description' => 'Trois jours de spectacles gratuits en plein air. Théâtre de rue, performances musicales, jongleurs, acrobates et bien plus encore dans le cœur de la ville.',
        'paiement' => 0,
        'prix' => null
    ],
    [
        'nom' => 'Spectacle de Danse Orientale',
        'type_evenement' => 'Spectacles de danse',
        'nbr_participant' => 180,
        'date' => '2026-03-25',
        'heure' => '19:00:00',
        'lieu' => 'Centre Culturel International',
        'description' => 'Performance exceptionnelle de danse orientale avec la troupe Al Nour. Costumes traditionnels, chorégraphies modernes, une fusion entre tradition et modernité.',
        'paiement' => 1,
        'prix' => 25.00
    ],
    [
        'nom' => 'Pièce de Théâtre : Les Fourberies de Scapin',
        'type_evenement' => 'Théâtre',
        'nbr_participant' => 200,
        'date' => '2026-04-10',
        'heure' => '20:00:00',
        'lieu' => 'Théâtre de l\'Étoile du Nord',
        'description' => 'Adaptation moderne du classique de Molière par la Compagnie Nationale. Rires et rebondissements garantis dans cette comédie intemporelle revisitée.',
        'paiement' => 1,
        'prix' => 20.00
    ],
    [
        'nom' => 'Tournoi d\'Échecs International',
        'type_evenement' => 'Tournois',
        'nbr_participant' => 64,
        'date' => '2026-03-30',
        'heure' => '09:00:00',
        'lieu' => 'Club d\'Échecs de Carthage',
        'description' => 'Compétition d\'échecs ouverte à tous niveaux. Inscription obligatoire. Prix pour les vainqueurs. Venez défier les meilleurs joueurs de la région.',
        'paiement' => 1,
        'prix' => 15.00
    ],
    [
        'nom' => 'Formation : Photographie Artistique',
        'type_evenement' => 'Formations',
        'nbr_participant' => 30,
        'date' => '2026-04-15',
        'heure' => '10:00:00',
        'lieu' => 'École des Beaux-Arts',
        'description' => 'Atelier de 3 jours sur les techniques de photographie artistique. Initiation à la composition, l\'éclairage et le post-traitement. Matériel fourni.',
        'paiement' => 1,
        'prix' => 120.00
    ],
    [
        'nom' => 'Concert Symphonique : Beethoven',
        'type_evenement' => 'Concerts',
        'nbr_participant' => 400,
        'date' => '2026-03-28',
        'heure' => '19:30:00',
        'lieu' => 'Opéra de Tunis',
        'description' => 'L\'Orchestre Symphonique National interprète la 9ème Symphonie de Beethoven. Une soirée mémorable avec l\'un des plus grands chefs-d\'œuvre de la musique classique.',
        'paiement' => 1,
        'prix' => 45.00
    ],
    [
        'nom' => 'Exposition : Photographies du Sahara',
        'type_evenement' => 'Expositions d\'art',
        'nbr_participant' => 100,
        'date' => '2026-04-12',
        'heure' => '11:00:00',
        'lieu' => 'Musée National du Bardo',
        'description' => 'Collection unique de photographies capturant la beauté du désert tunisien. Exposition gratuite organisée en partenariat avec le Ministère de la Culture.',
        'paiement' => 0,
        'prix' => null
    ],
    [
        'nom' => 'Festival de Musique Électronique',
        'type_evenement' => 'Festivals',
        'nbr_participant' => 800,
        'date' => '2026-05-01',
        'heure' => '21:00:00',
        'lieu' => 'Plage de La Marsa',
        'description' => 'Nuit sous les étoiles avec les meilleurs DJs nationaux et internationaux. Food trucks, animations, et ambiance festive garantie jusqu\'au lever du soleil.',
        'paiement' => 1,
        'prix' => 40.00
    ],
    [
        'nom' => 'Spectacle de Flamenco',
        'type_evenement' => 'Spectacles de danse',
        'nbr_participant' => 120,
        'date' => '2026-04-08',
        'heure' => '20:30:00',
        'lieu' => 'Palais Ennejma Ezzahra',
        'description' => 'Soirée flamenco authentique dans un cadre historique exceptionnel. Danseurs espagnols accompagnés de musiciens virtuoses pour une performance passionnée.',
        'paiement' => 1,
        'prix' => 30.00
    ],
    [
        'nom' => 'Comédie Musicale : Les Misérables',
        'type_evenement' => 'Théâtre',
        'nbr_participant' => 350,
        'date' => '2026-04-20',
        'heure' => '19:00:00',
        'lieu' => 'Théâtre de Carthage',
        'description' => 'Version arabe de la célèbre comédie musicale. Production grandiose avec décors spectaculaires, costumes somptueux et acteurs talentueux.',
        'paiement' => 1,
        'prix' => 50.00
    ],
    [
        'nom' => 'Tournoi de Tennis Amateur',
        'type_evenement' => 'Tournois',
        'nbr_participant' => 32,
        'date' => '2026-04-18',
        'heure' => '08:00:00',
        'lieu' => 'Club Sportif de Tunis',
        'description' => 'Compétition de tennis en simple et double. Ouvert aux amateurs et semi-professionnels. Inscription obligatoire avant le 10 avril.',
        'paiement' => 1,
        'prix' => 25.00
    ],
    [
        'nom' => 'Atelier Calligraphie Arabe',
        'type_evenement' => 'Formations',
        'nbr_participant' => 20,
        'date' => '2026-04-22',
        'heure' => '14:00:00',
        'lieu' => 'Médina de Tunis',
        'description' => 'Initiation à l\'art de la calligraphie arabe traditionnelle. Matériel fourni. Apprenez les bases de cet art millénaire avec un maître calligraphe.',
        'paiement' => 1,
        'prix' => 40.00
    ],
    [
        'nom' => 'Concert Rap : Festival Hip Hop TN',
        'type_evenement' => 'Concerts',
        'nbr_participant' => 600,
        'date' => '2026-05-10',
        'heure' => '20:00:00',
        'lieu' => 'Stade El Menzah',
        'description' => 'Festival réunissant les plus grands rappeurs tunisiens. Balti, Kafon, Emino et bien d\'autres sur une seule scène. Événement urbain de l\'année.',
        'paiement' => 1,
        'prix' => 30.00
    ]
];

foreach ($events as $event) {
    $connection->insert('evenement', $event);
    echo "✓ Inserted: {$event['nom']}\n";
}

echo "\nDone! {$connection->lastInsertId()} events inserted successfully.\n";

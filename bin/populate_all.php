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

// Hash password using PHP native bcrypt (same as Symfony default)
$hashedPw = password_hash('Password123!', PASSWORD_BCRYPT);

echo "=== SEEDING DATABASE WITH REALISTIC DATA (5 per table) ===\n\n";
$connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

// ── Clear all tables ──
$tables = [
    'publication_reaction', 'commentaire_reaction', 'commentaire', 'publication',
    'resultat', 'question', 'quiz', 'evaluation', 'formation',
    'participation', 'evenement', 'oeuvre', 'galerie_artiste', 'galerie', 'user'
];
foreach ($tables as $t) {
    $connection->executeStatement("DELETE FROM `$t`");
    try { $connection->executeStatement("ALTER TABLE `$t` AUTO_INCREMENT = 1"); } catch (\Exception $e) {}
}
echo "Cleared all tables.\n\n";

$connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

// ════════════════════════════════════════════════════════════
// 1. USERS (5)
// ════════════════════════════════════════════════════════════
echo "Inserting Users...\n";

$users = [
    ['Nom' => 'Benali',   'Prenom' => 'Amira',   'Email' => 'amira.benali@email.com',   'Telephone' => '+21698123456', 'Role' => json_encode(['ROLE_ADMIN']),  'Password' => $hashedPw, 'avatar_filename' => null, 'google_id' => null, 'reset_token' => null, 'reset_token_expires_at' => null],
    ['Nom' => 'Trabelsi', 'Prenom' => 'Youssef',  'Email' => 'youssef.trabelsi@email.com','Telephone' => '+21655789012', 'Role' => json_encode(['ROLE_USER']),   'Password' => $hashedPw, 'avatar_filename' => null, 'google_id' => null, 'reset_token' => null, 'reset_token_expires_at' => null],
    ['Nom' => 'Khemiri',  'Prenom' => 'Leila',    'Email' => 'leila.khemiri@email.com',  'Telephone' => '+21622345678', 'Role' => json_encode(['ROLE_USER']),   'Password' => $hashedPw, 'avatar_filename' => null, 'google_id' => null, 'reset_token' => null, 'reset_token_expires_at' => null],
    ['Nom' => 'Gharbi',   'Prenom' => 'Mehdi',    'Email' => 'mehdi.gharbi@email.com',   'Telephone' => '+21690456789', 'Role' => json_encode(['ROLE_USER']),   'Password' => $hashedPw, 'avatar_filename' => null, 'google_id' => null, 'reset_token' => null, 'reset_token_expires_at' => null],
    ['Nom' => 'Saidi',    'Prenom' => 'Nour',     'Email' => 'nour.saidi@email.com',     'Telephone' => '+21678901234', 'Role' => json_encode(['ROLE_USER']),   'Password' => $hashedPw, 'avatar_filename' => null, 'google_id' => null, 'reset_token' => null, 'reset_token_expires_at' => null],
];

$userIds = [];
foreach ($users as $u) {
    $connection->executeStatement(
        'INSERT INTO `user` (`Nom`, `Prenom`, `Email`, `Telephone`, `Role`, `Password`, `avatar_filename`, `google_id`, `reset_token`, `reset_token_expires_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$u['Nom'], $u['Prenom'], $u['Email'], $u['Telephone'], $u['Role'], $u['Password'], $u['avatar_filename'], $u['google_id'], $u['reset_token'], $u['reset_token_expires_at']]
    );
    $userIds[] = (int) $connection->lastInsertId();
}
echo "  -> " . count($userIds) . " users inserted (IDs: " . implode(', ', $userIds) . ")\n";

// ════════════════════════════════════════════════════════════
// 2. EVENEMENTS (5)
// ════════════════════════════════════════════════════════════
echo "Inserting Evenements...\n";

$evenements = [
    ['nom' => 'Nuit du Jazz Tunisien',            'type_evenement' => 'Concerts',             'nbr_participant' => 200, 'date' => '2026-04-10', 'heure' => '20:00:00', 'lieu' => 'Théâtre Municipal de Tunis',   'description' => 'Une soirée dédiée au jazz tunisien avec des musiciens locaux de renom. Ambiance intimiste garantie.', 'paiement' => 1, 'prix' => 25.00, 'image' => null],
    ['nom' => 'Exposition Art Moderne',            'type_evenement' => 'Expositions d\'art',   'nbr_participant' => 100, 'date' => '2026-04-18', 'heure' => '10:00:00', 'lieu' => 'Galerie El Marsa',              'description' => 'Découvrez les créations les plus audacieuses de la scène artistique tunisienne contemporaine.',      'paiement' => 0, 'prix' => null,  'image' => null],
    ['nom' => 'Festival des Arts de la Rue',       'type_evenement' => 'Festivals',            'nbr_participant' => 500, 'date' => '2026-05-01', 'heure' => '14:00:00', 'lieu' => 'Avenue Habib Bourguiba, Tunis', 'description' => 'Art de rue, graffiti live, performances et ateliers créatifs dans le cœur de Tunis.',                 'paiement' => 0, 'prix' => null,  'image' => null],
    ['nom' => 'Spectacle de Danse Soufie',         'type_evenement' => 'Spectacles de danse',  'nbr_participant' => 150, 'date' => '2026-05-15', 'heure' => '19:30:00', 'lieu' => 'Centre Culturel de Sidi Bou Saïd','description' => 'Une performance de danse soufie mêlant tradition et modernité dans un cadre enchanteur.',          'paiement' => 1, 'prix' => 15.00, 'image' => null],
    ['nom' => 'Atelier Peinture à l\'Huile',       'type_evenement' => 'Formations',           'nbr_participant' => 20,  'date' => '2026-06-05', 'heure' => '09:00:00', 'lieu' => 'Atelier d\'Art La Goulette',     'description' => 'Apprenez les techniques de la peinture à l\'huile avec un artiste professionnel. Matériel fourni.',  'paiement' => 1, 'prix' => 40.00, 'image' => null],
];

$eventIds = [];
foreach ($evenements as $e) {
    $connection->executeStatement(
        'INSERT INTO evenement (nom, type_evenement, nbr_participant, date, heure, lieu, description, paiement, prix, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$e['nom'], $e['type_evenement'], $e['nbr_participant'], $e['date'], $e['heure'], $e['lieu'], $e['description'], $e['paiement'], $e['prix'], $e['image']]
    );
    $eventIds[] = (int) $connection->lastInsertId();
}
echo "  -> " . count($eventIds) . " evenements inserted\n";

// ════════════════════════════════════════════════════════════
// 3. PARTICIPATIONS (5)
// ════════════════════════════════════════════════════════════
echo "Inserting Participations...\n";

$participations = [
    ['id_user' => $userIds[1], 'id_evenement' => $eventIds[0], 'date_participation' => '2026-03-25', 'statut' => 'Confirmée', 'nbr_participation' => 2, 'mode_paiement' => 'Carte',  'scanned' => 0, 'scanned_at' => null],
    ['id_user' => $userIds[2], 'id_evenement' => $eventIds[1], 'date_participation' => '2026-04-01', 'statut' => 'Confirmée', 'nbr_participation' => 1, 'mode_paiement' => null,     'scanned' => 0, 'scanned_at' => null],
    ['id_user' => $userIds[3], 'id_evenement' => $eventIds[2], 'date_participation' => '2026-04-20', 'statut' => 'En attente','nbr_participation' => 3, 'mode_paiement' => null,     'scanned' => 0, 'scanned_at' => null],
    ['id_user' => $userIds[4], 'id_evenement' => $eventIds[3], 'date_participation' => '2026-05-10', 'statut' => 'Confirmée', 'nbr_participation' => 1, 'mode_paiement' => 'Cash',   'scanned' => 0, 'scanned_at' => null],
    ['id_user' => $userIds[0], 'id_evenement' => $eventIds[4], 'date_participation' => '2026-05-30', 'statut' => 'Annulée',   'nbr_participation' => 1, 'mode_paiement' => 'Carte',  'scanned' => 0, 'scanned_at' => null],
];

foreach ($participations as $p) {
    $connection->executeStatement(
        'INSERT INTO participation (id_user, id_evenement, date_participation, statut, nbr_participation, mode_paiement, scanned, scanned_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [$p['id_user'], $p['id_evenement'], $p['date_participation'], $p['statut'], $p['nbr_participation'], $p['mode_paiement'], $p['scanned'], $p['scanned_at']]
    );
}
echo "  -> 5 participations inserted\n";

// ════════════════════════════════════════════════════════════
// 4. GALERIES (5)
// ════════════════════════════════════════════════════════════
echo "Inserting Galeries...\n";

$galeries = [
    ['categorie' => 'Peinture',      'nom' => 'Galerie des Lumières',        'nb_oeuvres_dispo' => 5, 'nb_employes' => 3],
    ['categorie' => 'Sculpture',     'nom' => 'Espace Sculptural',           'nb_oeuvres_dispo' => 5, 'nb_employes' => 2],
    ['categorie' => 'Photographie',  'nom' => 'Lens & Vision Gallery',       'nb_oeuvres_dispo' => 5, 'nb_employes' => 2],
    ['categorie' => 'Art Numérique', 'nom' => 'Digital Art Lab',             'nb_oeuvres_dispo' => 5, 'nb_employes' => 4],
    ['categorie' => 'Art Mixte',     'nom' => 'Atelier Pluriel',             'nb_oeuvres_dispo' => 5, 'nb_employes' => 1],
];

$galerieIds = [];
foreach ($galeries as $g) {
    $connection->executeStatement(
        'INSERT INTO galerie (categorie, nom, nb_oeuvres_dispo, nb_employes) VALUES (?, ?, ?, ?)',
        [$g['categorie'], $g['nom'], $g['nb_oeuvres_dispo'], $g['nb_employes']]
    );
    $galerieIds[] = (int) $connection->lastInsertId();
}
echo "  -> 5 galeries inserted\n";

// Link users to galeries (ManyToMany)
$connection->executeStatement('INSERT INTO galerie_artiste (galerie_id_galerie, user_id_user) VALUES (?, ?)', [$galerieIds[0], $userIds[1]]);
$connection->executeStatement('INSERT INTO galerie_artiste (galerie_id_galerie, user_id_user) VALUES (?, ?)', [$galerieIds[1], $userIds[2]]);
$connection->executeStatement('INSERT INTO galerie_artiste (galerie_id_galerie, user_id_user) VALUES (?, ?)', [$galerieIds[2], $userIds[3]]);
$connection->executeStatement('INSERT INTO galerie_artiste (galerie_id_galerie, user_id_user) VALUES (?, ?)', [$galerieIds[3], $userIds[4]]);
$connection->executeStatement('INSERT INTO galerie_artiste (galerie_id_galerie, user_id_user) VALUES (?, ?)', [$galerieIds[4], $userIds[1]]);
echo "  -> 5 galerie-artiste links inserted\n";

// ════════════════════════════════════════════════════════════
// 5. OEUVRES (5)
// ════════════════════════════════════════════════════════════
echo "Inserting Oeuvres...\n";

$oeuvres = [
    ['titre' => 'Crépuscule sur Sidi Bou Saïd',  'prix' => 1200.00, 'etat' => 'neuve', 'annee_realisation' => 2024, 'image' => null, 'description' => 'Peinture à l\'huile représentant le coucher de soleil sur les ruelles bleues et blanches.',     'statut' => 'disponible', 'date_vente' => null, 'id_artiste' => $userIds[1], 'id_galerie' => $galerieIds[0]],
    ['titre' => 'Buste en Bronze - Le Penseur',    'prix' => 3500.00, 'etat' => 'neuve', 'annee_realisation' => 2023, 'image' => null, 'description' => 'Sculpture en bronze d\'un personnage méditatif inspiré de la culture méditerranéenne.',          'statut' => 'disponible', 'date_vente' => null, 'id_artiste' => $userIds[2], 'id_galerie' => $galerieIds[1]],
    ['titre' => 'Ruelles de la Médina',            'prix' => 450.00,  'etat' => 'neuve', 'annee_realisation' => 2025, 'image' => null, 'description' => 'Série photographique capturant l\'essence des médinas tunisiennes au petit matin.',             'statut' => 'vendue',     'date_vente' => '2026-01-15 10:00:00', 'id_artiste' => $userIds[3], 'id_galerie' => $galerieIds[2]],
    ['titre' => 'Fractales Méditerranéennes',      'prix' => 800.00,  'etat' => 'neuve', 'annee_realisation' => 2025, 'image' => null, 'description' => 'Art numérique génératif inspiré des motifs géométriques islamiques et des fractales.',          'statut' => 'disponible', 'date_vente' => null, 'id_artiste' => $userIds[4], 'id_galerie' => $galerieIds[3]],
    ['titre' => 'Mémoires de Carthage',            'prix' => 2000.00, 'etat' => 'défectueuse', 'annee_realisation' => 2022, 'image' => null, 'description' => 'Installation mixte mêlant céramique antique, fils de cuivre et projections lumineuses.', 'statut' => 'disponible', 'date_vente' => null, 'id_artiste' => $userIds[1], 'id_galerie' => $galerieIds[4]],
];

foreach ($oeuvres as $o) {
    $connection->executeStatement(
        'INSERT INTO oeuvre (titre, prix, etat, annee_realisation, image, description, statut, date_vente, id_artiste, id_galerie) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$o['titre'], $o['prix'], $o['etat'], $o['annee_realisation'], $o['image'], $o['description'], $o['statut'], $o['date_vente'], $o['id_artiste'], $o['id_galerie']]
    );
}
echo "  -> 5 oeuvres inserted\n";

// ════════════════════════════════════════════════════════════
// 6. FORMATIONS (5)
// ════════════════════════════════════════════════════════════
echo "Inserting Formations...\n";

$formations = [
    ['nom_form' => 'Initiation à l\'Aquarelle',        'date_form' => '2026-04-01', 'type' => 'Peinture',     'description' => 'Apprenez les bases de l\'aquarelle : mélanges, techniques de lavis et composition. Matériel inclus.', 'image_name' => null, 'user_id' => $userIds[0]],
    ['nom_form' => 'Sculpture sur Argile',              'date_form' => '2026-04-15', 'type' => 'Sculpture',    'description' => 'Atelier pratique de modelage sur argile. Créez votre première pièce en 3 heures.', 'image_name' => null, 'user_id' => $userIds[0]],
    ['nom_form' => 'Photographie Portrait Studio',      'date_form' => '2026-05-05', 'type' => 'Photographie', 'description' => 'Maîtrisez l\'éclairage studio et les poses pour réaliser des portraits professionnels.', 'image_name' => null, 'user_id' => $userIds[0]],
    ['nom_form' => 'Design Graphique avec Figma',       'date_form' => '2026-05-20', 'type' => 'Numérique',    'description' => 'Formation complète sur Figma : composants, prototypage et design system.', 'image_name' => null, 'user_id' => $userIds[0]],
    ['nom_form' => 'Histoire de l\'Art Contemporain',   'date_form' => '2026-06-10', 'type' => 'Théorie',      'description' => 'Parcours des grands mouvements artistiques du XXe siècle à aujourd\'hui.', 'image_name' => null, 'user_id' => $userIds[0]],
];

$formationIds = [];
foreach ($formations as $f) {
    $connection->executeStatement(
        'INSERT INTO formation (nom_form, date_form, type, description, image_name, user_id) VALUES (?, ?, ?, ?, ?, ?)',
        [$f['nom_form'], $f['date_form'], $f['type'], $f['description'], $f['image_name'], $f['user_id']]
    );
    $formationIds[] = (int) $connection->lastInsertId();
}
echo "  -> 5 formations inserted\n";

// ════════════════════════════════════════════════════════════
// 7. EVALUATIONS (5)
// ════════════════════════════════════════════════════════════
echo "Inserting Evaluations...\n";

$evaluations = [
    ['titre' => 'Très instructif',               'note' => 5, 'commentaire' => 'La formation aquarelle était passionnante. J\'ai appris énormément en très peu de temps.', 'formation_id' => $formationIds[0], 'user_id' => $userIds[1]],
    ['titre' => 'Bonne expérience',              'note' => 4, 'commentaire' => 'L\'atelier sculpture était bien organisé. J\'aurais aimé un peu plus de temps pour finir ma pièce.', 'formation_id' => $formationIds[1], 'user_id' => $userIds[2]],
    ['titre' => 'Qualité professionnelle',       'note' => 5, 'commentaire' => 'Le formateur photographie est excellent. Ses conseils sur l\'éclairage m\'ont vraiment aidé.', 'formation_id' => $formationIds[2], 'user_id' => $userIds[3]],
    ['titre' => 'Contenu riche et pratique',     'note' => 4, 'commentaire' => 'Formation Figma complète et bien structurée. Les exercices pratiques sont un vrai plus.', 'formation_id' => $formationIds[3], 'user_id' => $userIds[4]],
    ['titre' => 'Intéressant mais dense',        'note' => 3, 'commentaire' => 'Beaucoup d\'informations en peu de temps. Aurait mérité d\'être étalé sur plusieurs séances.', 'formation_id' => $formationIds[4], 'user_id' => $userIds[1]],
];

foreach ($evaluations as $ev) {
    $connection->executeStatement(
        'INSERT INTO evaluation (titre, note, commentaire, formation_id, user_id) VALUES (?, ?, ?, ?, ?)',
        [$ev['titre'], $ev['note'], $ev['commentaire'], $ev['formation_id'], $ev['user_id']]
    );
}
echo "  -> 5 evaluations inserted\n";

// ════════════════════════════════════════════════════════════
// 8. QUIZZES (5) – one per formation
// ════════════════════════════════════════════════════════════
echo "Inserting Quizzes...\n";

$quizzes = [
    ['title' => 'Quiz Aquarelle',          'formation_id' => $formationIds[0]],
    ['title' => 'Quiz Sculpture',          'formation_id' => $formationIds[1]],
    ['title' => 'Quiz Photographie',       'formation_id' => $formationIds[2]],
    ['title' => 'Quiz Design Graphique',   'formation_id' => $formationIds[3]],
    ['title' => 'Quiz Histoire de l\'Art', 'formation_id' => $formationIds[4]],
];

$quizIds = [];
foreach ($quizzes as $qz) {
    $connection->executeStatement(
        'INSERT INTO quiz (title, formation_id) VALUES (?, ?)',
        [$qz['title'], $qz['formation_id']]
    );
    $quizIds[] = (int) $connection->lastInsertId();
}
echo "  -> 5 quizzes inserted\n";

// ════════════════════════════════════════════════════════════
// 9. QUESTIONS (5) – one per quiz
// ════════════════════════════════════════════════════════════
echo "Inserting Questions...\n";

$questions = [
    ['question_text' => 'Quel type de papier est recommandé pour l\'aquarelle ?',       'choice_a' => 'Papier kraft',        'choice_b' => 'Papier cellulose 300g', 'choice_c' => 'Papier journal',         'correct_answer' => 'B', 'quiz_id' => $quizIds[0]],
    ['question_text' => 'Quelle terre est la plus utilisée en sculpture ?',              'choice_a' => 'Argile chamottée',    'choice_b' => 'Sable fin',             'choice_c' => 'Terre de bruyère',       'correct_answer' => 'A', 'quiz_id' => $quizIds[1]],
    ['question_text' => 'Qu\'est-ce que la règle des tiers en photographie ?',           'choice_a' => 'Régler l\'ISO à 1/3', 'choice_b' => 'Diviser l\'image en 9 zones', 'choice_c' => 'Utiliser 3 objectifs', 'correct_answer' => 'B', 'quiz_id' => $quizIds[2]],
    ['question_text' => 'Dans Figma, comment créer un composant réutilisable ?',         'choice_a' => 'Ctrl+Alt+K',          'choice_b' => 'Ctrl+Shift+P',          'choice_c' => 'Ctrl+Alt+B',             'correct_answer' => 'A', 'quiz_id' => $quizIds[3]],
    ['question_text' => 'Quel mouvement artistique est né au début du XXe siècle ?',    'choice_a' => 'Baroque',             'choice_b' => 'Cubisme',               'choice_c' => 'Romantisme',             'correct_answer' => 'B', 'quiz_id' => $quizIds[4]],
];

foreach ($questions as $q) {
    $connection->executeStatement(
        'INSERT INTO question (question_text, choice_a, choice_b, choice_c, correct_answer, quiz_id) VALUES (?, ?, ?, ?, ?, ?)',
        [$q['question_text'], $q['choice_a'], $q['choice_b'], $q['choice_c'], $q['correct_answer'], $q['quiz_id']]
    );
}
echo "  -> 5 questions inserted\n";

// ════════════════════════════════════════════════════════════
// 10. RESULTATS (5) – one per quiz
// ════════════════════════════════════════════════════════════
echo "Inserting Resultats...\n";

$resultats = [
    ['score' => 1, 'is_passed' => 1, 'created_at' => '2026-02-20 14:30:00', 'quiz_id' => $quizIds[0]],
    ['score' => 0, 'is_passed' => 0, 'created_at' => '2026-02-21 10:15:00', 'quiz_id' => $quizIds[1]],
    ['score' => 1, 'is_passed' => 1, 'created_at' => '2026-02-22 16:45:00', 'quiz_id' => $quizIds[2]],
    ['score' => 1, 'is_passed' => 1, 'created_at' => '2026-02-23 09:00:00', 'quiz_id' => $quizIds[3]],
    ['score' => 0, 'is_passed' => 0, 'created_at' => '2026-02-24 11:30:00', 'quiz_id' => $quizIds[4]],
];

foreach ($resultats as $r) {
    $connection->executeStatement(
        'INSERT INTO resultat (score, is_passed, created_at, quiz_id) VALUES (?, ?, ?, ?)',
        [$r['score'], $r['is_passed'], $r['created_at'], $r['quiz_id']]
    );
}
echo "  -> 5 resultats inserted\n";

// ════════════════════════════════════════════════════════════
// 11. PUBLICATIONS (5)
// ════════════════════════════════════════════════════════════
echo "Inserting Publications...\n";

$publications = [
    ['date_act' => '2026-02-10 09:00:00', 'description' => 'Découvrez les nouvelles tendances de l\'art contemporain tunisien à travers les yeux de jeunes artistes émergents.', 'titre' => 'L\'Art Contemporain en Tunisie', 'slug' => 'l-art-contemporain-en-tunisie', 'image' => 'default.jpg', 'nb_likes' => 12, 'nb_dislikes' => 1, 'id_user' => $userIds[0]],
    ['date_act' => '2026-02-15 14:30:00', 'description' => 'Guide complet pour débuter la peinture à l\'aquarelle : matériel, techniques de base et premiers exercices pratiques.', 'titre' => 'Débuter l\'Aquarelle',          'slug' => 'debuter-l-aquarelle',            'image' => 'default.jpg', 'nb_likes' => 8,  'nb_dislikes' => 0, 'id_user' => $userIds[1]],
    ['date_act' => '2026-02-20 11:00:00', 'description' => 'Retour en images sur l\'exposition qui a marqué le mois de février. Plus de 200 visiteurs ont découvert les œuvres.', 'titre' => 'Retour sur l\'Exposition de Février', 'slug' => 'retour-sur-l-exposition-de-fevrier', 'image' => 'default.jpg', 'nb_likes' => 15, 'nb_dislikes' => 2, 'id_user' => $userIds[2]],
    ['date_act' => '2026-02-25 16:00:00', 'description' => 'Rencontre avec Mehdi Gharbi, sculpteur passionné qui transforme le bronze en émotion. Interview exclusive.', 'titre' => 'Interview : Mehdi Gharbi, Sculpteur', 'slug' => 'interview-mehdi-gharbi-sculpteur', 'image' => 'default.jpg', 'nb_likes' => 20, 'nb_dislikes' => 0, 'id_user' => $userIds[3]],
    ['date_act' => '2026-03-01 08:00:00', 'description' => 'Calendrier des événements artistiques à ne pas manquer en mars et avril. Concerts, expos et ateliers au programme.', 'titre' => 'Agenda Culturel Mars-Avril 2026', 'slug' => 'agenda-culturel-mars-avril-2026', 'image' => 'default.jpg', 'nb_likes' => 5,  'nb_dislikes' => 0, 'id_user' => $userIds[4]],
];

$pubIds = [];
foreach ($publications as $pub) {
    $connection->executeStatement(
        'INSERT INTO publication (date_act, description, titre, slug, image, nb_likes, nb_dislikes, id_user) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [$pub['date_act'], $pub['description'], $pub['titre'], $pub['slug'], $pub['image'], $pub['nb_likes'], $pub['nb_dislikes'], $pub['id_user']]
    );
    $pubIds[] = (int) $connection->lastInsertId();
}
echo "  -> 5 publications inserted\n";

// ════════════════════════════════════════════════════════════
// 12. COMMENTAIRES (5)
// ════════════════════════════════════════════════════════════
echo "Inserting Commentaires...\n";

$commentaires = [
    ['content' => 'Superbe article ! J\'ai appris beaucoup de choses sur la scène artistique locale.', 'date_creation' => '2026-02-11 10:30:00', 'status' => 'visible', 'nb_likes' => 4,  'nb_dislikes' => 0, 'parent_id' => 0, 'est_signale' => 0, 'raison_signalement' => null, 'id_user' => $userIds[1], 'id_publication' => $pubIds[0]],
    ['content' => 'Merci pour ce guide, j\'ai enfin osé me lancer dans l\'aquarelle !',               'date_creation' => '2026-02-16 09:15:00', 'status' => 'visible', 'nb_likes' => 7,  'nb_dislikes' => 0, 'parent_id' => 0, 'est_signale' => 0, 'raison_signalement' => null, 'id_user' => $userIds[2], 'id_publication' => $pubIds[1]],
    ['content' => 'L\'exposition était magnifique, les photos ne rendent pas justice à la beauté des œuvres.', 'date_creation' => '2026-02-21 15:00:00', 'status' => 'visible', 'nb_likes' => 3, 'nb_dislikes' => 1, 'parent_id' => 0, 'est_signale' => 0, 'raison_signalement' => null, 'id_user' => $userIds[3], 'id_publication' => $pubIds[2]],
    ['content' => 'Interview très inspirante, Mehdi est un artiste au parcours remarquable.',           'date_creation' => '2026-02-26 12:45:00', 'status' => 'visible', 'nb_likes' => 10, 'nb_dislikes' => 0, 'parent_id' => 0, 'est_signale' => 0, 'raison_signalement' => null, 'id_user' => $userIds[4], 'id_publication' => $pubIds[3]],
    ['content' => 'Hâte d\'assister au festival des arts de la rue ! Merci pour l\'info.',             'date_creation' => '2026-03-01 08:30:00', 'status' => 'visible', 'nb_likes' => 2,  'nb_dislikes' => 0, 'parent_id' => 0, 'est_signale' => 0, 'raison_signalement' => null, 'id_user' => $userIds[0], 'id_publication' => $pubIds[4]],
];

$comIds = [];
foreach ($commentaires as $c) {
    $connection->executeStatement(
        'INSERT INTO commentaire (content, date_creation, status, nb_likes, nb_dislikes, parent_id, est_signale, raison_signalement, id_user, id_publication) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [$c['content'], $c['date_creation'], $c['status'], $c['nb_likes'], $c['nb_dislikes'], $c['parent_id'], $c['est_signale'], $c['raison_signalement'], $c['id_user'], $c['id_publication']]
    );
    $comIds[] = (int) $connection->lastInsertId();
}
echo "  -> 5 commentaires inserted\n";

// ════════════════════════════════════════════════════════════
// 13. PUBLICATION REACTIONS (5)
// ════════════════════════════════════════════════════════════
echo "Inserting Publication Reactions...\n";

$pubReactions = [
    ['id_user' => $userIds[1], 'id_publication' => $pubIds[0], 'is_like' => 1],
    ['id_user' => $userIds[2], 'id_publication' => $pubIds[1], 'is_like' => 1],
    ['id_user' => $userIds[3], 'id_publication' => $pubIds[2], 'is_like' => 1],
    ['id_user' => $userIds[4], 'id_publication' => $pubIds[3], 'is_like' => 1],
    ['id_user' => $userIds[0], 'id_publication' => $pubIds[4], 'is_like' => 0],
];

foreach ($pubReactions as $pr) {
    $connection->executeStatement(
        'INSERT INTO publication_reaction (id_user, id_publication, is_like) VALUES (?, ?, ?)',
        [$pr['id_user'], $pr['id_publication'], $pr['is_like']]
    );
}
echo "  -> 5 publication reactions inserted\n";

// ════════════════════════════════════════════════════════════
// 14. COMMENTAIRE REACTIONS (5)
// ════════════════════════════════════════════════════════════
echo "Inserting Commentaire Reactions...\n";

$comReactions = [
    ['id_user' => $userIds[0], 'id_commentaire' => $comIds[0], 'is_like' => 1],
    ['id_user' => $userIds[1], 'id_commentaire' => $comIds[1], 'is_like' => 1],
    ['id_user' => $userIds[2], 'id_commentaire' => $comIds[2], 'is_like' => 0],
    ['id_user' => $userIds[3], 'id_commentaire' => $comIds[3], 'is_like' => 1],
    ['id_user' => $userIds[4], 'id_commentaire' => $comIds[4], 'is_like' => 1],
];

foreach ($comReactions as $cr) {
    $connection->executeStatement(
        'INSERT INTO commentaire_reaction (id_user, id_commentaire, is_like) VALUES (?, ?, ?)',
        [$cr['id_user'], $cr['id_commentaire'], $cr['is_like']]
    );
}
echo "  -> 5 commentaire reactions inserted\n";

echo "\n=== DATABASE SEEDED SUCCESSFULLY ===\n";
echo "Admin login: amira.benali@email.com / Password123!\n";
echo "User login:  youssef.trabelsi@email.com / Password123!\n";

<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=integration', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$stmt = $pdo->query('SELECT id_user, Email, LEFT(Password,25) as pw, Role FROM user ORDER BY id_user LIMIT 10');
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $r['id_user'] . ' | ' . $r['Email'] . ' | ' . $r['pw'] . '... | ' . $r['Role'] . PHP_EOL;
}

// Test password verification
$stmt2 = $pdo->query("SELECT Email, Password FROM user WHERE id_user = 1");
$user = $stmt2->fetch(PDO::FETCH_ASSOC);
if ($user) {
    echo "\nPassword verify test for " . $user['Email'] . ": ";
    echo password_verify('Password123!', $user['Password']) ? 'OK' : 'FAIL';
    echo PHP_EOL;
}

// Count all tables
$tables = ['user','evenement','participation','galerie','oeuvre','formation','evaluation','quiz','question','resultat','publication','commentaire'];
foreach ($tables as $t) {
    try {
        $c = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "$t: $c rows\n";
    } catch (Exception $e) {
        echo "$t: ERROR - " . $e->getMessage() . "\n";
    }
}

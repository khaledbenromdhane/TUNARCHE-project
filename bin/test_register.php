<?php
// Test 1: Password hashing
require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

echo "=== Test 1: Password Hashing ===" . PHP_EOL;
try {
    $hasher = new NativePasswordHasher(null, null, 12, '2y');
    $hash = $hasher->hash('TestPassword123!');
    echo "Hash OK: " . substr($hash, 0, 30) . PHP_EOL;
    echo "Verify: " . ($hasher->verify($hash, 'TestPassword123!') ? 'OK' : 'FAIL') . PHP_EOL;
} catch (Exception $e) {
    echo "ERROR hashing: " . $e->getMessage() . PHP_EOL;
}

// Test 2: Recaptcha URL access
echo PHP_EOL . "=== Test 2: External URL Access ===" . PHP_EOL;
$ctx = stream_context_create(['http' => ['timeout' => 5]]);
$result = @file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=test&response=test', false, $ctx);
echo $result !== false ? "URL access OK" : "URL access FAILED";
echo PHP_EOL;

// Test 3: Database insert
echo PHP_EOL . "=== Test 3: Database Insert ===" . PHP_EOL;
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=integration', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    // Check if test email exists
    $stmt = $pdo->prepare('SELECT id_user FROM user WHERE Email = ?');
    $stmt->execute(['test_register@test.com']);
    $existing = $stmt->fetch();
    if ($existing) {
        $pdo->exec("DELETE FROM user WHERE Email = 'test_register@test.com'");
        echo "Cleaned up existing test user" . PHP_EOL;
    }
    
    $hash = password_hash('TestPassword123!', PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare('INSERT INTO user (Nom, Prenom, Email, Telephone, Password, Role) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute(['TestNom', 'TestPrenom', 'test_register@test.com', '+216 00 000 000', $hash, '["ROLE_USER"]']);
    echo "Insert OK - ID: " . $pdo->lastInsertId() . PHP_EOL;
    
    // Clean up
    $pdo->exec("DELETE FROM user WHERE Email = 'test_register@test.com'");
    echo "Cleanup OK" . PHP_EOL;
} catch (Exception $e) {
    echo "DB ERROR: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "All tests completed." . PHP_EOL;

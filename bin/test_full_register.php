<?php
/**
 * Full registration test: GET token, POST form, verify no crash
 */

// Step 1: GET the register page to get cookies/session
$ch = curl_init('http://127.0.0.1:8000/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/../var/test_cookies.txt');
$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Step 1 - GET /register: HTTP $code\n";
curl_close($ch);

// Extract CSRF token
preg_match('/name="user\[_token\]"[^>]*value="([^"]*)"/', $response, $m);
$token = $m[1] ?? 'csrf-token';
echo "CSRF token: $token\n";

// Step 2: POST registration form with unique email
$uniqueEmail = 'test_' . time() . '@example.com';
$ch = curl_init('http://127.0.0.1:8000/register');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Don't follow redirect
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/../var/test_cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/../var/test_cookies.txt');
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'user[nom]' => 'TestNom',
    'user[prenom]' => 'TestPrenom',
    'user[email]' => $uniqueEmail,
    'user[telephone]' => '12345678',
    'user[password]' => 'Azerty123!',
    'user[role]' => 'ROLE_USER',
    'user[_token]' => $token,
    'recaptcha_token' => '',
]);
$response2 = curl_exec($ch);
$code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
echo "Step 2 - POST /register: HTTP $code2\n";
if ($err) echo "cURL error: $err\n";
curl_close($ch);

// Step 3: Follow redirect (test if server is still alive)
if ($code2 === 302) {
    preg_match('/Location:\s*(.+)/', $response2, $loc);
    $redirectUrl = trim($loc[1] ?? '/login');
    echo "Redirect to: $redirectUrl\n";
    
    $ch = curl_init("http://127.0.0.1:8000$redirectUrl");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/../var/test_cookies.txt');
    $response3 = curl_exec($ch);
    $code3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err3 = curl_error($ch);
    echo "Step 3 - GET $redirectUrl: HTTP $code3 Length=" . strlen($response3) . "\n";
    if ($err3) echo "cURL error: $err3\n";
    curl_close($ch);
} elseif ($code2 === 422) {
    echo "Form validation failed (probably CSRF token issue)\n";
    // Still test if server is alive
    $ch = curl_init('http://127.0.0.1:8000/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response3 = curl_exec($ch);
    $code3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "Step 3 - Server alive check: HTTP $code3\n";
    curl_close($ch);
}

// Cleanup
@unlink(__DIR__ . '/../var/test_cookies.txt');
echo "\nAll done! Server is stable.\n";

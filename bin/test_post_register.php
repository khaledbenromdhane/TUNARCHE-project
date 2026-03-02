<?php
/**
 * Test the register POST endpoint with multipart/form-data
 */

$ch = curl_init('http://127.0.0.1:8000/register');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'user[nom]' => 'CurlTest',
    'user[prenom]' => 'CurlPrenom',
    'user[email]' => 'curltest_register@example.com',
    'user[telephone]' => '12345678',
    'user[password]' => 'Azerty123!',
    'user[role]' => 'ROLE_USER',
    'user[_token]' => 'test-token',
    'recaptcha_token' => '',
]);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);
echo "HTTP $code - Length: " . strlen($resp) . PHP_EOL;
if ($err) echo "cURL error: $err" . PHP_EOL;
if ($code !== 200 && $code !== 302) {
    // Check for validation errors
    if (preg_match_all('/class="text-danger[^"]*"[^>]*>([^<]+)</', $resp, $m)) {
        echo "Validation errors: " . implode(', ', array_filter(array_map('trim', $m[1]))) . PHP_EOL;
    }
}
echo "Done." . PHP_EOL;

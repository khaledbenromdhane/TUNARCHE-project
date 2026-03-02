<?php
// Simulate a POST to /register through Symfony kernel
require __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

$_SERVER['APP_ENV'] = 'dev';
$_SERVER['APP_DEBUG'] = '1';

echo "Booting kernel..." . PHP_EOL;
$kernel = new Kernel('dev', true);

echo "Creating GET request to /register..." . PHP_EOL;
$request = Request::create('/register', 'GET');

try {
    $response = $kernel->handle($request);
    echo "Status: " . $response->getStatusCode() . PHP_EOL;
    echo "Content length: " . strlen($response->getContent()) . PHP_EOL;
    echo "First 200 chars: " . substr(strip_tags($response->getContent()), 0, 200) . PHP_EOL;
} catch (\Throwable $e) {
    echo "EXCEPTION: " . get_class($e) . PHP_EOL;
    echo "Message: " . $e->getMessage() . PHP_EOL;
    echo "File: " . $e->getFile() . ":" . $e->getLine() . PHP_EOL;
    echo "Trace: " . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
}

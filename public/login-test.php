<?php

/**
 * Shows the REAL login error (upload to public/, open once, then DELETE).
 * https://tpi.turnkeyinfotech.live/login-test.php?key=tpi-fix-2026
 */
declare(strict_types=1);
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

header('Content-Type: text/plain; charset=utf-8');

if (($_GET['key'] ?? '') !== 'tpi-fix-2026') {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$email = $_GET['email'] ?? 'sales@tpi.com';
$password = $_GET['password'] ?? '12345678';

echo "Testing login for {$email}\n\n";

try {
    require __DIR__.'/../vendor/autoload.php';
    $app = require __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Kernel::class);

    $request = Request::create('/api/login', 'POST', [
        'email' => $email,
        'password' => $password,
        'device_name' => 'login-test',
    ], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/json',
    ]);

    // JSON body for Laravel
    $request = Request::create(
        '/api/login',
        'POST',
        [],
        [],
        [],
        [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ],
        json_encode([
            'email' => $email,
            'password' => $password,
            'device_name' => 'login-test',
        ])
    );

    $response = $kernel->handle($request);
    echo 'HTTP '.$response->getStatusCode()."\n";
    echo $response->getContent()."\n";
    $kernel->terminate($request, $response);
} catch (Throwable $e) {
    echo 'EXCEPTION: '.$e::class."\n";
    echo $e->getMessage()."\n";
    echo $e->getFile().':'.$e->getLine()."\n\n";
    echo $e->getTraceAsString()."\n";
}

<?php

function env(string $key, string $default = ''): string
{
    static $vars = null;

    if ($vars === null) {
        $vars = [];
        $envFile = __DIR__ . '/.env';
        if (is_file($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$k, $v] = explode('=', $line, 2);
                $vars[trim($k)] = trim($v, " \t\"'");
            }
        }
    }

    $value = getenv($key);
    if ($value === false || $value === '') {
        $value = $vars[$key] ?? '';
    }

    return $value !== '' ? $value : $default;
}

$adminPassword = env('ADMIN_PASSWORD', 'admin123');

return [
    'db' => [
        'driver'      => env('DB_DRIVER', 'mysql'),
        'host'        => env('DB_HOST', '127.0.0.1'),
        'port'        => env('DB_PORT', '3306'),
        'dbname'      => env('DB_NAME', 'sportshop'),
        'user'        => env('DB_USER', 'root'),
        'password'    => env('DB_PASSWORD', ''),
        'charset'     => 'utf8mb4',
        'sqlite_path' => __DIR__ . '/../database/sportshop.sqlite',
    ],

    'admin' => [
        'username'      => env('ADMIN_USER', 'admin'),
        'password_hash' => password_hash($adminPassword, PASSWORD_DEFAULT),
        'session_ttl'   => (int)env('ADMIN_SESSION_TTL', '7200'),
    ],

    'uploads' => [
        'dir'      => __DIR__ . '/../public/images/products',
        'max_size' => 5 * 1024 * 1024,
        'allowed'  => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
    ],

    'app' => [
        'name'  => env('APP_NAME', 'SportShop'),
        'debug' => env('APP_DEBUG', '1') === '1',
    ],
];

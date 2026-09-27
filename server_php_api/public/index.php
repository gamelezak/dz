<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    foreach (['core', 'models', 'controllers', 'helpers'] as $dir) {
        $file = APP_ROOT . '/' . $dir . '/' . $class . '.php';
        if (is_file($file)) {
            require_once $file;
            return;
        }
    }
});

$config = require APP_ROOT . '/config/config.php';
App::init($config);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

(new Router())->dispatch($method, $uri);

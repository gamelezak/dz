<?php

return [
    'db' => [
        'driver'   => 'mysql',
        'host'     => '127.0.0.1',
        'port'     => '3306',
        'dbname'   => 'sportshop',
        'user'     => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
        'sqlite_path' => __DIR__ . '/../database/sportshop.sqlite',
    ],

    'admin' => [
        'username'      => 'admin',
        'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
        'session_ttl'   => 7200,
    ],

    'uploads' => [
        'dir'      => __DIR__ . '/../public/images/products',
        'max_size' => 5 * 1024 * 1024,
        'allowed'  => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
    ],

    'app' => [
        'name'  => 'SportShop',
        'debug' => true,
    ],
];

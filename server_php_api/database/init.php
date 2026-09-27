<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/core/App.php';
require APP_ROOT . '/core/Response.php';
require APP_ROOT . '/core/Database.php';

$config = require APP_ROOT . '/config/config.php';
App::init($config);

$driver = $argv[1] ?? 'sqlite';
$pdo    = Database::pdo();

$schemaSqlite = <<<'SQL'
CREATE TABLE IF NOT EXISTS products (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT NOT NULL,
    price       REAL NOT NULL,
    description TEXT,
    image       TEXT,
    created_at  TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at  TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS product_images (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    filename   TEXT NOT NULL,
    sort_order INTEGER DEFAULT 0
);
CREATE TABLE IF NOT EXISTS admin_sessions (
    token      TEXT PRIMARY KEY,
    username   TEXT NOT NULL,
    expires_at TEXT NOT NULL
);
SQL;

$tables = ['products', 'product_images', 'admin_sessions'];
$already = [];
foreach ($tables as $t) {
    try {
        $pdo->query("SELECT 1 FROM $t LIMIT 1");
        $already[] = $t;
    } catch (Throwable $e) {  }
}

if ($driver === 'sqlite') {
    if (!in_array('products', $already, true)) {
        $pdo->exec('PRAGMA foreign_keys = ON');
        foreach (array_filter(explode(';', $schemaSqlite)) as $stmt) {
            if (trim($stmt) !== '') $pdo->exec($stmt);
        }
        echo "SQLite: таблицы созданы в {$config['db']['sqlite_path']}\n";
    } else {
        echo "SQLite: таблицы уже существуют\n";
    }
} else {
    if (!in_array('products', $already, true)) {
        $schema = file_get_contents(APP_ROOT . '/database/schema.sql');
        $schema = preg_replace('/^--.*$/m', '', $schema);
        $schema = preg_replace('/^(CREATE DATABASE|USE)\b.*$/im', '', $schema);
        foreach (array_filter(array_map('trim', explode(';', $schema))) as $stmt) {
            $pdo->exec($stmt);
        }
        echo "MySQL: таблицы созданы из database/schema.sql.\n";
    } else {
        echo "MySQL: таблицы уже существуют.\n";
    }
    foreach ($tables as $t) {
        echo "  [ok] $t\n";
    }
}

$count = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
if ($count === 0) {
    $imgDir = $config['uploads']['dir'];
    $demo = [
        ['Гантели 10 кг (пара)', 1990.00, 'Виниловые гантели для домашних тренировок.', 'ddcfe18949b6d8e1.png'],
        ['Футбольный мяч', 1799.00, 'Мяч размер 5, машинная сшивка.', 'bc695d10f188797c.png'],
        ['Коврик для йоги', 990.00, 'Нескользящий TPE-коврик 183x61 см.', null],
    ];
    $ins = $pdo->prepare('INSERT INTO products (name, price, description, image) VALUES (?,?,?,?)');
    foreach ($demo as $d) {
        if ($d[3] && !is_file("$imgDir/{$d[3]}")) $d[3] = null;
        $ins->execute($d);
    }
    $pid = (int)$pdo->query('SELECT id FROM products WHERE name LIKE \'Гантели%\'')->fetchColumn();
    if ($pid && is_file("$imgDir/7adbbe240d93a7e2.png")) {
        $pdo->prepare('INSERT INTO product_images (product_id, filename, sort_order) VALUES (?, ?, 1)')
            ->execute([$pid, '7adbbe240d93a7e2.png']);
    }
    echo "Добавлены демо-товары.\n";
} else {
    echo "Товары уже есть ($count шт.) — демо не добавляем.\n";
}
echo "Готово.\n";

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
    stock       INTEGER NOT NULL DEFAULT 0,
    description TEXT,
    image       TEXT,
    created_at  TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at  TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT NOT NULL UNIQUE,
    email         TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role          TEXT NOT NULL DEFAULT 'user' CHECK (role IN ('user','manager','admin')),
    created_at    TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS orders (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    username   TEXT NOT NULL,
    phone      TEXT NOT NULL,
    address    TEXT NOT NULL,
    comment    TEXT,
    total      REAL NOT NULL,
    status     TEXT NOT NULL DEFAULT 'new' CHECK (status IN ('new','confirmed','done','canceled')),
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS order_items (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id   INTEGER NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
    product_id INTEGER NOT NULL REFERENCES products(id) ON DELETE CASCADE,
    name       TEXT NOT NULL,
    price      REAL NOT NULL,
    qty        INTEGER NOT NULL
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

$tables  = ['products', 'users', 'orders', 'order_items', 'product_images', 'admin_sessions'];
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
    // Миграция для старых SQLite-БД: недостающие таблицы создаём поштучно.
    foreach (['users', 'orders', 'order_items'] as $t) {
        if (in_array($t, $already, true)) continue;
        if (preg_match('/CREATE TABLE IF NOT EXISTS ' . $t . ' \((.*?)\n\);/s', $schemaSqlite, $m)) {
            $pdo->exec("CREATE TABLE IF NOT EXISTS $t ($m[1]\n)");
            echo "SQLite: создана таблица $t.\n";
        }
    }
    try {
        $cols = $pdo->query('PRAGMA table_info(products)')->fetchAll(PDO::FETCH_COLUMN, 1);
        if (!in_array('stock', (array)$cols, true)) {
            $pdo->exec('ALTER TABLE products ADD COLUMN stock INTEGER NOT NULL DEFAULT 0');
            echo "SQLite: добавлена колонка products.stock.\n";
        }
    } catch (Throwable $e) { }
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
    // Миграция для старых MySQL-БД: недостающие таблицы и колонки.
    foreach (['users', 'orders', 'order_items'] as $t) {
        if (in_array($t, $already, true)) continue;
        $schema = file_get_contents(APP_ROOT . '/database/schema.sql');
        if (preg_match('/CREATE TABLE IF NOT EXISTS ' . $t . ' \(.*?\)\s*ENGINE[^;]*/is', $schema, $m)) {
            $pdo->exec($m[0]);
            echo "MySQL: создана таблица $t.\n";
        }
    }
    try {
        $cols = $pdo->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('stock', (array)$cols, true)) {
            $pdo->exec('ALTER TABLE products ADD COLUMN stock INT NOT NULL DEFAULT 0 AFTER price');
            echo "MySQL: добавлена колонка products.stock.\n";
        }
    } catch (Throwable $e) { }
    foreach ($tables as $t) {
        echo "  [ok] $t\n";
    }
}

// Пользователи по умолчанию (admin / manager). Пароли — из config/.env.
foreach (['seed_admin', 'seed_manager'] as $key) {
    $seed = $config['auth'][$key] ?? null;
    if (!$seed) continue;
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = :u OR email = :e');
    $stmt->execute([':u' => $seed['username'], ':e' => $seed['email']]);
    if ((int)$stmt->fetchColumn() === 0) {
        $insUser = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role) VALUES (:u, :e, :p, :r)'
        );
        $insUser->execute([
            ':u' => $seed['username'],
            ':e' => $seed['email'],
            ':p' => password_hash((string)$seed['password'], PASSWORD_DEFAULT),
            ':r' => $seed['role'],
        ]);
        echo "Создан пользователь «{$seed['username']}» с ролью «{$seed['role']}».\n";
    }
}

$count = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
if ($count === 0) {
    $imgDir = $config['uploads']['dir'];
    $demo = [
        ['Гантели 10 кг (пара)', 1990.00, 25, 'Виниловые гантели для домашних тренировок.', 'ddcfe18949b6d8e1.png'],
        ['Футбольный мяч', 1799.00, 40, 'Мяч размер 5, машинная сшивка.', 'bc695d10f188797c.png'],
        ['Коврик для йоги', 990.00, 15, 'Нескользящий TPE-коврик 183x61 см.', null],
    ];
    $ins = $pdo->prepare('INSERT INTO products (name, price, stock, description, image) VALUES (?,?,?,?,?)');
    foreach ($demo as $d) {
        if ($d[4] && !is_file("$imgDir/{$d[4]}")) $d[4] = null;
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

<?php

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $cfg    = App::config('db');
        $driver = $cfg['driver'] ?? 'mysql';

        try {
            if ($driver === 'sqlite') {
                $dsn = 'sqlite:' . $cfg['sqlite_path'];
                $pdo = new PDO($dsn);
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    $cfg['host'], $cfg['port'], $cfg['dbname'], $cfg['charset']
                );
                $pdo = new PDO($dsn, $cfg['user'], $cfg['password']);
            }

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            self::$pdo = $pdo;
            return $pdo;
        } catch (PDOException $e) {
            Response::json([
                'detail' => 'Ошибка подключения к базе данных (' . $driver . '): ' . $e->getMessage(),
                'hint'   => 'Проверьте, что OpenServer запущен (MySQL), БД sportshop импортирована '
                          . 'через phpMyAdmin (database/schema.sql), а доступные настройки — в config/config.php.',
            ], 500);
        }
    }
}

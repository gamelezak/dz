<?php

/**
 * Авторизация, сессии и проверка ролей.
 *
 * Иерархия ролей (каждая роль включает права предыдущей):
 *   user    — покупатель;
 *   manager — управление товарами;
 *   admin   — всё, включая управление пользователями и их ролями.
 */
class Auth
{
    public const ROLE_USER    = 'user';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_ADMIN   = 'admin';

    private const LEVELS = [
        self::ROLE_USER    => 1,
        self::ROLE_MANAGER => 2,
        self::ROLE_ADMIN   => 3,
    ];

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /** Текущий пользователь из сессии (с проверкой времени жизни) или null. */
    public static function user(): ?array
    {
        self::startSession();
        if (empty($_SESSION['user'])) {
            return null;
        }
        $ttl    = (int)App::config('auth.session_ttl');
        $logged = (int)($_SESSION['login_time'] ?? 0);
        if ($ttl > 0 && (time() - $logged) > $ttl) {
            self::logout();
            return null;
        }
        return $_SESSION['user'];
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u !== null ? (int)$u['id'] : null;
    }

    public static function username(): string
    {
        $u = self::user();
        return $u !== null ? (string)$u['username'] : '';
    }

    public static function role(): string
    {
        $u = self::user();
        return $u !== null ? (string)($u['role'] ?? self::ROLE_USER) : '';
    }

    public static function is(string $role): bool
    {
        return self::role() === $role;
    }

    public static function roleLevel(string $role): int
    {
        return self::LEVELS[$role] ?? 0;
    }

    /** Достаточно ли прав у текущего пользователя для требуемой роли. */
    public static function can(string $requiredRole): bool
    {
        return self::roleLevel(self::role()) >= self::roleLevel($requiredRole);
    }

    /** Вход: сохраняем пользователя в сессию и обновляем идентификатор. */
    public static function login(array $user): void
    {
        self::startSession();
        session_regenerate_id(true);
        unset($_SESSION['flash'], $_SESSION['old']);
        $_SESSION['user'] = [
            'id'       => (int)$user['id'],
            'username' => (string)$user['username'],
            'email'    => (string)($user['email'] ?? ''),
            'role'     => (string)($user['role'] ?? self::ROLE_USER),
        ];
        $_SESSION['login_time'] = time();
    }

    /** Обновление данных пользователя в активной сессии (например, после смены роли). */
    public static function refresh(): void
    {
        self::startSession();
        $id = self::id();
        if ($id === null) return;
        $fresh = (new UserModel())->find($id);
        if ($fresh === null) {
            self::logout();
            return;
        }
        $_SESSION['user'] = $fresh;
    }

    public static function logout(): void
    {
        self::startSession();
        unset($_SESSION['user'], $_SESSION['login_time'], $_SESSION['flash'], $_SESSION['old']);
    }

    /** Защита HTML-страницы: нет сессии — редирект на логин, не хватает роли — 403. */
    public static function requireRoleHtml(string $role, string $loginUrl = '/login'): void
    {
        if (!self::check()) {
            header('Location: ' . $loginUrl, true, 302);
            exit;
        }
        if (!self::can($role)) {
            View::renderPage('forbidden', [
                'pageTitle' => 'Нет доступа — SportShop',
                'role'      => $role,
            ]);
        }
    }
}

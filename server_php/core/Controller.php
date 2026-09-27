<?php

abstract class Controller
{
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    /**
     * Требование авторизации для JSON-API.
     * Токен берётся из параметра ?token= или заголовка X-Admin-Token,
     * либо используется PHP-сессия браузера.
     */
    protected function requireRole(string $role): array
    {
        $token   = (string)($this->request->input('token') ?: ($_SERVER['HTTP_X_ADMIN_TOKEN'] ?? ''));
        $session = $token !== '' ? (new AdminSessionModel())->findValid($token) : null;

        if ($session !== null) {
            $user = (new UserModel())->findByUsername($session['username']);
            if ($user !== null && Auth::roleLevel((string)$user['role']) >= Auth::roleLevel($role)) {
                return $user;
            }
        }

        if (Auth::check() && Auth::can($role)) {
            $user = (new UserModel())->findByUsername(Auth::username());
            if ($user !== null) {
                return $user;
            }
        }

        Response::json(['detail' => 'Требуется авторизация с ролью не ниже «' . $role . '»'], 401);
    }

    protected function requireAdmin(): array
    {
        return $this->requireRole(Auth::ROLE_MANAGER);
    }
}

<?php

/**
 * JSON-API входа/выхода сотрудников (manager/admin).
 * Возвращает токен сессии, который передаётся через ?token= или заголовок X-Admin-Token.
 */
class AdminAuthController extends Controller
{
    public function login(): void
    {
        $username = trim((string)$this->request->input('username', ''));
        $password = (string)$this->request->input('password', '');

        $user = (new UserModel())->findByUsername($username);
        if ($user === null || !password_verify($password, (string)$user['password_hash'])) {
            Response::json(['detail' => 'Неверный логин или пароль'], 401);
        }
        if (Auth::roleLevel((string)$user['role']) < Auth::roleLevel(Auth::ROLE_MANAGER)) {
            Response::json(['detail' => 'Недостаточно прав: требуется роль manager или admin'], 403);
        }

        $ttl   = max(600, (int)App::config('auth.session_ttl'));
        $token = (new AdminSessionModel())->create($username, $ttl);

        Response::json([
            'token'      => $token,
            'expires_in' => $ttl,
            'user'       => ['username' => $username, 'role' => $user['role']],
        ]);
    }

    public function logout(): void
    {
        $token = (string)$this->request->input('token', '');
        if ($token !== '') {
            (new AdminSessionModel())->destroy($token);
        }
        Response::json(['ok' => true]);
    }
}

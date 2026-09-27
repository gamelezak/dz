<?php

class AdminAuthController extends Controller
{
    public function login(): void
    {
        $username = trim((string)$this->request->input('username', ''));
        $password = (string)$this->request->input('password', '');

        $okUser = hash_equals((string)App::config('admin.username'), $username);
        $okPass = password_verify($password, (string)App::config('admin.password_hash'));

        if (!$okUser || !$okPass) {
            Response::json(['detail' => 'Неверный логин или пароль'], 401);
        }

        $ttl   = (int)App::config('admin.session_ttl');
        $token = (new AdminSessionModel())->create($username, $ttl);

        Response::json(['token' => $token, 'expires_in' => $ttl]);
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

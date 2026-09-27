<?php

abstract class Controller
{
    protected Request $request;

    public function __construct()
    {
        $this->request = new Request();
    }

    protected function requireAdmin(): void
    {
        $token = $this->request->input('token') ?: '';
        if ($token === '' && isset($_SERVER['HTTP_X_ADMIN_TOKEN'])) {
            $token = $_SERVER['HTTP_X_ADMIN_TOKEN'];
        }
        $session = (new AdminSessionModel())->findValid($token);
        if ($session === null) {
            Response::json(['detail' => 'Требуется авторизация администратора'], 401);
        }
    }
}

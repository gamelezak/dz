<?php

/**
 * Регистрация и авторизация пользователей (HTML-формы).
 * Роли: user < manager < admin (см. core/Auth.php).
 */
class AuthController extends Controller
{
    private UserModel $users;

    public function __construct()
    {
        parent::__construct();
        $this->users = new UserModel();
        Auth::startSession();
    }

    private function flash(string $msg, string $type = 'ok'): void
    {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
    }

    private function pullFlash(): ?array
    {
        $f = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $f;
    }

    private function redirect(string $url): never
    {
        header('Location: ' . $url, true, 302);
        exit;
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::can(Auth::ROLE_MANAGER) ? '/admin' : '/account');
        }
        View::renderPage('login', [
            'pageTitle' => 'Вход — SportShop',
            'flash'     => $this->pullFlash(),
        ]);
    }

    public function login(): void
    {
        $identity = trim((string)$this->request->input('username', ''));
        $password = (string)$this->request->input('password', '');

        $user = str_contains($identity, '@')
            ? $this->users->findByEmail($identity)
            : $this->users->findByUsername($identity);

        if (!$this->users->verify($user, $password)) {
            $this->flash('Неверный логин или пароль', 'err');
            $this->redirect('/login');
        }

        Auth::login($user);
        $this->flash('Вы вошли как ' . $user['username']);
        $this->redirect(Auth::roleLevel((string)$user['role']) >= Auth::roleLevel(Auth::ROLE_MANAGER) ? '/admin' : '/account');
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirect('/account');
        }
        $flash  = $this->pullFlash();
        $errors = is_array($flash) && array_is_list($flash) ? $flash : [];
        View::renderPage('register', [
            'pageTitle' => 'Регистрация — SportShop',
            'errors'    => $errors,
            'old'       => $_SESSION['old'] ?? [],
        ]);
        unset($_SESSION['old']);
    }

    /** Открытая регистрация: всегда с ролью user. */
    public function register(): void
    {
        [$data, $errors] = $this->readUserForm();

        if ($errors) {
            $_SESSION['flash'] = $errors;
            $_SESSION['old']   = $data;
            $this->redirect('/register');
        }

        $id   = $this->users->create($data['username'], $data['email'], $data['password'], Auth::ROLE_USER);
        $user = $this->users->find($id);
        Auth::login($user);
        $this->flash('Аккаунт создан. Вы зарегистрированы с ролью «user».');
        $this->redirect('/account');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->flash('Вы вышли из аккаунта');
        $this->redirect('/login');
    }

    /** Личный кабинет покупателя: данные аккаунта + история заказов. */
    public function account(): void
    {
        Auth::requireRoleHtml(Auth::ROLE_USER);

        $orders = [];
        $detail = null;
        if ($orderId = (int)$this->request->query('order', 0)) {
            $order = (new OrderModel())->find($orderId);
            // Смотреть заказ может только его владелец (или менеджер/админ).
            if ($order !== null &&
                ((int)$order['user_id'] === (int)Auth::id() || Auth::can(Auth::ROLE_MANAGER))) {
                $detail = $order;
            }
        } else {
            $orders = (new OrderModel())->forUser((int)Auth::id());
        }

        View::renderPage('account', [
            'pageTitle' => 'Личный кабинет — SportShop',
            'user'      => Auth::user(),
            'flash'     => $this->pullFlash(),
            'orders'    => $orders,
            'detail'    => $detail,
        ]);
    }

    /** Валидация формы регистрации; возвращает [data, errors]. */
    private function readUserForm(): array
    {
        $username = trim((string)$this->request->input('username', ''));
        $email    = strtolower(trim((string)$this->request->input('email', '')));
        $password = (string)$this->request->input('password', '');
        $errors   = [];

        if ($username === '' || !preg_match('/^[a-zA-Z0-9_.-]{3,64}$/', $username)) {
            $errors[] = 'Логин: 3-64 символа, допустимы латиница, цифры, «_», «.», «-».';
        } elseif ($this->users->findByUsername($username) !== null) {
            $errors[] = 'Пользователь с таким логином уже существует.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Укажите корректный email.';
        } elseif ($this->users->findByEmail($email) !== null) {
            $errors[] = 'Пользователь с таким email уже существует.';
        }

        if (strlen($password) < 6) {
            $errors[] = 'Пароль должен содержать минимум 6 символов.';
        }

        return [['username' => $username, 'email' => $email, 'password' => $password], $errors];
    }
}

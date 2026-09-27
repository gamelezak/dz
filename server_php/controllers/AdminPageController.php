<?php

class AdminPageController extends Controller
{
    private ProductModel $products;

    public function __construct()
    {
        parent::__construct();
        $this->products = new ProductModel();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function isAuthed(): bool
    {
        return !empty($_SESSION['admin_user'])
            && (time() - (int)($_SESSION['admin_time'] ?? 0)) < (int)App::config('admin.session_ttl');
    }

    private function requireAdminHtml(): void
    {
        if (!$this->isAuthed()) {
            header('Location: /admin/login', true, 302);
            exit;
        }
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

    public function dashboard(): void
    {
        $this->requireAdminHtml();
        View::renderPage('admin_dashboard', [
            'pageTitle' => 'Админка — SportShop',
            'products'  => $this->products->all(),
            'flash'     => $this->pullFlash(),
            'adminUser' => $_SESSION['admin_user'],
        ]);
    }

    public function showLogin(): void
    {
        if ($this->isAuthed()) {
            header('Location: /admin', true, 302);
            exit;
        }
        View::renderPage('admin_login', [
            'pageTitle' => 'Вход в админку — SportShop',
            'error'     => $this->pullFlash(),
        ]);
    }

    public function login(): void
    {
        $username = trim((string)$this->request->input('username', ''));
        $password = (string)$this->request->input('password', '');

        $okUser = hash_equals((string)App::config('admin.username'), $username);
        $okPass = password_verify($password, (string)App::config('admin.password_hash'));

        if ($okUser && $okPass) {
            session_regenerate_id(true);
            $_SESSION['admin_user'] = $username;
            $_SESSION['admin_time'] = time();
            header('Location: /admin', true, 302);
            exit;
        }

        $this->flash('Неверный логин или пароль', 'err');
        header('Location: /admin/login', true, 302);
        exit;
    }

    public function logout(): void
    {
        unset($_SESSION['admin_user'], $_SESSION['admin_time'], $_SESSION['flash']);
        session_destroy();
        header('Location: /admin/login', true, 302);
        exit;
    }

    public function showCreate(): void
    {
        $this->requireAdminHtml();
        $flash = $this->pullFlash(); 

        View::renderPage('admin_form', [
            'pageTitle' => 'Новый товар — админка SportShop',
            'product'   => null,
            'errors'    => is_array($flash) && array_is_list($flash) ? $flash : [],
            'old'       => $_SESSION['old'] ?? [],
        ]);
        unset($_SESSION['old']);
    }

    public function store(): void
    {
        $this->requireAdminHtml();

        [$data, $errors] = $this->readProductForm();
        $image = null;
        $extra = [];

        try {
            if ($this->request->hasFile('image')) {
                $image = ImageUploader::save($this->request->files('image')[0]);
            } else {
                $errors[] = 'Загрузите картинку товара.';
            }
            foreach ($this->request->files('extra_images') as $f) {
                $extra[] = ImageUploader::save($f); 

            }
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }

        if ($errors) {
            ImageUploader::removeAll(array_merge($extra, $image ? [$image] : []));
            $_SESSION['flash'] = $errors; 

            $_SESSION['old']   = $data;
            header('Location: /admin/products/new', true, 302);
            exit;
        }

        $this->products->create($data['name'], $data['price'], $data['description'], $image, $extra);
        $this->flash('Товар «' . $data['name'] . '» добавлен.');
        header('Location: /admin', true, 302);
        exit;
    }

    public function showEdit(int $id): void
    {
        $this->requireAdminHtml();
        $product = $this->products->find($id);
        if ($product === null) {
            $this->flash('Товар не найден', 'err');
            header('Location: /admin', true, 302);
            exit;
        }
        $flash = $this->pullFlash();
        View::renderPage('admin_form', [
            'pageTitle' => 'Редактирование — ' . $product['name'],
            'product'   => $product,
            'errors'    => is_array($flash) ? $flash : [],
            'old'       => $_SESSION['old'] ?? [],
        ]);
        unset($_SESSION['old']);
    }

    public function update(int $id): void
    {
        $this->requireAdminHtml();

        $old = $this->products->find($id);
        if ($old === null) {
            $this->flash('Товар не найден', 'err');
            header('Location: /admin', true, 302);
            exit;
        }

        [$data, $errors] = $this->readProductForm();
        $fields      = ['name' => $data['name'], 'price' => $data['price'], 'description' => $data['description']];
        $extraAppend = [];

        try {
            if ($this->request->hasFile('image')) {
                $new = ImageUploader::save($this->request->files('image')[0]);
                if ($old['image']) {
                    ImageUploader::remove($old['image']); 

                }
                $fields['image'] = $new;
            }
            foreach ($this->request->files('extra_images') as $f) {
                $extraAppend[] = ImageUploader::save($f);
            }
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }

        if ($errors) {
            ImageUploader::removeAll($extraAppend);
            $_SESSION['flash'] = $errors;
            $_SESSION['old']   = $data;
            header("Location: /admin/products/$id/edit", true, 302);
            exit;
        }

        $this->products->update($id, $fields, $extraAppend);
        $this->flash('Товар обновлён.');
        header('Location: /admin', true, 302);
        exit;
    }

    public function destroy(int $id): void
    {
        $this->requireAdminHtml();

        $files = $this->products->delete($id);
        if (!$files && $this->products->find($id) === null) {
            $this->flash('Товар не найден', 'err');
        } else {
            foreach ($files as $f) {
                ImageUploader::remove($f);
            }
            $this->flash('Товар удалён.');
        }
        header('Location: /admin', true, 302);
        exit;
    }

    private function readProductForm(): array
    {
        $name  = trim((string)$this->request->input('name', ''));
        $price = $this->request->input('price');
        $desc  = trim((string)$this->request->input('description', ''));
        $errors = [];

        if ($name === '') {
            $errors[] = 'Укажите название товара.';
        } elseif ((function_exists('mb_strlen') ? mb_strlen($name) : strlen($name)) > 255) {
            $errors[] = 'Название слишком длинное (до 255 символов).';
        }
        if ($price === null || $price === '' || !is_numeric($price)) {
            $errors[] = 'Укажите цену товара числом.';
        } elseif ((float)$price < 0) {
            $errors[] = 'Цена не может быть отрицательной.';
        }

        return [['name' => $name, 'price' => round((float)$price, 2), 'description' => $desc], $errors];
    }
}

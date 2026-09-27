<?php

/**
 * HTML-страницы админки. Доступ по ролям:
 *   /admin            — manager и admin (управление товарами);
 *   /admin/users      — только admin (распределение ролей).
 */
class AdminPageController extends Controller
{
    private ProductModel $products;
    private UserModel $users;
    private OrderModel $orders;

    public function __construct()
    {
        parent::__construct();
        $this->products = new ProductModel();
        $this->users    = new UserModel();
        $this->orders   = new OrderModel();
        Auth::startSession();
    }

    private function requireManager(): void
    {
        Auth::requireRoleHtml(Auth::ROLE_MANAGER);
    }

    private function requireAdminRole(): void
    {
        Auth::requireRoleHtml(Auth::ROLE_ADMIN);
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

    public function dashboard(): void
    {
        $this->requireManager();
        View::renderPage('admin_dashboard', [
            'pageTitle' => 'Админка — SportShop',
            'products'  => $this->products->all(),
            'flash'     => $this->pullFlash(),
            'adminUser' => Auth::user(),
        ]);
    }

    /** Список заказов (manager и admin). */
    public function orders(): void
    {
        $this->requireManager();
        View::renderPage('admin_orders', [
            'pageTitle' => 'Заказы — админка SportShop',
            'orders'    => $this->orders->all(),
            'statuses'  => OrderModel::STATUSES,
            'flash'     => $this->pullFlash(),
            'adminUser' => Auth::user(),
        ]);
    }

    /** Смена статуса заказа (manager и admin). */
    public function updateOrderStatus(int $id): void
    {
        $this->requireManager();

        $status = trim((string)$this->request->input('status', ''));
        if ($this->orders->setStatus($id, $status)) {
            $this->flash('Статус заказа №' . $id . ' изменён на «' . $status . '».');
        } else {
            $this->flash('Не удалось изменить статус (заказ не найден или недопустимый статус)', 'err');
        }
        $this->redirect('/admin/orders');
    }

    /** Список пользователей и распределение ролей (только admin). */
    public function users(): void
    {
        $this->requireAdminRole();
        View::renderPage('admin_users', [
            'pageTitle' => 'Пользователи — админка SportShop',
            'users'     => $this->users->all(),
            'flash'     => $this->pullFlash(),
            'adminUser' => Auth::user(),
            'roles'     => UserModel::ROLES,
        ]);
    }

    /** Смена роли пользователя (только admin). */
    public function updateUserRole(int $id): void
    {
        $this->requireAdminRole();

        $role = trim((string)$this->request->input('role', ''));
        $user = $this->users->find($id);

        if ($user === null) {
            $this->flash('Пользователь не найден', 'err');
        } elseif (!in_array($role, UserModel::ROLES, true)) {
            $this->flash('Недопустимая роль: ' . $role, 'err');
        } elseif ($id === Auth::id() && $role !== Auth::ROLE_ADMIN) {
            $this->flash('Нельзя понизить собственную роль', 'err');
        } else {
            $this->users->setRole($id, $role);
            if ($id === Auth::id()) {
                Auth::refresh();
            }
            $this->flash('Роль пользователя «' . $user['username'] . '» изменена на «' . $role . '».');
        }
        $this->redirect('/admin/users');
    }

    public function showCreate(): void
    {
        $this->requireManager();
        $flash = $this->pullFlash();

        View::renderPage('admin_form', [
            'pageTitle' => 'Новый товар — админка SportShop',
            'product'   => null,
            'errors'    => is_array($flash) && array_is_list($flash) ? $flash : [],
            'old'       => $_SESSION['old'] ?? [],
            'adminUser' => Auth::user(),
        ]);
        unset($_SESSION['old']);
    }

    public function store(): void
    {
        $this->requireManager();

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
            $this->redirect('/admin/products/new');
        }

        $this->products->create($data['name'], $data['price'], $data['description'], $image, $extra);
        $this->flash('Товар «' . $data['name'] . '» добавлен.');
        $this->redirect('/admin');
    }

    public function showEdit(int $id): void
    {
        $this->requireManager();
        $product = $this->products->find($id);
        if ($product === null) {
            $this->flash('Товар не найден', 'err');
            $this->redirect('/admin');
        }
        $flash = $this->pullFlash();
        View::renderPage('admin_form', [
            'pageTitle' => 'Редактирование — ' . $product['name'],
            'product'   => $product,
            'errors'    => is_array($flash) ? $flash : [],
            'old'       => $_SESSION['old'] ?? [],
            'adminUser' => Auth::user(),
        ]);
        unset($_SESSION['old']);
    }

    public function update(int $id): void
    {
        $this->requireManager();

        $old = $this->products->find($id);
        if ($old === null) {
            $this->flash('Товар не найден', 'err');
            $this->redirect('/admin');
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
            $this->redirect("/admin/products/$id/edit");
        }

        $this->products->update($id, $fields, $extraAppend);
        $this->flash('Товар обновлён.');
        $this->redirect('/admin');
    }

    public function destroy(int $id): void
    {
        $this->requireManager();

        $files = $this->products->delete($id);
        if (!$files && $this->products->find($id) === null) {
            $this->flash('Товар не найден', 'err');
        } else {
            foreach ($files as $f) {
                ImageUploader::remove($f);
            }
            $this->flash('Товар удалён.');
        }
        $this->redirect('/admin');
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

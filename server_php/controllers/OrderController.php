<?php

/**
 * Оформление заказов: форма, создание заказа из корзины,
 * страница подтверждения и отмена заказа покупателем.
 */
class OrderController extends Controller
{
    private OrderModel $orders;

    public function __construct()
    {
        parent::__construct();
        $this->orders = new OrderModel();
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

    /** Форма оформления заказа (только для авторизованных). */
    public function showCheckout(): void
    {
        if (!Auth::check()) {
            $this->flash('Войдите, чтобы оформить заказ', 'err');
            $this->redirect('/login');
        }

        $user  = Auth::user();
        $flash = $this->pullFlash();
        $errors = is_array($flash) && array_is_list($flash) ? $flash : [];

        View::renderPage('checkout', [
            'pageTitle' => 'Оформление заказа — SportShop',
            'errors'    => $errors,
            'old'       => $_SESSION['old'] ?? [],
            'user'      => $user,
        ]);
        unset($_SESSION['old']);
    }

    /** Приём формы: валидация, создание заказа, редирект на страницу успеха. */
    public function create(): void
    {
        if (!Auth::check()) {
            $this->flash('Войдите, чтобы оформить заказ', 'err');
            $this->redirect('/login');
        }

        $name    = trim((string)$this->request->input('name', ''));
        $phone   = trim((string)$this->request->input('phone', ''));
        $address = trim((string)$this->request->input('address', ''));
        $comment = trim((string)$this->request->input('comment', ''));

        // Состав заказа приходит из корзины localStorage в скрытых полях items[].
        $rawItems = (array)$this->request->input('items', []);
        $items    = [];
        foreach ($rawItems as $it) {
            if (!is_array($it)) continue;
            $pid = (int)($it['id'] ?? 0);
            $qty = max(1, (int)($it['qty'] ?? 0));
            if ($pid > 0) {
                // Суммируем дубликаты одного товара.
                $items[$pid] = ($items[$pid] ?? 0) + $qty;
            }
        }
        $items = array_map(fn ($pid, $qty) => ['id' => $pid, 'qty' => $qty], array_keys($items), $items);

        $errors = [];
        if ($name === '' || mb_strlen($name) < 2 || mb_strlen($name) > 128) {
            $errors[] = 'Укажите имя получателя (2-128 символов).';
        }
        if (!preg_match('/^[\d\s()+\-]{7,32}$/', $phone)) {
            $errors[] = 'Телефон: 7-32 символа, допустимы цифры, скобки, «+» и «-».';
        }
        if ($address === '' || mb_strlen($address) > 255) {
            $errors[] = 'Укажите адрес доставки (до 255 символов).';
        }
        if (mb_strlen($comment) > 500) {
            $errors[] = 'Комментарий слишком длинный (до 500 символов).';
        }
        if (!$items) {
            $errors[] = 'Корзина пуста — добавьте товары перед оформлением.';
        }

        if ($errors) {
            $_SESSION['flash'] = $errors;
            $_SESSION['old']   = compact('name', 'phone', 'address', 'comment') + ['items' => $rawItems];
            $this->redirect('/checkout');
        }

        try {
            $orderId = $this->orders->create(
                (int)Auth::id(),
                $name,
                $phone,
                $address,
                $comment,
                $items
            );
        } catch (RuntimeException $e) {
            $_SESSION['flash'] = [$e->getMessage()];
            $_SESSION['old']   = compact('name', 'phone', 'address', 'comment') + ['items' => $rawItems];
            $this->redirect('/checkout');
        }

        $this->flash('Заказ №' . $orderId . ' успешно оформлен.');
        $this->redirect('/order/' . $orderId);
    }

    /** Страница успешного оформления / детали заказа. */
    public function show(int $id): void
    {
        Auth::requireRoleHtml(Auth::ROLE_USER);

        $order = $this->orders->find($id);
        if ($order === null ||
            ((int)$order['user_id'] !== (int)Auth::id() && !Auth::can(Auth::ROLE_MANAGER))) {
            View::renderPage('404', ['pageTitle' => 'SportShop — заказ не найден']);
        }

        View::renderPage('order_success', [
            'pageTitle' => 'Заказ №' . $order['id'] . ' — SportShop',
            'order'     => $order,
            'flash'     => $this->pullFlash(),
        ]);
    }

    /** Отмена своего ещё не подтверждённого заказа. */
    public function cancel(int $id): void
    {
        Auth::requireRoleHtml(Auth::ROLE_USER);

        $order = $this->orders->find($id);
        if ($order === null || (int)$order['user_id'] !== (int)Auth::id()) {
            View::renderPage('404', ['pageTitle' => 'SportShop — заказ не найден']);
        }

        if ($order['status'] !== OrderModel::STATUS_NEW) {
            $this->flash('Отменить можно только заказ со статусом «new»', 'err');
            $this->redirect('/order/' . $id);
        }

        // Отменяем заказ и возвращаем списанные остатки товаров.
        $this->orders->cancel($id);
        $this->flash('Заказ №' . $id . ' отменён, товары возвращены в наличие.');
        $this->redirect('/account');
    }
}

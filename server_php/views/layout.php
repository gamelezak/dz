<?php
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
$__user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'SportShop') ?></title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<header>
    <a class="logo" href="/">Sport<span>Shop</span></a>
    <nav>
        <a href="/">Каталог</a>
        <a href="/cart">Корзина (<span class="cart-count">0</span>)</a>
        <?php if ($__user): ?>
            <?php if (in_array($__user['role'] ?? '', ['manager', 'admin'], true)): ?>
                <a href="/admin">Админка</a>
            <?php endif; ?>
            <a href="/account"><?= htmlspecialchars($__user['username'], ENT_QUOTES) ?></a>
            <a href="/logout">Выйти</a>
        <?php else: ?>
            <a href="/login">Войти</a>
            <a href="/register">Регистрация</a>
        <?php endif; ?>
    </nav>
</header>

<main>
<?= $content ?>
</main>

<footer>© 2026 SportShop — спортивные товары для активных</footer>

<script>

const IMG_BASE = '/images/products/';
function imgUrl(name) { return name ? IMG_BASE + encodeURIComponent(name) : ''; }
function fmtPrice(v) { return Number(v).toLocaleString('ru-RU') + ' ₽'; }

const CART_KEY = 'sportshop_cart';
function loadCart() { try { return JSON.parse(localStorage.getItem(CART_KEY)) || []; } catch { return []; } }
function saveCart(cart) { localStorage.setItem(CART_KEY, JSON.stringify(cart)); updateCartBadge(); }
function addToCart(p) {
    const cart = loadCart();
    const item = cart.find(i => i.id === p.id);
    if (item) item.qty += 1; else cart.push({ id: p.id, name: p.name, price: p.price, image: p.image, qty: 1 });
    saveCart(cart);
}
function removeFromCart(id) { saveCart(loadCart().filter(i => i.id !== id)); }
function changeQty(id, d) {
    const cart = loadCart(); const it = cart.find(i => i.id === id);
    if (!it) return; it.qty += d;
    if (it.qty <= 0) removeFromCart(id); else saveCart(cart);
}
function updateCartBadge() {
    document.querySelectorAll('.cart-count').forEach(el =>
        el.textContent = loadCart().reduce((s, i) => s + i.qty, 0));
}
document.addEventListener('DOMContentLoaded', () => {
    updateCartBadge();

    document.querySelectorAll('[data-product]').forEach(btn => {
        btn.addEventListener('click', () => {
            addToCart(JSON.parse(btn.dataset.product));
            btn.textContent = 'Добавлено';
            setTimeout(() => btn.textContent = btn.dataset.label || 'В корзину', 900);
        });
    });
});
</script>
</body>
</html>

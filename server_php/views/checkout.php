<?php $e = fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES); ?>
<h1 class="page-title">Оформление заказа</h1>

<?php foreach (($errors ?? []) as $err): ?>
    <div class="flash err"><?= is_array($err) ? $e($err['msg'] ?? '') : $e($err) ?></div>
<?php endforeach; ?>

<form method="post" action="/checkout" id="checkout-form" class="card checkout-card">
    <div class="body">
        <label>Ваше имя
            <input type="text" name="name" required minlength="2" maxlength="128"
                   value="<?= $e($old['name'] ?? ($user['username'] ?? '')) ?>">
        </label>

        <label>Телефон
            <input type="tel" name="phone" required pattern="[\d\s()+\-]{7,32}"
                   placeholder="+7 900 000-00-00" value="<?= $e($old['phone'] ?? '') ?>">
        </label>

        <label>Адрес доставки
            <input type="text" name="address" required maxlength="255"
                   placeholder="Город, улица, дом, квартира" value="<?= $e($old['address'] ?? '') ?>">
        </label>

        <label>Комментарий к заказу (необязательно)
            <textarea name="comment" rows="3" maxlength="500"><?= $e($old['comment'] ?? '') ?></textarea>
        </label>

        <div id="checkout-items" class="cart-summary"></div>
        <div class="cart-total" id="checkout-total"></div>
        <div id="checkout-items-fields"></div>

        <p class="actions-row">
            <button class="btn" type="submit" id="checkout-submit">Подтвердить заказ</button>
            <a class="btn secondary" href="/cart">Вернуться в корзину</a>
        </p>
    </div>
</form>

<script>
(function () {
    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    const cart = loadCart();
    const box = document.getElementById('checkout-items');
    const total = document.getElementById('checkout-total');
    const fields = document.getElementById('checkout-items-fields');
    const submit = document.getElementById('checkout-submit');

    if (!cart.length) {
        box.innerHTML = '<div class="empty">Корзина пуста. <a href="/">Перейти в каталог</a></div>';
        total.textContent = '';
        submit.disabled = true;
        return;
    }

    box.innerHTML = cart.map(i => `
        <div class="cart-item">
            ${i.image ? `<img src="${esc(imgUrl(i.image))}" alt="">` : ''}
            <div class="grow">
                <b>${esc(i.name)}</b><br>
                <span style="color:var(--gray)">${fmtPrice(i.price)} × ${i.qty}</span>
            </div>
            <span>${fmtPrice(i.price * i.qty)}</span>
        </div>`).join('');

    const sum = cart.reduce((s, i) => s + i.price * i.qty, 0);
    total.textContent = 'Итого к оплате: ' + fmtPrice(sum);

    // Скрытые поля состава заказа — сервер проверит цены и остатки сам.
    fields.innerHTML = cart.map(i =>
        `<input type="hidden" name="items[${Number(i.id)}][id]" value="${Number(i.id)}">` +
        `<input type="hidden" name="items[${Number(i.id)}][qty]" value="${Number(i.qty)}">`
    ).join('');
})();
</script>

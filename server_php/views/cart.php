<h1 class="page-title">Корзина</h1>
<div id="cart-items"></div>
<div class="empty" id="cart-empty" style="display:none">Корзина пуста.</div>
<div class="cart-total" id="cart-total"></div>
<div style="margin-top:16px">
    <button class="btn" id="cart-checkout" style="display:none">Оформить заказ</button>
</div>

<script>
(function () {
    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function renderCart() {
        const cart = loadCart();
        const box = document.getElementById('cart-items');
        const empty = document.getElementById('cart-empty');
        const total = document.getElementById('cart-total');
        const btn = document.getElementById('cart-checkout');

        if (!cart.length) {
            box.innerHTML = '';
            empty.style.display = 'block';
            total.textContent = '';
            btn.style.display = 'none';
            return;
        }
        empty.style.display = 'none';
        btn.style.display = 'inline-block';

        box.innerHTML = cart.map((i, idx) => `
            <div class="cart-item">
                ${i.image ? `<img src="${esc(imgUrl(i.image))}" alt="">` : ''}
                <div class="grow">
                    <b>${esc(i.name)}</b><br>
                    <span style="color:var(--gray)">${fmtPrice(i.price)} × ${i.qty}</span>
                </div>
                <button class="btn small secondary" data-idx="${idx}" data-action="dec">-</button>
                <button class="btn small secondary" data-idx="${idx}" data-action="inc">+</button>
                <button class="btn small danger" data-idx="${idx}" data-action="remove">Удалить</button>
            </div>`).join('');

        const sum = cart.reduce((s, i) => s + i.price * i.qty, 0);
        total.textContent = 'Итого: ' + fmtPrice(sum);
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('#cart-items button[data-action]');
        if (!btn) return;
        const cart = loadCart();
        const idx = Number(btn.dataset.idx);
        const item = cart[idx];
        if (!item) return;

        switch (btn.dataset.action) {
            case 'inc':
                item.qty += 1;
                saveCart(cart);
                break;
            case 'dec':
                item.qty -= 1;
                if (item.qty <= 0) cart.splice(idx, 1);
                saveCart(cart);
                break;
            case 'remove':
                cart.splice(idx, 1);
                saveCart(cart);
                break;
        }
        updateCartBadge();
        renderCart();
    });

    document.getElementById('cart-checkout').addEventListener('click', () => {
        alert('Спасибо за заказ! Мы свяжемся с вами для подтверждения.');
        saveCart([]);
        renderCart();
    });

    window.addEventListener('storage', renderCart);
    renderCart();
})();
</script>

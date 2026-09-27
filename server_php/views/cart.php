<h1 class="page-title">Корзина</h1>
<div id="items"></div>
<div class="empty" id="empty" style="display:none">Корзина пуста.</div>
<div class="cart-total" id="total"></div>
<div style="margin-top:16px">
    <button class="btn" id="checkout" style="display:none">Оформить заказ</button>
</div>

<script>
function renderCart() {
    const cart = loadCart();
    const box = document.getElementById('items');
    const empty = document.getElementById('empty');
    const total = document.getElementById('total');
    const btn = document.getElementById('checkout');

    if (!cart.length) {
        box.innerHTML = ''; empty.style.display = 'block';
        total.textContent = ''; btn.style.display = 'none';
        return;
    }
    empty.style.display = 'none';
    btn.style.display = 'inline-block';
    box.innerHTML = cart.map(i => `
        <div class="cart-item">
            ${i.image ? `<img src="${imgUrl(i.image)}" alt="">` : ''}
            <div class="grow">
                <b>${i.name}</b><br>
                <span style="color:var(--gray)">${fmtPrice(i.price)} × ${i.qty}</span>
            </div>
            <button class="btn small secondary" onclick="changeQty(${JSON.stringify(i.id)}, -1); renderCart()">−</button>
            <button class="btn small secondary" onclick="changeQty(${JSON.stringify(i.id)}, 1); renderCart()">+</button>
            <button class="btn small danger" onclick="removeFromCart(${JSON.stringify(i.id)}); renderCart()">✕</button>
        </div>`).join('');
    const sum = cart.reduce((s, i) => s + i.price * i.qty, 0);
    total.textContent = 'Итого: ' + fmtPrice(sum);
}

document.getElementById('checkout').addEventListener('click', () => {
    alert('Спасибо за заказ! Мы свяжемся с вами для подтверждения.');
    saveCart([]);
    renderCart();
});

renderCart();
</script>

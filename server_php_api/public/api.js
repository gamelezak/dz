const API_BASE = (location.protocol === 'file:') ? 'http://localhost:8000' : '';

async function api(path, options = {}) {
    const resp = await fetch(API_BASE + path, options);
    if (!resp.ok) {
        let detail = 'Ошибка сервера';
        try { detail = (await resp.json()).detail || detail; } catch (e) {}
        throw new Error(detail);
    }
    return resp.json();
}

const getProducts = (search = '') =>
    api(`/api/products?search=${encodeURIComponent(search)}`);

const getProduct = (id) => api(`/api/products/${id}`);

function imgUrl(name) {
    if (!name) return '';
    if (name.startsWith('http') || name.startsWith('/static')) return API_BASE + name;
    return API_BASE + `/static/images/products/${encodeURIComponent(name)}`;
}

function fmtPrice(v) {
    return Number(v).toLocaleString('ru-RU') + ' ₽';
}

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[c]));
}


const CART_KEY = 'sportshop_cart';

function loadCart() {
    try { return JSON.parse(localStorage.getItem(CART_KEY)) || []; }
    catch { return []; }
}

function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    updateCartBadge();
}

function addToCart(product) {
    const cart = loadCart();
    const item = cart.find(i => i.id === product.id);
    if (item) item.qty += 1;
    else cart.push({ id: product.id, name: product.name, price: product.price,
                     image: product.image, qty: 1 });
    saveCart(cart);
}

function removeFromCart(id) {
    saveCart(loadCart().filter(i => i.id !== id));
}

function changeQty(id, delta) {
    const cart = loadCart();
    const item = cart.find(i => i.id === id);
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) removeFromCart(id);
    else saveCart(cart);
}

function cartCount() {
    return loadCart().reduce((s, i) => s + i.qty, 0);
}

function updateCartBadge() {
    document.querySelectorAll('.cart-count').forEach(el => {
        el.textContent = cartCount();
    });
}

document.addEventListener('DOMContentLoaded', updateCartBadge);

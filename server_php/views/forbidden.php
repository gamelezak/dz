<div class="empty">
    <h1 class="page-title">Нет доступа</h1>
    <p>Эта страница требует роль не ниже «<?= htmlspecialchars((string)($role ?? ''), ENT_QUOTES) ?>».</p>
    <p class="actions-row" style="justify-content:center">
        <a class="btn small" href="/">В каталог</a>
        <a class="btn small secondary" href="/account">Личный кабинет</a>
    </p>
</div>

<?php $e = fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES); ?>
<div class="auth-wrap">
    <h1 class="page-title">Вход в аккаунт</h1>
    <?php if (!empty($flash['msg'])): ?>
        <div class="flash <?= $e($flash['type'] ?? 'ok') ?>"><?= $e($flash['msg']) ?></div>
    <?php endif; ?>
    <form method="post" action="/login" class="admin-form">
        <label>Логин или email
            <input type="text" name="username" required autofocus>
        </label>
        <label>Пароль
            <input type="password" name="password" required>
        </label>
        <button class="btn" type="submit">Войти</button>
    </form>
    <p class="auth-note">
        Нет аккаунта? <a href="/register">Зарегистрируйтесь</a>.<br>
        Управление товарами доступно сотрудникам с ролью manager или admin.
    </p>
</div>

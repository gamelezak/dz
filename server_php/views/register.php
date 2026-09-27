<?php $e = fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES); ?>
<div class="auth-wrap">
    <h1 class="page-title">Регистрация</h1>
    <?php if (!empty($errors)): ?>
        <?php foreach ((array)$errors as $err): ?>
            <div class="flash err"><?= is_array($err) ? $e($err['msg'] ?? '') : $e($err) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    <form method="post" action="/register" class="admin-form">
        <label>Логин
            <input type="text" name="username" required value="<?= $e($old['username'] ?? '') ?>"
                   pattern="[A-Za-z0-9_.\-]{3,64}">
        </label>
        <label>Email
            <input type="email" name="email" required value="<?= $e($old['email'] ?? '') ?>">
        </label>
        <label>Пароль (минимум 6 символов)
            <input type="password" name="password" required minlength="6">
        </label>
        <button class="btn" type="submit">Создать аккаунт</button>
    </form>
    <p class="auth-note">
        Новый аккаунт создается с ролью «user».<br>
        Уже есть аккаунт? <a href="/login">Войти</a>.
    </p>
</div>

<?php $e = fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES); ?>
<div style="max-width:380px;margin:60px auto">
    <h1 class="page-title">Вход в админку</h1>
    <?php if (!empty($error['msg'])): ?>
        <div class="flash err"><?= $e($error['msg']) ?></div>
    <?php endif; ?>
    <form method="post" action="/admin/login" class="admin-form">
        <label>Логин
            <input type="text" name="username" required autofocus>
        </label>
        <label>Пароль
            <input type="password" name="password" required>
        </label>
        <button class="btn" type="submit">Войти</button>
    </form>
    <p style="color:var(--gray);font-size:13px">Демо-доступ: admin / admin123</p>
</div>

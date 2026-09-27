<?php $e = fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES); ?>
<h1 class="page-title">Личный кабинет</h1>
<?php if (!empty($flash['msg'])): ?>
    <div class="flash <?= $e($flash['type'] ?? 'ok') ?>"><?= $e($flash['msg']) ?></div>
<?php endif; ?>
<div class="card" style="max-width:480px">
    <div class="body">
        <p><b><?= $e($user['username'] ?? '') ?></b> (<?= $e($user['role'] ?? '') ?>)</p>
        <p><?= $e($user['email'] ?? '') ?></p>
        <p style="margin-top:12px">
            <?php if (($user['role'] ?? '') === 'manager' || ($user['role'] ?? '') === 'admin'): ?>
                <a class="btn small" href="/admin">Перейти в админку</a>
            <?php endif; ?>
            <a class="btn small secondary" href="/logout">Выйти</a>
        </p>
    </div>
</div>

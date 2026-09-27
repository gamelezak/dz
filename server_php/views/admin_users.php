<?php
$e = fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES);
?>
<div class="admin-head">
    <h1 class="page-title">Пользователи (<?= count($users ?? []) ?>)</h1>
    <div>
        <a class="btn small secondary" href="/admin">Товары</a>
        <a class="btn small secondary" href="/admin/orders">Заказы</a>
        <span class="admin-user">Роль: <span class="badge role-<?= $e($adminUser['role'] ?? '') ?>"><?= $e($adminUser['role'] ?? '') ?></span></span>
        <a class="btn small secondary" href="/logout">Выйти</a>
    </div>
</div>

<?php if (!empty($flash['msg'])): ?>
    <div class="flash <?= $e($flash['type'] ?? 'ok') ?>"><?= $e($flash['msg']) ?></div>
<?php endif; ?>

<div class="table-wrap">
<table class="admin">
<tr><th>ID</th><th>Логин</th><th>Email</th><th>Роль</th><th></th></tr>
<?php foreach (($users ?? []) as $u): ?>
<tr>
    <td><?= (int)$u['id'] ?></td>
    <td><?= $e($u['username']) ?></td>
    <td><?= $e($u['email']) ?></td>
    <td><span class="badge role-<?= $e($u['role']) ?>"><?= $e($u['role']) ?></span></td>
    <td class="nowrap">
        <form method="post" action="/admin/users/<?= (int)$u['id'] ?>/role" class="inline-form">
            <select name="role">
                <?php foreach (($roles ?? ['user','manager','admin']) as $r): ?>
                    <option value="<?= $e($r) ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= $e($r) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn small" type="submit">Назначить</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$users): ?>
<tr><td colspan="5">Пользователей нет.</td></tr>
<?php endif; ?>
</table>
</div>

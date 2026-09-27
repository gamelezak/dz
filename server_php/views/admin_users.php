<?php
$e = fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES);
?>
<style>
.flash{padding:10px 14px;border-radius:8px;margin:10px 0}
table.admin{width:100%;border-collapse:collapse;background:#fff}
table.admin th,table.admin td{padding:10px 12px;border-bottom:1px solid #e5e7eb;text-align:left}
.admin-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
</style>

<div class="admin-head">
    <h1 class="page-title">Пользователи (<?= count($users ?? []) ?>)</h1>
    <div>
        <a class="btn small secondary" href="/admin">Товары</a>
        <span style="color:var(--gray)">Роль: <?= $e($adminUser['role'] ?? '') ?></span>
        <a class="btn small secondary" href="/logout">Выйти</a>
    </div>
</div>

<?php if (!empty($flash['msg'])): ?>
    <div class="flash <?= $e($flash['type'] ?? 'ok') ?>"><?= $e($flash['msg']) ?></div>
<?php endif; ?>

<table class="admin">
<tr><th>ID</th><th>Логин</th><th>Email</th><th>Роль</th><th></th></tr>
<?php foreach (($users ?? []) as $u): ?>
<tr>
    <td><?= (int)$u['id'] ?></td>
    <td><?= $e($u['username']) ?></td>
    <td><?= $e($u['email']) ?></td>
    <td><?= $e($u['role']) ?></td>
    <td style="white-space:nowrap">
        <form method="post" action="/admin/users/<?= (int)$u['id'] ?>/role" style="display:inline-flex;gap:6px">
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

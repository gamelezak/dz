<?php
$e = fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES);
?>
<div class="admin-head">
    <h1 class="page-title">Заказы (<?= count($orders) ?>)</h1>
    <div>
        <a class="btn small secondary" href="/admin">Товары</a>
        <?php if (($adminUser['role'] ?? '') === 'admin'): ?>
            <a class="btn small secondary" href="/admin/users">Пользователи</a>
        <?php endif; ?>
        <span class="admin-user"><?= $e($adminUser['username'] ?? '') ?></span>
        <span class="badge role-<?= $e($adminUser['role'] ?? '') ?>"><?= $e($adminUser['role'] ?? '') ?></span>
        <a class="btn small secondary" href="/logout">Выйти</a>
    </div>
</div>

<?php if (!empty($flash['msg'])): ?>
    <div class="flash <?= $e($flash['type'] ?? 'ok') ?>"><?= $e($flash['msg']) ?></div>
<?php endif; ?>

<?php if (!$orders): ?>
    <div class="empty">Заказов пока нет.</div>
<?php else: ?>
<div class="table-wrap">
<table class="admin">
    <tr><th>№</th><th>Дата</th><th>Покупатель</th><th>Телефон</th><th>Адрес</th><th>Позиций</th><th>Сумма</th><th>Статус</th></tr>
    <?php foreach ($orders as $o): ?>
    <tr>
        <td><?= (int)$o['id'] ?></td>
        <td class="nowrap"><?= $e(substr((string)$o['created_at'], 0, 16)) ?></td>
        <td><?= $e($o['username']) ?></td>
        <td class="nowrap"><?= $e($o['phone']) ?></td>
        <td><?= $e($o['address']) ?></td>
        <td><?= (int)($o['items_count'] ?? 0) ?></td>
        <td class="nowrap"><?= number_format((float)$o['total'], 2, ',', ' ') ?> ₽</td>
        <td>
            <form method="post" action="/admin/orders/<?= (int)$o['id'] ?>/status" class="inline-form">
                <select name="status">
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= $e($s) ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= $e($s) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn small" type="submit">OK</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<?php endif; ?>

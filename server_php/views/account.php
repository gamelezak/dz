<?php $e = fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES); ?>
<h1 class="page-title">Личный кабинет</h1>
<?php if (!empty($flash['msg'])): ?>
    <div class="flash <?= $e($flash['type'] ?? 'ok') ?>"><?= $e($flash['msg']) ?></div>
<?php endif; ?>
<div class="card account-card">
    <div class="body">
        <p><b><?= $e($user['username'] ?? '') ?></b> <span class="badge role-<?= $e($user['role'] ?? '') ?>"><?= $e($user['role'] ?? '') ?></span></p>
        <p><?= $e($user['email'] ?? '') ?></p>
        <p class="actions-row">
            <?php if (in_array($user['role'] ?? '', ['manager', 'admin'], true)): ?>
                <a class="btn small" href="/admin">Перейти в админку</a>
            <?php endif; ?>
            <a class="btn small secondary" href="/logout">Выйти</a>
        </p>
    </div>
</div>

<?php if ($detail !== null): ?>
<h2 class="page-title">Заказ №<?= (int)$detail['id'] ?></h2>
<div class="card order-card">
    <div class="body">
        <p><b>Статус:</b> <span class="badge status-<?= $e($detail['status']) ?>"><?= $e($detail['status']) ?></span> · <b>Итого:</b> <?= number_format((float)$detail['total'], 2, ',', ' ') ?> ₽</p>
        <p><b>Получатель:</b> <?= $e($detail['username']) ?>, <?= $e($detail['phone']) ?></p>
        <p><b>Адрес:</b> <?= $e($detail['address']) ?></p>
        <?php if (($detail['comment'] ?? '') !== ''): ?>
            <p><b>Комментарий:</b> <?= $e($detail['comment']) ?></p>
        <?php endif; ?>
        <div class="table-wrap">
        <table class="admin">
            <tr><th>Товар</th><th>Цена</th><th>Кол-во</th><th>Сумма</th></tr>
            <?php foreach ($detail['items'] as $it): ?>
            <tr>
                <td><?= $e($it['name']) ?></td>
                <td class="nowrap"><?= number_format($it['price'], 2, ',', ' ') ?> ₽</td>
                <td><?= (int)$it['qty'] ?></td>
                <td class="nowrap"><?= number_format($it['price'] * $it['qty'], 2, ',', ' ') ?> ₽</td>
            </tr>
            <?php endforeach; ?>
        </table>
        </div>
        <p class="actions-row">
            <a class="btn small secondary" href="/account">К списку заказов</a>
            <?php if ($detail['status'] === 'new' && (int)$detail['user_id'] === (int)($user['id'] ?? 0)): ?>
            <form method="post" action="/order/<?= (int)$detail['id'] ?>/cancel" class="inline-form"
                  onsubmit="return confirm('Отменить заказ №<?= (int)$detail['id'] ?>?')">
                <button class="btn small danger" type="submit">Отменить заказ</button>
            </form>
            <?php endif; ?>
        </p>
    </div>
</div>
<?php else: ?>

<h2 class="page-title">Мои заказы (<?= count($orders) ?>)</h2>
<?php if (!$orders): ?>
    <div class="empty">Заказов пока нет. <a href="/">Перейти в каталог</a></div>
<?php else: ?>
<div class="table-wrap">
<table class="admin">
    <tr><th>№</th><th>Дата</th><th>Позиций</th><th>Сумма</th><th>Статус</th><th></th></tr>
    <?php foreach ($orders as $o): ?>
    <tr>
        <td><?= (int)$o['id'] ?></td>
        <td class="nowrap"><?= $e(substr((string)$o['created_at'], 0, 16)) ?></td>
        <td><?= (int)($o['items_count'] ?? 0) ?></td>
        <td class="nowrap"><?= number_format((float)$o['total'], 2, ',', ' ') ?> ₽</td>
        <td><span class="badge status-<?= $e($o['status']) ?>"><?= $e($o['status']) ?></span></td>
        <td class="nowrap">
            <a class="btn small secondary" href="/account?order=<?= (int)$o['id'] ?>">Подробнее</a>
            <?php if ($o['status'] === 'new'): ?>
            <form method="post" action="/order/<?= (int)$o['id'] ?>/cancel" class="inline-form"
                  onsubmit="return confirm('Отменить заказ №<?= (int)$o['id'] ?>?')">
                <button class="btn small danger" type="submit">Отменить</button>
            </form>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</div>
<?php endif; ?>
<?php endif; ?>

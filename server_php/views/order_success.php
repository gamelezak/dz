<?php $e = fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES); ?>
<h1 class="page-title">Заказ №<?= (int)$order['id'] ?> оформлен</h1>

<?php if (!empty($flash['msg'])): ?>
    <div class="flash <?= $e($flash['type'] ?? 'ok') ?>"><?= $e($flash['msg']) ?></div>
<?php endif; ?>

<div class="card order-card">
    <div class="body">
        <p><b>Статус:</b> <span class="badge status-<?= $e($order['status']) ?>"><?= $e($order['status']) ?></span></p>
        <p><b>Получатель:</b> <?= $e($order['username']) ?></p>
        <p><b>Телефон:</b> <?= $e($order['phone']) ?></p>
        <p><b>Адрес:</b> <?= $e($order['address']) ?></p>
        <?php if (($order['comment'] ?? '') !== ''): ?>
            <p><b>Комментарий:</b> <?= $e($order['comment']) ?></p>
        <?php endif; ?>

        <div class="table-wrap">
        <table class="admin">
            <tr><th>Товар</th><th>Цена</th><th>Кол-во</th><th>Сумма</th></tr>
            <?php foreach ($order['items'] as $it): ?>
            <tr>
                <td><?= $e($it['name']) ?></td>
                <td class="nowrap"><?= number_format($it['price'], 2, ',', ' ') ?> ₽</td>
                <td><?= (int)$it['qty'] ?></td>
                <td class="nowrap"><?= number_format($it['price'] * $it['qty'], 2, ',', ' ') ?> ₽</td>
            </tr>
            <?php endforeach; ?>
        </table>
        </div>

        <p class="cart-total">Итого: <?= number_format((float)$order['total'], 2, ',', ' ') ?> ₽</p>

        <p class="actions-row">
            <a class="btn" href="/account">Мои заказы</a>
            <a class="btn secondary" href="/">В каталог</a>
            <?php if ($order['status'] === 'new'): ?>
            <form method="post" action="/order/<?= (int)$order['id'] ?>/cancel" class="inline-form"
                  onsubmit="return confirm('Отменить заказ №<?= (int)$order['id'] ?>?')">
                <button class="btn small danger" type="submit">Отменить заказ</button>
            </form>
            <?php endif; ?>
        </p>
    </div>
</div>

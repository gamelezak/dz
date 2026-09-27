<?php
$e = fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES);

?>
<style>
.flash{padding:10px 14px;border-radius:8px;margin:10px 0}
.flash.ok{background:
.flash.err{background:
table.admin{width:100%;border-collapse:collapse;background:
table.admin th,table.admin td{padding:10px 12px;border-bottom:1px solid 
table.admin img{width:56px;height:56px;object-fit:cover;border-radius:8px}
.admin-head{display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px}
</style>

<div class="admin-head">
    <h1 class="page-title">Админка — товары (<?= count($products) ?>)</h1>
    <div>
        <a class="btn" href="/admin/products/new">+ Добавить товар</a>
        <?php if (($adminUser['role'] ?? '') === 'admin'): ?>
            <a class="btn small secondary" href="/admin/users">Пользователи</a>
        <?php endif; ?>
        <span style="color:var(--gray)"><?= $e($adminUser['username'] ?? '') ?> (<?= $e($adminUser['role'] ?? '') ?>)</span>
        <a class="btn small secondary" href="/logout">Выйти</a>
    </div>
</div>

<?php if (!empty($flash['msg'])): ?>
    <div class="flash <?= $e($flash['type'] ?? 'ok') ?>"><?= $e($flash['msg']) ?></div>
<?php endif; ?>

<table class="admin">
<tr><th>Фото</th><th>Название</th><th>Цена</th><th>Описание</th><th>Галерея</th><th></th></tr>
<?php foreach ($products as $p): ?>
<tr>
    <td><?= $p['image'] ? '<img src="/images/products/' . $e($p['image']) . '" alt="">' : '—' ?></td>
    <td><?= $e($p['name']) ?></td>
    <td><?= number_format((float)$p['price'], 0, '', ' ') ?> ₽</td>
    <td><?= $e(mb_substr((string)($p['description'] ?? ''), 0, 60)) ?></td>
    <td><?= count($p['extra_images'] ?? []) ?> доп.</td>
    <td style="white-space:nowrap">
        <a class="btn small secondary" href="/admin/products/<?= (int)$p['id'] ?>/edit">Ред.</a>
        <form method="post" action="/admin/products/<?= (int)$p['id'] ?>/delete" style="display:inline"
              onsubmit="return confirm('Удалить товар «<?= $e($p['name']) ?>» вместе с картинками?')">
            <button class="btn small danger" type="submit">Удалить</button>
        </form>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$products): ?>
<tr><td colspan="6">Товаров нет — добавьте первый!</td></tr>
<?php endif; ?>
</table>

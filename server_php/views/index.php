<?php

$e = fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES);
?>
<h1 class="page-title">Каталог спортивных товаров</h1>

<form method="get" action="/" class="search-box" style="margin-bottom:20px">
    <input type="text" name="search" placeholder="Поиск по товарам…" value="<?= $e($search ?? '') ?>">
    <button class="btn small" type="submit">Найти</button>
</form>

<div class="grid">
    <?php foreach (($products ?? []) as $p): ?>
    <div class="card">
        <a href="/product/<?= (int)$p['id'] ?>">
            <div class="thumb">
                <?php if ($p['image']): ?>
                    <img src="/images/products/<?= $e($p['image']) ?>" alt="<?= $e($p['name']) ?>">
                <?php else: ?>
                    <span style="color:#9ca3af">Нет фото</span>
                <?php endif; ?>
            </div>
        </a>
        <div class="body">
            <a href="/product/<?= (int)$p['id'] ?>" class="name"><?= $e($p['name']) ?></a>
            <div class="desc"><?= $e(mb_substr((string)($p['description'] ?? ''), 0, 90)) ?></div>
            <div class="price"><?= number_format((float)$p['price'], 0, '', ' ') ?> ₽</div>
            <button class="btn small" data-label="В корзину"
                    data-product='<?= json_encode(["id"=>$p["id"],"name"=>$p["name"],"price"=>$p["price"],"image"=>$p["image"]], JSON_UNESCAPED_UNICODE) ?>'>В корзину</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php if (!count($products ?? [])): ?>
<div class="empty">Товары не найдены. Добавьте их через <a href="/admin">админку</a>.</div>
<?php endif; ?>

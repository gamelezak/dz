<?php

$e = fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES);
$p = $p ?? [];
$images = array_values(array_filter(array_merge([$p['image'] ?? null], $p['extra_images'] ?? [])));
?>
<a class="back-link" href="/">← Вернуться в каталог</a>
<div class="product-page">
    <div class="gallery">
        <div class="main-img">
            <?php if ($images): ?>
                <img id="main-img" src="/images/products/<?= $e($images[0]) ?>" alt="<?= $e($p['name']) ?>">
            <?php else: ?>
                <span style="color:#9ca3af">Нет фото</span>
            <?php endif; ?>
        </div>
        <?php if (count($images) > 1): ?>
        <div class="thumbs-row">
            <?php foreach ($images as $i => $n): ?>
                <img src="/images/products/<?= $e($n) ?>" class="<?= $i === 0 ? 'active' : '' ?>"
                     onclick="switchImg(this, '<?= $e($n) ?>')"/>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <div class="product-info">
        <h1><?= $e($p['name']) ?></h1>
        <div class="price"><?= number_format((float)$p['price'], 0, '', ' ') ?> ₽</div>
        <div class="description"><?= $e($p['description'] ?: 'Описание отсутствует.') ?></div>
        <button class="btn" data-label="🛒 Добавить в корзину"
                data-product='<?= json_encode(["id"=>$p["id"],"name"=>$p["name"],"price"=>$p["price"],"image"=>$p["image"]], JSON_UNESCAPED_UNICODE) ?>'>🛒 Добавить в корзину</button>
    </div>
</div>
<script>
function switchImg(thumb, name) {
    document.getElementById('main-img').src = '/images/products/' + encodeURIComponent(name);
    document.querySelectorAll('.thumbs-row img').forEach(i => i.classList.remove('active'));
    thumb.classList.add('active');
}
</script>

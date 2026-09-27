<?php
$e = fn ($v) => htmlspecialchars((string)$v, ENT_QUOTES);

$isEdit = !empty($product);
$val = function ($key) use ($product, $old) {
    if (!empty($old[$key])) return $old[$key];
    return $product[$key] ?? '';
};
?>
<a class="back-link" href="/admin">Назад в админку</a>
<h1 class="page-title"><?= $isEdit ? 'Редактирование товара' : 'Новый товар' ?></h1>

<?php if (!empty($errors)): ?>
    <?php foreach ((array)$errors as $err): ?><div class="flash err"><?= $e(is_array($err) ? ($err['msg'] ?? '') : $err) ?></div><?php endforeach; ?>
<?php endif; ?>

<form class="admin-form" method="post" enctype="multipart/form-data"
      action="<?= $isEdit ? '/admin/products/' . (int)$product['id'] : '/admin/products' ?>">

    <label>Название товара *
        <input type="text" name="name" maxlength="255" required value="<?= $e($val('name')) ?>">
    </label>

    <label>Цена, ₽ *
        <input type="number" name="price" min="0" step="0.01" required value="<?= $e($val('price')) ?>">
    </label>

    <label>Описание
        <textarea name="description"><?= $e($val('description')) ?></textarea>
    </label>

    <label>Картинка товара *<?= $isEdit ? '<span class="hint">можно заменить, оставив текущую</span>' : '' ?>
        <input type="file" name="image" accept="image/*" <?= $isEdit ? '' : 'required' ?>>
    </label>

    <?php if ($isEdit && $product['image']): ?>
    <div>
        <span class="img-caption">Текущая картинка:</span>
        <div class="cur-imgs"><img src="/images/products/<?= $e($product['image']) ?>" alt=""></div>
    </div>
    <?php endif; ?>

    <label>Дополнительные картинки для страницы товара <span class="hint">(необязательно, можно несколько)</span>
        <input type="file" name="extra_images[]" accept="image/*" multiple>
    </label>

    <?php if ($isEdit && !empty($product['extra_images'])): ?>
    <div>
        <span class="img-caption">Текущая галерея:</span>
        <div class="cur-imgs">
            <?php foreach ($product['extra_images'] as $n): ?>
                <img src="/images/products/<?= $e($n) ?>" alt="">
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="form-actions">
        <button class="btn" type="submit"><?= $isEdit ? 'Сохранить' : 'Добавить товар' ?></button>
        <a class="btn secondary" href="/admin">Отмена</a>
    </div>
</form>

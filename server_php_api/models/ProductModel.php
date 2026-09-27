<?php

class ProductModel extends BaseModel
{

    private static function serialize(array $row, array $extraImages = []): array
    {
        return [
            'id'          => (int)$row['id'],
            'name'        => $row['name'],
            'price'       => (float)$row['price'],
            'description' => $row['description'] ?? '',
            'image'       => $row['image'],
            'extra_images' => $extraImages,
        ];
    }

    public function all(?string $search = null): array
    {
        $pdo = $this->pdo();
        if ($search !== null && $search !== '') {
            $stmt = $pdo->prepare(
                'SELECT * FROM products
                 WHERE name LIKE :q OR description LIKE :q
                 ORDER BY id DESC'
            );
            $stmt->execute([':q' => '%' . $search . '%']);
        } else {
            $stmt = $pdo->query('SELECT * FROM products ORDER BY id DESC');
        }
        $rows = $stmt->fetchAll();

        $extras = $this->allExtraImages(array_column($rows, 'id'));

        return array_map(
            fn (array $r) => self::serialize($r, $extras[$r['id']] ?? []),
            $rows
        );
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return self::serialize($row, $this->extraImages($id));
    }

    public function create(string $name, float $price, string $description, ?string $image, array $extraImages = []): int
    {
        $pdo  = $this->pdo();
        $stmt = $pdo->prepare(
            'INSERT INTO products (name, price, description, image) VALUES (:n, :p, :d, :i)'
        );
        $stmt->execute([
            ':n' => $name,
            ':p' => $price,
            ':d' => $description !== '' ? $description : null,
            ':i' => $image,
        ]);
        $id = (int)$pdo->lastInsertId();
        $this->setExtraImages($id, $extraImages);
        return $id;
    }

    public function update(int $id, array $fields, array $extraImagesAppend = []): bool
    {
        $allowed = ['name', 'price', 'description', 'image'];
        $sets  = [];
        $binds = [':id' => $id];
        foreach ($fields as $k => $v) {
            if (!in_array($k, $allowed, true)) continue;
            $sets[]         = "$k = :$k";
            $binds[":$k"]   = $v;
        }
        if ($sets) {
            $stmt = $this->pdo()->prepare('UPDATE products SET ' . implode(', ', $sets) . ' WHERE id = :id');
            $stmt->execute($binds);
        }
        if ($extraImagesAppend) {
            $current = $this->extraImages($id);
            $this->setExtraImages($id, array_merge($current, $extraImagesAppend));
        }
        return !empty($sets) || !empty($extraImagesAppend);
    }

    public function delete(int $id): array
    {
        $pdo  = $this->pdo();
        $stmt = $pdo->prepare('SELECT image FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return [];

        $files   = $this->extraImages($id);
        if ($row['image']) $files[] = $row['image'];

        $del = $pdo->prepare('DELETE FROM products WHERE id = :id'); 

        $del->execute([':id' => $id]);
        return $files;
    }

    public function extraImages(int $productId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT filename FROM product_images WHERE product_id = :id ORDER BY sort_order, id'
        );
        $stmt->execute([':id' => $productId]);
        return array_column($stmt->fetchAll(), 'filename');
    }

    public function setExtraImages(int $productId, array $filenames): void
    {
        $pdo = $this->pdo();
        $pdo->prepare('DELETE FROM product_images WHERE product_id = :id')->execute([':id' => $productId]);
        if (!$filenames) return;
        $stmt = $pdo->prepare(
            'INSERT INTO product_images (product_id, filename, sort_order) VALUES (:pid, :f, :s)'
        );
        foreach (array_values($filenames) as $i => $f) {
            $stmt->execute([':pid' => $productId, ':f' => $f, ':s' => $i]);
        }
    }

    private function allExtraImages(array $ids): array
    {
        if (!$ids) return [];
        $in   = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo()->prepare(
            "SELECT product_id, filename FROM product_images
             WHERE product_id IN ($in) ORDER BY sort_order, id"
        );
        $stmt->execute(array_map('intval', $ids));
        $out = [];
        foreach ($stmt->fetchAll() as $r) {
            $out[(int)$r['product_id']][] = $r['filename'];
        }
        return $out;
    }
}

<?php

/**
 * Заказы: оформление из корзины, история пользователя, список для менеджера/админа.
 */
class OrderModel extends BaseModel
{
    public const STATUS_NEW       = 'new';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_DONE      = 'done';
    public const STATUS_CANCELED  = 'canceled';

    public const STATUSES = [self::STATUS_NEW, self::STATUS_CONFIRMED, self::STATUS_DONE, self::STATUS_CANCELED];

    private static function serialize(array $row): array
    {
        return [
            'id'          => (int)$row['id'],
            'user_id'     => (int)$row['user_id'],
            'username'    => $row['username'] ?? '',
            'phone'       => $row['phone'] ?? '',
            'address'     => $row['address'] ?? '',
            'comment'     => $row['comment'] ?? '',
            'total'       => (float)$row['total'],
            'status'      => $row['status'] ?? self::STATUS_NEW,
            'created_at'  => $row['created_at'] ?? '',
            'items_count' => isset($row['items_count']) ? (int)$row['items_count'] : null,
        ];
    }

    /**
     * Создание заказа в транзакции: проверяем наличие товаров,
     * фиксируем цены на момент оформления, уменьшаем остатки.
     * Бросает RuntimeException с локализуемым сообщением при нехватке товара.
     */
    public function create(int $userId, string $username, string $phone, string $address,
                           string $comment, array $items): int
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $check  = $pdo->prepare('SELECT id, name, price, stock FROM products WHERE id = :id');
            $decSt  = $pdo->prepare(
                $this->driver() === 'sqlite'
                    ? 'UPDATE products SET stock = stock - :q, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND stock >= :q'
                    : 'UPDATE products SET stock = stock - :q WHERE id = :id AND stock >= :q'
            );
            $insOrd = $pdo->prepare(
                'INSERT INTO orders (user_id, username, phone, address, comment, total, status)
                 VALUES (:uid, :un, :ph, :ad, :cm, :total, :st)'
            );
            $insIt  = $pdo->prepare(
                'INSERT INTO order_items (order_id, product_id, name, price, qty)
                 VALUES (:oid, :pid, :n, :p, :q)'
            );

            $total = 0.0;
            $rows  = [];

            foreach ($items as $it) {
                $pid = (int)($it['id'] ?? 0);
                $qty = max(1, (int)($it['qty'] ?? 0));
                if ($pid <= 0 || $qty <= 0) {
                    throw new RuntimeException('Некорректный состав заказа.');
                }

                $check->execute([':id' => $pid]);
                $product = $check->fetch();
                if (!$product) {
                    throw new RuntimeException('Товар #' . $pid . ' не найден. Обновите корзину.');
                }

                $stock = (int)($product['stock'] ?? 0);
                if ($stock < $qty) {
                    throw new RuntimeException(
                        'Недостаточно товара «' . $product['name'] . '»: в наличии ' . $stock . ' шт.'
                    );
                }

                $price = (float)$product['price'];
                $total += $price * $qty;
                $rows[] = ['pid' => $pid, 'name' => $product['name'], 'price' => $price, 'qty' => $qty];

                $decSt->execute([':q' => $qty, ':id' => $pid]);
                if ($decSt->rowCount() !== 1) {
                    throw new RuntimeException('Товар «' . $product['name'] . '» только что закончился.');
                }
            }

            $insOrd->execute([
                ':uid'   => $userId,
                ':un'    => $username,
                ':ph'    => $phone,
                ':ad'    => $address,
                ':cm'    => $comment,
                ':total' => round($total, 2),
                ':st'    => self::STATUS_NEW,
            ]);

            $orderId = (int)$pdo->lastInsertId();
            foreach ($rows as $r) {
                $insIt->execute([
                    ':oid' => $orderId,
                    ':pid' => $r['pid'],
                    ':n'   => $r['name'],
                    ':p'   => $r['price'],
                    ':q'   => $r['qty'],
                ]);
            }

            $pdo->commit();
            return $orderId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM orders WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $order           = self::serialize($row);
        $order['items']  = $this->items($id);
        return $order;
    }

    public function items(int $orderId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT product_id, name, price, qty FROM order_items WHERE order_id = :oid ORDER BY id'
        );
        $stmt->execute([':oid' => $orderId]);
        return array_map(fn (array $r) => [
            'product_id' => (int)$r['product_id'],
            'name'       => $r['name'],
            'price'      => (float)$r['price'],
            'qty'        => (int)$r['qty'],
        ], $stmt->fetchAll());
    }

    /** История заказов пользователя. */
    public function forUser(int $userId): array
    {
        $stmt = $this->pdo()->prepare(
            'SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS items_count
             FROM orders o WHERE o.user_id = :uid ORDER BY o.id DESC'
        );
        $stmt->execute([':uid' => $userId]);
        return array_map([self::class, 'serialize'], $stmt->fetchAll());
    }

    /** Все заказы (для manager/admin). */
    public function all(): array
    {
        $rows = $this->pdo()->query(
            'SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS items_count
             FROM orders o ORDER BY o.id DESC'
        )->fetchAll();
        return array_map([self::class, 'serialize'], $rows);
    }

    /**
     * Отмена заказа покупателем: статус -> canceled, остатки товаров возвращаются.
     * Повторная отмена невозможна (защита от двойного возврата остатков).
     */
    public function cancel(int $id): bool
    {
        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $lock = $pdo->prepare('SELECT status FROM orders WHERE id = :id');
            $lock->execute([':id' => $id]);
            $status = $lock->fetchColumn();
            if ($status === false || $status !== self::STATUS_NEW) {
                $pdo->rollBack();
                return false;
            }

            $upd = $pdo->prepare("UPDATE orders SET status = 'canceled' WHERE id = :id AND status = 'new'");
            $upd->execute([':id' => $id]);
            if ($upd->rowCount() !== 1) {
                $pdo->rollBack();
                return false;
            }

            $items = $pdo->prepare('SELECT product_id, qty FROM order_items WHERE order_id = :oid');
            $items->execute([':oid' => $id]);
            $restore = $pdo->prepare(
                $this->driver() === 'sqlite'
                    ? 'UPDATE products SET stock = stock + :q, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
                    : 'UPDATE products SET stock = stock + :q WHERE id = :id'
            );
            foreach ($items->fetchAll() as $it) {
                $restore->execute([':q' => (int)$it['qty'], ':id' => (int)$it['product_id']]);
            }

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function setStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::STATUSES, true)) {
            return false;
        }
        $stmt = $this->pdo()->prepare('UPDATE orders SET status = :s WHERE id = :id');
        $stmt->execute([':s' => $status, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function countAll(): int
    {
        return (int)$this->pdo()->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }

    public function sumAll(): float
    {
        return (float)$this->pdo()->query('SELECT COALESCE(SUM(total), 0) FROM orders')->fetchColumn();
    }

    private function driver(): string
    {
        return (string)$this->pdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
}

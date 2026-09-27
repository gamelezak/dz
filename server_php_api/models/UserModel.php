<?php

class UserModel extends BaseModel
{
    public const ROLES = ['user', 'manager', 'admin'];

    private static function serialize(array $row): array
    {
        return [
            'id'       => (int)$row['id'],
            'username' => $row['username'],
            'email'    => $row['email'],
            'role'     => $row['role'] ?? 'user',
        ];
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM users WHERE username = :u');
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM users WHERE email = :e');
        $stmt->execute([':e' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::serialize($row) : null;
    }

    public function all(): array
    {
        $rows = $this->pdo()->query('SELECT * FROM users ORDER BY id')->fetchAll();
        return array_map([self::class, 'serialize'], $rows);
    }

    public function create(string $username, string $email, string $password, string $role = 'user'): int
    {
        if (!in_array($role, self::ROLES, true)) {
            $role = 'user';
        }
        $stmt = $this->pdo()->prepare(
            'INSERT INTO users (username, email, password_hash, role)
             VALUES (:u, :e, :p, :r)'
        );
        $stmt->execute([
            ':u' => $username,
            ':e' => $email,
            ':p' => password_hash($password, PASSWORD_DEFAULT),
            ':r' => $role,
        ]);
        return (int)$this->pdo()->lastInsertId();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo()->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function countUsers(): int
    {
        return (int)$this->pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function setRole(int $id, string $role): bool
    {
        if (!in_array($role, self::ROLES, true)) {
            return false;
        }
        $stmt = $this->pdo()->prepare('UPDATE users SET role = :r WHERE id = :id');
        $stmt->execute([':r' => $role, ':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function verify(?array $user, string $password): bool
    {
        return $user !== null && password_verify($password, (string)$user['password_hash']);
    }
}

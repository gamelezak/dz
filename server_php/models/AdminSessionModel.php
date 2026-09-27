<?php

class AdminSessionModel extends BaseModel
{
    public function create(string $username, int $ttlSeconds): string
    {
        $token = bin2hex(random_bytes(32));
        $stmt  = $this->pdo()->prepare(
            'INSERT INTO admin_sessions (token, username, expires_at)
             VALUES (:t, :u, :e)'
        );
        $stmt->execute([
            ':t' => $token,
            ':u' => $username,
            ':e' => date('Y-m-d H:i:s', time() + $ttlSeconds),
        ]);
        $this->purgeExpired();
        return $token;
    }

    public function findValid(string $token): ?array
    {
        if (strlen($token) !== 64) return null;
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM admin_sessions WHERE token = :t AND expires_at > CURRENT_TIMESTAMP'
        );
        $stmt->execute([':t' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function destroy(string $token): void
    {
        $stmt = $this->pdo()->prepare('DELETE FROM admin_sessions WHERE token = :t');
        $stmt->execute([':t' => $token]);
    }

    private function purgeExpired(): void
    {
        $this->pdo()->exec('DELETE FROM admin_sessions WHERE expires_at < CURRENT_TIMESTAMP');
    }
}

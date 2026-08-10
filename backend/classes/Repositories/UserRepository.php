<?php

class UserRepository {
    private PDO $pdo;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo ?? Database::connect();
    }

    public function findByProvider(string $provider, string $providerId): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE provider = ? AND provider_id = ? LIMIT 1');
        $stmt->execute([$provider, $providerId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findByToken(string $token): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE token = ? LIMIT 1');
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findForAuth(string $username): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
        $stmt->execute([$username, $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findForAuthById(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findAllByRole(string $role, string $q = ''): array {
        $sql = 'SELECT id, username, email, provider, avatar, created_at FROM users WHERE role = ?';
        $args = [$role];
        if ($q !== '') {
            $sql .= ' AND (username LIKE ? OR email LIKE ?)';
            $like = '%' . $q . '%';
            $args[] = $like;
            $args[] = $like;
        }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll();
    }

    public function setProvider(int $id, string $provider, string $providerId): void {
        $stmt = $this->pdo->prepare('UPDATE users SET provider = ?, provider_id = ? WHERE id = ?');
        $stmt->execute([$provider, $providerId, $id]);
    }

    public function createOAuthUser(string $username, string $email, string $provider, string $providerId, ?string $avatar): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, password, role, provider, provider_id, avatar) VALUES (?, ?, ?, \'user\', ?, ?, ?)'
        );
        $stmt->execute([$username, $email, bin2hex(random_bytes(16)), $provider, $providerId, Support::nullIfEmpty($avatar)]);
        return (int)$this->pdo->lastInsertId();
    }

    public function setToken(int $id, string $token): void {
        if ($token === '') {
            $stmt = $this->pdo->prepare('UPDATE users SET token = NULL WHERE id = ?');
            $stmt->execute([$id]);
            return;
        }
        $stmt = $this->pdo->prepare('UPDATE users SET token = ? WHERE id = ?');
        $stmt->execute([$token, $id]);
    }

    public function existsByUsernameOrEmail(string $username, string $email, int $excludeId = 0): bool {
        $sql = 'SELECT COUNT(*) FROM users WHERE (username = ? OR email = ?)';
        $args = [$username, $email];
        if ($excludeId > 0) {
            $sql .= ' AND id != ?';
            $args[] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($args);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function countByRole(string $role): int {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE role = ?');
        $stmt->execute([$role]);
        return (int)$stmt->fetchColumn();
    }

    public function create(string $username, string $email, string $passwordHash, string $role): void {
        $stmt = $this->pdo->prepare('INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$username, $email, $passwordHash, $role]);
    }

    public function update(int $id, string $username, string $email, string $passwordHash = ''): void {
        if ($passwordHash !== '') {
            $stmt = $this->pdo->prepare('UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?');
            $stmt->execute([$username, $email, $passwordHash, $id]);
            return;
        }
        $stmt = $this->pdo->prepare('UPDATE users SET username = ?, email = ? WHERE id = ?');
        $stmt->execute([$username, $email, $id]);
    }

    public function updatePassword(int $id, string $passwordHash): void {
        $stmt = $this->pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$passwordHash, $id]);
    }

    public function updateContact(int $id, string $phone, string $address): void {
        $stmt = $this->pdo->prepare('UPDATE users SET phone = ?, address = ? WHERE id = ?');
        $stmt->execute([$phone, $address, $id]);
    }

    public function updateAvatar(int $id, string $avatar): void {
        $stmt = $this->pdo->prepare('UPDATE users SET avatar = ? WHERE id = ?');
        $stmt->execute([$avatar, $id]);
    }

    public function delete(int $id): void {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function getRole(int $id): string {
        $stmt = $this->pdo->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $role = $stmt->fetchColumn();
        return $role === false ? '' : (string)$role;
    }
}
